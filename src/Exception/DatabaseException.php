<?php

namespace krzysztofzylka\DatabaseManager\Exception;

use Exception;
use Throwable;

class DatabaseException extends Exception {

    /**
     * Hidden message
     * @var string
     */
    private string $hiddenMessage;

    /**
     * Previous exception
     * @var Throwable
     */
    private Throwable $previousException;

    /**
     * Database exception
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null) {
        $this->hiddenMessage = $message;
        $this->previousException = $previous;
        $message = 'Database error';

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get hidden message
     * @return string
     */
    public function getHiddenMessage() : string {
        return $this->hiddenMessage;
    }

    /**
     * Get previous Excepton
     * @return ?Throwable
     */
    public function getPreviousException() : ?Throwable {
        return $this->previousException;
    }

}