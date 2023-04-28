<?php

namespace krzysztofzylka\DatabaseManager;

use krzysztofzylka\DatabaseManager\Enum\DatabaseType;
use krzysztofzylka\DatabaseManager\Exception\DatabaseException;
use PDO;
use PDOException;

class DatabaseConnect
{

    /**
     * PDO Instance
     * @var ?PDO
     */
    private ?PDO $pdoInstance = null;

    /**
     * Connection config
     * @var array
     */
    private array $connConfig = [
        'host' => '127.0.0.1',
        'name' => '',
        'username' => '',
        'password' => '',
        'port' => 3306
    ];

    /**
     * Charset
     * @var string
     */
    private string $charset = 'utf8';

    /**
     * Database type
     * @var DatabaseType
     */
    private DatabaseType $type = DatabaseType::mysql;

    /**
     * SQLite path
     * @var string
     */
    private string $sqlitePath = '';

    /**
     * Debug mode
     * @var bool
     */
    private bool $debug = false;

    /**
     * Set connection type
     * @param DatabaseType $type
     * @return DatabaseConnect
     */
    public function setType(DatabaseType $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set SQLite path
     * @param string $path
     * @return DatabaseConnect
     */
    public function setSqlitePath(string $path): self
    {
        $this->sqlitePath = $path;

        return $this;
    }

    /**
     * @param string $host
     * @param string $databaseName
     * @param string $username
     * @param string $password
     * @param int $port
     * @return DatabaseConnect
     */
    public function setConnection(string $databaseName, string $username, string $password, string $host = '127.0.0.1', int $port = 3306): self
    {
        $this->connConfig = [
            'host' => $host,
            'name' => $databaseName,
            'username' => $username,
            'password' => $password,
            'port' => $port
        ];

        return $this;
    }

    /**
     * Connect
     * @return void
     * @throws DatabaseException
     */
    public function connect(): void
    {
        try {
            if ($this->getType() === DatabaseType::mysql) {
                $this->pdoInstance = new PDO(
                    'mysql:host=' . $this->connConfig['host'] . ';dbname=' . $this->connConfig['name'] . ';charset=' . $this->charset,
                    $this->connConfig['username'] ?? '',
                    $this->connConfig['password'] ?? ''
                );
            } elseif ($this->getType() === DatabaseType::sqlite) {
                $this->pdoInstance = new PDO('sqlite:' . $this->sqlitePath);
            }
        } catch (PDOException $exception) {
            throw new DatabaseException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Get PDO instance
     * @return PDO|null
     */
    public function getPdoInstance(): ?PDO
    {
        return $this->pdoInstance;
    }

    /**
     * Get database type
     * @return DatabaseType
     */
    public function getType(): DatabaseType
    {
        return $this->type;
    }

    /**
     * Set charset
     * @param string $charset
     * @return DatabaseConnect
     */
    public function setCharset(string $charset): self
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Is debug mode
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Set database mode
     * @param bool $debug
     * @return $this
     */
    public function setDebug(bool $debug): DatabaseConnect
    {
        $this->debug = $debug;

        return $this;
    }

}