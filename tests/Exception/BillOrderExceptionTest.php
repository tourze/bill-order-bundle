<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\BillOrderBundle\Exception\BillOrderException;

class BillOrderExceptionTest extends TestCase
{
    /**
     * 测试异常继承自 RuntimeException
     */
    public function testExceptionInheritance(): void
    {
        $exception = new BillOrderException();
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
    
    /**
     * 测试默认构造函数参数
     */
    public function testDefaultConstructor(): void
    {
        $exception = new BillOrderException();
        
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
        $exception = new BillOrderException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }
    
    /**
     * 测试自定义错误码
     */
    public function testCustomCode(): void
    {
        $code = 500;
        $exception = new BillOrderException('错误', $code);
        
        $this->assertSame($code, $exception->getCode());
    }
    
    /**
     * 测试链式异常
     */
    public function testPreviousException(): void
    {
        $previous = new \Exception('前置异常');
        $exception = new BillOrderException('账单异常', 0, $previous);
        
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
        
        $exception = new BillOrderException($message, $code, $previous);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}