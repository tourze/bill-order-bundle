<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Symfony\BillOrderBundle\Exception\BillOrderException;

/**
 * @internal
 */
#[CoversClass(BillOrderException::class)]
final class BillOrderExceptionTest extends AbstractExceptionTestCase
{
    /**
     * 测试异常继承自 RuntimeException
     */
    public function testExceptionInheritance(): void
    {
        // 使用具体子类测试抽象基类
        $exception = new class extends BillOrderException {};

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    /**
     * 测试默认构造函数参数
     */
    public function testDefaultConstructor(): void
    {
        $exception = new class extends BillOrderException {};

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    /**
     * 测试自定义消息
     */
    public function testCustomMessage(): void
    {
        $message = '账单订单异常';
        $exception = new class($message) extends BillOrderException {};

        $this->assertSame($message, $exception->getMessage());
    }

    /**
     * 测试自定义错误码
     */
    public function testCustomCode(): void
    {
        $code = 500;
        $exception = new class('错误', $code) extends BillOrderException {};

        $this->assertSame($code, $exception->getCode());
    }

    /**
     * 测试链式异常
     */
    public function testPreviousException(): void
    {
        $previous = new \Exception('前置异常');
        $exception = new class('账单异常', 0, $previous) extends BillOrderException {};

        $this->assertSame($previous, $exception->getPrevious());
    }

    /**
     * 测试完整构造函数
     */
    public function testFullConstructor(): void
    {
        $message = '账单订单处理失败';
        $code = 400;
        $previous = new \RuntimeException('数据库错误');

        $exception = new class($message, $code, $previous) extends BillOrderException {};

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
