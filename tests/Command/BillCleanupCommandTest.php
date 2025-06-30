<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Command;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\Symfony\BillOrderBundle\Command\BillCleanupCommand;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Repository\BillOrderRepository;
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;

class BillCleanupCommandTest extends TestCase
{
    private BillOrderRepository $billOrderRepository;
    private BillOrderService $billOrderService;
    private BillCleanupCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->billOrderRepository = $this->createMock(BillOrderRepository::class);
        $this->billOrderService = $this->createMock(BillOrderService::class);
        $this->command = new BillCleanupCommand($this->billOrderRepository, $this->billOrderService);

        $application = new Application();
        $application->add($this->command);

        $command = $application->find('bill:cleanup');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithDryRun(): void
    {
        $bills = [];
        for ($i = 1; $i <= 5; $i++) {
            $bill = $this->createMock(BillOrder::class);
            $bill->method('getId')->willReturn((string)$i);
            $bill->method('getBillNumber')->willReturn('BILL-' . str_pad((string)$i, 5, '0', STR_PAD_LEFT));
            $bill->method('getCreateTime')->willReturn(new \DateTimeImmutable('-10 days'));
            $bill->method('getTotalAmount')->willReturn('100.00');
            $bills[] = $bill;
        }

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($bills);
        $queryBuilder->method('getQuery')->willReturn($query);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();

        $this->billOrderRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->commandTester->execute([
            '--dry-run' => true
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('找到 5 个超过 7 天未支付的账单', $output);
        $this->assertStringContainsString('这是演示模式，没有实际执行取消操作', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithoutDryRun(): void
    {
        $bills = [];
        for ($i = 1; $i <= 3; $i++) {
            $bill = $this->createMock(BillOrder::class);
            $bill->method('getId')->willReturn((string)$i);
            $bill->method('getBillNumber')->willReturn('BILL-' . str_pad((string)$i, 5, '0', STR_PAD_LEFT));
            $bills[] = $bill;
        }

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($bills);
        $queryBuilder->method('getQuery')->willReturn($query);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();

        $this->billOrderRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $this->billOrderService->expects($this->exactly(3))
            ->method('cancelBill');

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('成功取消 3 个过期账单', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithNoExpiredBills(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $queryBuilder->method('getQuery')->willReturn($query);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();

        $this->billOrderRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有找到过期的待支付账单', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithException(): void
    {
        $bill = $this->createMock(BillOrder::class);
        $bill->method('getId')->willReturn('1');
        $bill->method('getBillNumber')->willReturn('BILL-00001');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$bill]);
        $queryBuilder->method('getQuery')->willReturn($query);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();

        $this->billOrderRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $this->billOrderService->expects($this->once())
            ->method('cancelBill')
            ->willThrowException(new \Exception('Database error'));

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('处理过程中的错误', $output);
        $this->assertStringContainsString('账单 1 (BILL-00001) 取消失败: Database error', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }
}