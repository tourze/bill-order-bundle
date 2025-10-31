<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\Symfony\BillOrderBundle\Command\BillStatisticsCommand;
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;

/**
 * @internal
 */
#[CoversClass(BillStatisticsCommand::class)]
#[RunTestsInSeparateProcesses]
final class BillStatisticsCommandTest extends AbstractCommandTestCase
{
    /** @var BillOrderService&MockObject */
    private BillOrderService $billOrderService;

    private BillStatisticsCommand $command;

    private CommandTester $commandTester;

    protected function onSetUp(): void
    {
        // 必须使用具体类 BillOrderService 的 Mock，原因：
        // 1. BillOrderService 没有对应的接口，是一个具体的业务服务类
        // 2. 命令行测试需要验证与 getBillStatistics 方法的具体交互行为
        // 3. 该服务类封装了业务逻辑，使用具体类模拟符合测试需求
        $this->billOrderService = $this->createMock(BillOrderService::class);

        // 将 Mock 服务注册到容器中
        $container = self::getContainer();
        $container->set(BillOrderService::class, $this->billOrderService);

        $command = $container->get(BillStatisticsCommand::class);
        $this->assertInstanceOf(BillStatisticsCommand::class, $command);
        $this->command = $command;
        $this->commandTester = new CommandTester($this->command);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    public function testExecuteDisplaysStatistics(): void
    {
        $this->billOrderService->expects($this->once())
            ->method('getBillStatistics')
            ->willReturn([
                'draft' => [
                    'count' => 10,
                    'totalAmount' => '1500.00',
                ],
                'pending' => [
                    'count' => 25,
                    'totalAmount' => '35000.00',
                ],
                'paid' => [
                    'count' => 100,
                    'totalAmount' => '250000.00',
                ],
                'completed' => [
                    'count' => 500,
                    'totalAmount' => '1250000.00',
                ],
                'cancelled' => [
                    'count' => 15,
                    'totalAmount' => '5000.00',
                ],
            ])
        ;

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
                    'totalAmount' => 0,
                ],
                'pending' => [
                    'count' => 0,
                    'totalAmount' => 0,
                ],
                'paid' => [
                    'count' => 0,
                    'totalAmount' => 0,
                ],
                'completed' => [
                    'count' => 0,
                    'totalAmount' => 0,
                ],
                'cancelled' => [
                    'count' => 0,
                    'totalAmount' => 0,
                ],
            ])
        ;

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
            ->willThrowException(new \Exception('Database connection failed'))
        ;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->commandTester->execute([]);
    }

    public function testOptionFormat(): void
    {
        $this->billOrderService->expects($this->once())
            ->method('getBillStatistics')
            ->willReturn([
                'draft' => [
                    'count' => 5,
                    'totalAmount' => '750.00',
                ],
                'pending' => [
                    'count' => 10,
                    'totalAmount' => '1500.00',
                ],
                'paid' => [
                    'count' => 20,
                    'totalAmount' => '5000.00',
                ],
                'completed' => [
                    'count' => 100,
                    'totalAmount' => '25000.00',
                ],
                'cancelled' => [
                    'count' => 3,
                    'totalAmount' => '300.00',
                ],
            ])
        ;

        $this->commandTester->execute([
            '--format' => 'json',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('"draft"', $output);
        $this->assertStringContainsString('"count": 5', $output);
        $this->assertStringContainsString('"totalAmount": "750.00"', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }
}
