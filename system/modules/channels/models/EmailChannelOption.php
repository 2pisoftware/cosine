<?php

use Ddeboer\Imap\Server as ImapServer;
use Ddeboer\Imap\Message as ImapMessage;
use Ddeboer\Imap\Search\Flag\Unseen;
use Ddeboer\Imap\Search\Email\To;
use Ddeboer\Imap\Search\Email\From;
use Ddeboer\Imap\Search\Email\Cc;
use Ddeboer\Imap\Search\Text\Subject;
use Ddeboer\Imap\Search\Text\Body;
use Ddeboer\Imap\SearchExpression;

class EmailChannelOption extends DbObject
{
    static $_db_table = "channel_email_option";
    public $_channeltype = "email";
    public $channel_id;
    public $server;
    public $s_username;
    public $s_password;
    public $port;
    public $use_auth;
    public $protocol; // pop3, imap
    static public $_select_protocol = ["POP3", "IMAP"];
    public $subject_filter;
    public $to_filter;
    public $from_filter;
    public $cc_filter;
    public $body_filter;
    public $folder;
    public $post_read_action; // delete, mark as archived, move to folder, apply tag, forward to email
    static public $_select_read_action = ["Archive", "Move to Folder", "Apply Tag", "Forward to", "Delete"];
    public $post_read_parameter; // stores extra data, eg. tag name, folder name, forward email, etc.

    public $verify_peer;
    public $allow_self_signed;

    public function __construct(Web $w)
    {
        parent::__construct($w);
        // $this->setPassword(hash("md5", $w->moduleConf("channels", "__password")));
    }

    public function delete($force = false)
    {
        $channel = $this->getChannel();
        $channel->delete($force);

        parent::delete($force);
    }

    public function getChannel()
    {
        if (!empty($this->channel_id)) {
            return ChannelService::getInstance($this->w)->getChannel($this->channel_id);
        }
        return null;
    }

    public function getNotifyUser()
    {
        $channel = $this->getChannel();
        if (!empty($channel)) {
            return $channel->getNotifyUser();
        }
    }

    public function read()
    {
        // Setup filter array
        $search = new SearchExpression();
        // $search = new LogicalAnd([]);

        if (!empty($this->to_filter)) {
            $search->addCondition(new To($this->to_filter));
        }

        if (!empty($this->from_filter)) {
            $search->addCondition(new From($this->from_filter));
        }

        if (!empty($this->cc_filter)) {
            $search->addCondition(new Cc($this->cc_filter));
        }

        if (!empty($this->subject_filter)) {
            $search->addCondition(new Subject($this->subject_filter));
        }

        if (!empty($this->body_filter)) {
            $search->addCondition(new Body($this->body_filter));
        }

        $search->addCondition(new Unseen());

        // Connect and fetch emails
        LogService::getInstance($this->w)->setLogger('EmailChannel')->info("Connecting to mail server");
        list($connected, $mailbox) = $this->connectToMail();

        if ($connected && $mailbox) {
            LogService::getInstance($this->w)->setLogger('EmailChannel')->info("Getting messages with filters");
            $messages = $mailbox->getMessages($search);

            if (count($messages) > 0) {
                LogService::getInstance($this->w)->setLogger('EmailChannel')->info("Found " . count($messages) . " messages, looping through");

                foreach ($messages as $i => $message) {
                    LogService::getInstance($this->w)->setLogger('EmailChannel')->debug("Reading message " . ($i + 1));

                    $email = new EmailStructure();
                    $email->to = $message->getTo();

                    $email->message_id = $message->getId();

                    $from = $message->getFrom();
                    if ($from) {
                        $email->from = $from->getName();
                        $email->from_email_address = $from->getAddress();
                    }

                    $email->subject = $message->getSubject();

                    // Create ChannelMessages
                    $channel_message = new ChannelMessage($this->w);
                    $channel_message->channel_id = $this->channel_id;
                    $channel_message->message_type = "email";
                    $channel_message->is_processed = 0;
                    $channel_message->insert();

                    $email->body = [];

                    foreach ($message->getParts() as $part) {
                        try {
                            $contentType = strtok($part->getSubtype(), ';');

                            switch ($contentType) {
                                case "plain":
                                    $body = $part->getDecodedContent();
                                    $email->body["plain"] = trim($body);
                                    break;
                                case "html":
                                    $body = $part->getDecodedContent();
                                    $email->body["html"] = trim($body);
                                    break;
                                default:
                                    // Attachment
                                    $parameters = $part->getParameters();
                                    
                                    $name = $parameters->get("filename");
                                    if (empty($name)) {
                                        $name = "attachment_" . substr(uniqid('', true), -6);
                                    }
                                    $name = trim($name, '"\'');
                                    FileService::getInstance($this->w)->saveFileContent(
                                        $channel_message,
                                        $part->getDecodedContent(),
                                        $name,
                                        "channel_email_attachment",
                                        $part->getType() . "/" . $part->getSubtype(),
                                    );
                            }
                        } catch (\Exception $e) {
                            LogService::getInstance($this->w)->setLogger('EmailChannel')->error("Imap_Exception {$e}");
                        }
                    }

                    // Save raw message
                    $rawmessage = $message->getRawMessage();
                    FileService::getInstance($this->w)->saveFileContent($channel_message, serialize($email), "email.txt", "channel_email_raw", "text/plain", 'serialized EmailStructure object | NOT SENT TO CLIENT');
                    FileService::getInstance($this->w)->saveFileContent($channel_message, $rawmessage, "rawemail.txt", "channel_email_raw", "text/plain", "raw email message | NOT SENT TO CLIENT");
                    $message->markAsSeen();
                }
            } else {
                LogService::getInstance($this->w)->setLogger('EmailChannel')->info("No new messages found");
            }
        }
    }

    public function connectToMail($shouldDecrypt = true)
    {
        if ($shouldDecrypt) {
            $this->decrypt();
        }

        try {
            $encryption = false;

            if ($this->use_auth == 1) {
                $encryption = 'ssl';
            }

            // SSL context options
            $contextOptions = [];
            if (!is_null($this->verify_peer)) {
                $contextOptions['verify_peer'] = $this->verify_peer ? true : false;
            }
            if (!is_null($this->allow_self_signed)) {
                $contextOptions['allow_self_signed'] = $this->allow_self_signed ? true : false;
            }

            $server = new ImapServer(
                $this->server,
                $this->port,
                $encryption,
                $contextOptions ? ['ssl' => $contextOptions] : []
            );

            $connection = $server->authenticate($this->s_username, $this->s_password);
            $folder = $this->folder ?: 'INBOX';
            $mailbox = $connection->getMailbox($folder);

            return [true, $mailbox];
        } catch (\Exception $e) {
            LogService::getInstance($this->w)->setLogger('EmailChannel')->error("Error connecting to mail server: " . $e->getMessage());
            return [false, $e->getMessage()];
        }
    }

    #[Deprecated(
        reason: "This method is deprecated as it is unused and will be removed in future versions.",
        version: "7.0.0",
    )]
    public function getFolderList($shouldDecrypt = true)
    {
        list($connected, $connection) = $this->connectToMail($shouldDecrypt);
        $folders = [];

        // if ($connected && $connection) {
        //     foreach ($connection->getMailboxes() as $mailfolder) {
        //         $folders[] = $mailfolder->getName();
        //     }
        // }

        return $folders;
    }
}
