<?php
///////////////////////////////////////////////////////////////////////////////
//
//                        Database Session Handling
//
///////////////////////////////////////////////////////////////////////////////
class SessionManager extends DbService implements SessionHandlerInterface
{
    private $tableName = 'sessions';

    // Open and close aren't needed to be overloaded (this is handled in DbService)
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        return $this->_db->get($this->tableName)->where("session_id", $id)->fetchElement("session_data") ?? '';
    }

    public function write(string $id, string $data): bool
    {
        $db_data = [
            'session_id' => $id,
            'session_data' => $data,
            'expires' => time()
        ];

        // Update the existing row, or insert one for a new session
        if ($this->_db->get($this->tableName)->where("session_id", $id)->count() > 0) {
            $this->_db->update($this->tableName, $db_data)->where("session_id", $id)->execute();
        } else {
            $this->_db->insert($this->tableName, $db_data)->execute();
        }
        return true;
    }

    public function destroy(string $id): bool
    {
        $this->_db->delete($this->tableName)->where("session_id", $id)->execute();
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return (int) $this->_db->delete($this->tableName)->where("expires < ?", time() - $max_lifetime)->execute();
    }
}
