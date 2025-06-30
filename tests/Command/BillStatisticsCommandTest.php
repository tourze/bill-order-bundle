<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\Symfony\BillOrderBundle\Command\BillStatisticsCommand;
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;

class BillStatisticsCommandTest extends TestCase
{
    private BillOrderService $billOrderService;
    private BillStatisticsCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->billOrderService = $this->createMock(BillOrderService::class);
        $this->command = new BillStatisticsCommand($this->billOrderService);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('bill:statistics');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteDisplaysStatistics(): void
    {
        $this->billOrderService->expects($this->once())
            ->method('getBillStatistics')
            ->willReturn([
                'draft' => [
                    'count' => 10,
                    'totalAmount' => '1500.00'
                ],
                'pending' => [
                    'count' => 25,
                    'totalAmount' => '35000.00'
                ],
                'paid' => [
                    'count' => 100,
                    'totalAmount' => '250000.00'
                ],
                'completed' => [
                    'count' => 500,
                    'totalAmount' => '1250000.00'
                ],
                'cancelled' => [
                    'count' => 15,
                    'totalAmount' => '5000.00'
                ]
            ]);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        
        // 检查标题
        $this->assertStringContainsString('账单统计信息', $output);
        
        // 检查各状态统计
        $this->assertStringContainsString('草稿', $output);
        $this->assertStringContainsString('10', $output);
        $this->assertStringContainsString('1,500.00', $output);
        
        $this->assertStringContainsString('待付款', $output);
        $this->assertStringContainsString('25', $output);
        $this->assertStringContainsString('35,000.00', $output);
        
        $this->assertStringContainsString('已付款', $output);
        $this->assertStringContainsString('100', $output);
        $this->assertStringContainsString('250,000.00', $output);
        
        $this->assertStringContainsString('已完成', $output);
        $this->assertStringContainsString('500', $output);
        $this->assertStringContainsString('1,250,000.00', $output);
        
        $this->assertStringContainsString('已取消', $output);
        $this->assertStringContainsString('15', $output);
        $this->assertStringContainsString('5,000.00', $output);
        
        // 检查总计
        $this->assertStringContainsString('总计', $output);
        $this->assertStringContainsString('650', $output);
        $this->assertStringContainsString('1,541,500.00', $output);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithEmptyStatistics(): void
    {
        $this->billOrderService->expects($this->once())
            ->method('getBillStatistics')
            ->willReturn([
                'draft' => [
                    'count' => 0,
                    'totalAmount' => 0
                ],
                'pending' => [
                    'count' => 0,
                    'totalAmount' => 0
                ],
                'paid' => [
                    'count' => 0,
                    'totalAmount' => 0
                ],
                'completed' => [
                    'count' => 0,
                    'totalAmount' => 0
                ],
                'cancelled' => [
                    'count' => 0,
                    'totalAmount' => 0
                ]
            ]);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        
        $this->assertStringContainsString('账单统计信息', $output);
        $this->assertStringContainsString('总计', $output);
        $this->assertStringContainsString('0    | 0.00 元', $output);
        
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithException(): void
    {
        $this->billOrderService->expects($this->once())
            ->method('getBillStatistics')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->commandTester->execute([]);
    }
}