<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\BillOrderBundle\Exception\InvalidBillStatusException;

class InvalidBillStatusExceptionTest extends TestCase
{
    /**
     * 测试异常继承自 RuntimeException
     */
    public function testExceptionInheritance(): void
    {
        $exception = new InvalidBillStatusException();
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
    
    /**
     * 测试默认构造函数参数
     */
    public function testDefaultConstructor(): void
    {
        $exception = new InvalidBillStatusException();
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
    
    /**
     * 测试自定义消息
     */
    public function testCustomMessage(): void
    {
        $message = '无效的账单状态';
        $exception = new InvalidBillStatusException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }
    
    /**
     * 测试自定义错误码
     */
    public function testCustomCode(): void
    {
        $code = 3001;
        $exception = new InvalidBillStatusException('状态错误', $code);
        
        $this->assertSame($code, $exception->getCode());
    }
    
    /**
     * 测试链式异常
     */
    public function testPreviousException(): void
    {
        $previous = new \Exception('状态转换失败');
        $exception = new InvalidBillStatusException('无效状态', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
    
    /**
     * 测试完整构造函数
     */
    public function testFullConstructor(): void
    {
        $message = '账单状态无法从已完成转换为待处理';
        $code = 4001;
        $previous = new \LogicException('业务逻辑错误');
        
        $exception = new InvalidBillStatusException($message, $code, $previous);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
    
    /**
     * 测试异常可以被抛出和捕获
     */
    public function testExceptionCanBeThrownAndCaught(): void
    {
        $this->expectException(InvalidBillStatusException::class);
        $this->expectExceptionMessage('账单状态无效');
        $this->expectExceptionCode(5001);
        
        throw new InvalidBillStatusException('账单状态无效', 5001);
    }
}