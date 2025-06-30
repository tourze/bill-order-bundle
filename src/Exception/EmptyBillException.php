<?php

namespace Tourze\Symfony\BillOrderBundle\Exception;

/**
 * 空账单异常 - 当账单没有任何项目时抛出
 */
class EmptyBillException extends BillOrderException
{
    public function __construct(string $message = '账单必须至少包含一个项目', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}