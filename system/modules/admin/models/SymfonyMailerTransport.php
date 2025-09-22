<?php

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\Part\DataPart;

class SymfonyMailerTransport implements GenericTransport
{
    private Web $w;
    private $transport;

    /**
     * @param Web $w
     * @param string $layer
     */
    public function __construct($w, $layer)
    {
        $this->w = &$w;
        $this->transport = $this->getTransport($layer);
    }

    /**
     * @param string $layer
     */
    public function getTransport($layer)
    {
        if (!empty($this->transport)) {
            return $this->transport;
        }

        switch (strtolower($layer)) {
            case "smtp":
            case "swiftmailer":
            case "ses":
            case "symfonymailer":
                $host = Config::get('email.host');
                $port = Config::get('email.port');
                $encryption = Config::get('email.encryption', Config::get('email.auth') == true ? 'ssl' : null);

                $username = Config::get('email.username');
                $password = Config::get('email.password');

                return (new EsmtpTransport($host, $port, $encryption))
                    ->setUsername($username)
                    ->setPassword($password);
            case "sendmail":
                $command = Config::get('email.command');

                return new SendmailTransport(!empty($command) ? $command : null);
        }
    }

    public function send(
        $to,
        $replyto,
        $subject,
        $body,
        $cc = null,
        $bcc = null,
        $attachments = [],
        $headers = []
    ) {
        $mailer = new Mailer($this->transport);

        $email = (new Email())
            ->from($replyto)
            ->to($to)
            ->text($body)
            ->html($body);

        $email->replyTo(...(is_array($replyto) ? $replyto : [$replyto]));

        if (!empty($cc)) {
            if (strpos($cc ?? "", ",") !== false) {
                $cc = array_map("trim", explode(',', $cc));
            }

            $email->cc($cc);
        }

        if (!empty($bcc)) {
            if (strpos($bcc ?? "", ",") !== false) {
                $bcc = array_map("trim", explode(',', $bcc));
            }

            $email->bcc($bcc);
        }

        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                if (!empty($attachment)) {
                    $email->addPart(DataPart::fromPath($attachment));
                }
            }
        }

        if (!empty($headers)) {
            foreach ($headers as $header => $value) {
                $email->getHeaders()->addTextHeader($header, $value);
            }
        }

        try {
            $mailer->send($email);
        } catch (Exception $e) {
            LogService::getInstance($this->w)->setLogger(MailService::$logger)->error("Failed to send email: " . $e);
            return 1;
        }

        return 0;
    }
}
