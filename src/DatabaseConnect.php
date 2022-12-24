<?php

namespace DatabaseManager;

use DatabaseManager\Enum\DatabaseType;
use DatabaseManager\Exception\ConnectException;
use PDO;

class DatabaseConnect {

    private string $host = '127.0.0.1';
    private string $name;
    private string $username;
    private string $password = '';
    private PDO $connection;
    private DatabaseType $type = DatabaseType::mysql;
    private string $sqlitePath = 'database.sqlite';
    private string $charset = 'utf8';

    /**
     * Set host
     * @param string $host
     * @return DatabaseConnect
     */
    public function setHost(string $host = '127.0.0.1') : self {
        $this->host = $host;

        return $this;
    }

    /**
     * Set sqlite path
     * @param string $path
     * @return DatabaseConnect
     */
    public function setSqlitePath(string $path) : self {
        $this->sqlitePath = $path;

        return $this;
    }

    /**
     * Get sqlite path
     * @return string
     */
    public function getSqlitePath() : string {
        return $this->sqlitePath;
    }

    /**
     * Set database type
     * @param DatabaseType $type
     * @return $this
     */
    public function setType(DatabaseType $type) : self {
        $this->type = $type;

        return $this;
    }

    /**
     * Set database name
     * @param string $name
     * @return DatabaseConnect
     */
    public function setDatabaseName(string $name) : self {
        $this->name = $name;

        return $this;
    }

    /**
     * Set username
     * @param string $username
     * @return DatabaseConnect
     */
    public function setUsername(string $username) : self {
        $this->username = $username;

        return $this;
    }

    /**
     * Set password
     * @param string $password
     * @return DatabaseConnect
     */
    public function setPassword(string $password) : self {
        $this->password = $password;

        return $this;
    }

    /**
     * Get database
     * @return string
     */
    public function getDatabaseName() : string {
        return $this->name;
    }

    /**
     * Get password
     * @return string
     */
    public function getPassword() : string {
        return $this->password;
    }

    /**
     * Get username
     * @return string
     */
    public function getUsername() : string {
        return $this->username;
    }

    /**
     * Get host
     * @return string
     */
    public function getHost() : string {
        return $this->host;
    }

    /**
     * Connect to database
     * @return void
     * @throws ConnectException
     */
    public function connect() : void {
        try {
            if ($this->getType() === DatabaseType::mysql) {
                $this->connection = new PDO(
                    'mysql:host=' . $this->getHost() . ';dbname=' . $this->getDatabaseName() . ';charset=' . $this->getCharset(),
                    $this->getUsername() ?? '',
                    $this->getPassword() ?? '');
            } elseif ($this->getType() === DatabaseType::sqlite) {
                $this->connection = new PDO('sqlite:' . $this->getSqlitePath());
            }
        } catch (\PDOException $e) {
            throw new ConnectException($e->getMessage());
        }
    }

    /**
     * Get PDO connection
     * @return PDO
     */
    public function getConnection() : PDO {
        return $this->connection;
    }

    /**
     * Get database type
     * @return DatabaseType
     */
    public function getType() : DatabaseType {
        return $this->type;
    }

    /**
     * Set charset
     * @param string $charset
     * @return DatabaseConnect
     */
    public function setCharset(string $charset) : self {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Get charset
     * @return string
     */
    public function getCharset() : string {
        return $this->charset;
    }

}