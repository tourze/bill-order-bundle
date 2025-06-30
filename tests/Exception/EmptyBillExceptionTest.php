<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\BillOrderBundle\Exception\BillOrderException;
use Tourze\Symfony\BillOrderBundle\Exception\EmptyBillException;

class EmptyBillExceptionTest extends TestCase
{
    /**
     * 测试异常继承关系
     */
    public function testExceptionInheritance(): void
    {
        $exception = new EmptyBillException();
        
        $this->assertInstanceOf(BillOrderException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
    
    /**
     * 测试默认消息
     */
    public function testDefaultMessage(): void
    {
        $exception = new EmptyBillException();
        
        $this->assertSame('账单必须至少包含一个项目', $exception->getMessage());
    }
    
    /**
     * 测试自定义消息
     */
    public function testCustomMessage(): void
    {
        $message = '账单项目为空';
        $exception = new EmptyBillException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }
    
    /**
     * 测试自定义错误码
     */
    public function testCustomCode(): void
    {
        $code = 1001;
        $exception = new EmptyBillException('空账单', $code);
        
        $this->assertSame($code, $exception->getCode());
    }
    
    /**
     * 测试链式异常
     */
    public function testPreviousException(): void
    {
        $previous = new \Exception('验证失败');
        $exception = new EmptyBillException('账单为空', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
    
    /**
     * 测试完整构造函数
     */
    public function testFullConstructor(): void
    {
        $message = '账单不能为空';
        $code = 2001;
        $previous = new \RuntimeException('数据验证失败');
        
        $exception = new EmptyBillException($message, $code, $previous);
        
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
    
    /**
     * 测试默认参数
     */
    public function testDefaultParameters(): void
    {
        $exception = new EmptyBillException();
        
        $this->assertSame('账单必须至少包含一个项目', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }
}