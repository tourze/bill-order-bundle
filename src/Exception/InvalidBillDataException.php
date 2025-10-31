<?php

namespace Tourze\Symfony\BillOrderBundle\Exception;

/**
 * 无效账单数据异常 - 当账单数据不合法时抛出
 */
class InvalidBillDataException extends BillOrderException
{
    public function __construct(string $message = '账单数据不合法', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
