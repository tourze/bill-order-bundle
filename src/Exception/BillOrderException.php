<?php

namespace Tourze\Symfony\BillOrderBundle\Exception;

/**
 * 账单订单通用异常基类
 */
class BillOrderException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}