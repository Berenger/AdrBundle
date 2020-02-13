<?php

namespace AdrBundle\Exceptions;

use Exception;

/**
 * Class ErrorApiException
 * @packageAdrBundle\Exceptions
 */
class ErrorApiException extends Exception
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var int
     */
    protected $status_code;

    /**
     * ErrorApiException constructor.
     * @param array $data
     * @param int $status_code
     * @param string $message
     * @param int $code
     */
    public function __construct(int $status_code = 500, array $data = [], $message = "", $code = 0)
    {
        parent::__construct($message, $code);

        $this->data = $data;
        $this->status_code = $status_code;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->status_code;
    }
}
