<?php

namespace Tourze\Symfony\BillOrderBundle\Tests;

use PHPUnit\Framework\TestCase;
use Tourze\Symfony\BillOrderBundle\BillOrderBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BillOrderBundleTest extends TestCase
{
    /**
     * 测试 Bundle 类继承
     */
    public function testBundleInheritance(): void
    {
        $bundle = new BillOrderBundle();
        
        $this->assertInstanceOf(Bundle::class, $bundle);
    }
    
    /**
     * 测试 Bundle 能够被实例化
     */
    public function testBundleCanBeInstantiated(): void
    {
        $bundle = new BillOrderBundle();
        
        $this->assertNotNull($bundle);
    }
}