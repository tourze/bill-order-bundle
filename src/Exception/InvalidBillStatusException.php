<?php

namespace Tourze\Symfony\BillOrderBundle\Exception;

/**
 * 账单状态无效异常
 */
class InvalidBillStatusException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
