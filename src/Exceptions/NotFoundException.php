<?php

namespace HairRescuer\AccountBook\Exceptions;

use Throwable;

class NotFoundException extends \Exception
{
    const TYPE_ACCOUNT = 1;
    const TYPE_TRANSACTION = 2;

    protected $type;

    public function __construct(int $type, $message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }
}
