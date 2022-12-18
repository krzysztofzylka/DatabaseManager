<?php

namespace DatabaseManager\Exception;

use Exception;
use Throwable;

class DatabaseManagerException extends Exception {

    private string $hiddenMessage;

    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null) {
        $this->hiddenMessage = $message;
        $message = 'Database error';

        parent::__construct($message, $code, $previous);
    }

    private function getHiddenMessage() : string {
        return $this->hiddenMessage;
    }

}