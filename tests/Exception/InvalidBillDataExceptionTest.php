<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Symfony\BillOrderBundle\Exception\BillOrderException;
use Tourze\Symfony\BillOrderBundle\Exception\InvalidBillDataException;

/**
 * @internal
 */
#[CoversClass(InvalidBillDataException::class)]
final class InvalidBillDataExceptionTest extends AbstractExceptionTestCase
{
    public function testConstruct(): void
    {
        $exception = new InvalidBillDataException();

        $this->assertInstanceOf(BillOrderException::class, $exception);
        $this->assertEquals('账单数据不合法', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructWithParameters(): void
    {
        $message = 'Custom error message';
        $code = 123;
        $previous = new \RuntimeException('Previous exception');

        $exception = new InvalidBillDataException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
