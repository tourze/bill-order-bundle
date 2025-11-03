<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Command;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\Symfony\BillOrderBundle\Command\BillCleanupCommand;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Repository\BillOrderRepository;
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;

/**
 * @internal
 */
#[CoversClass(BillCleanupCommand::class)]
#[RunTestsInSeparateProcesses]
final class BillCleanupCommandTest extends AbstractCommandTestCase
{
    /** @var BillOrderRepository&MockObject */
    private BillOrderRepository $billOrderRepository;

    /** @var BillOrderService&MockObject */
    private BillOrderService $billOrderService;

    private BillCleanupCommand $command;

    private CommandTester $commandTester;

    protected function onSetUp(): void
    {
        // 使用具体类 BillOrderRepository Mock：
        // 1) Repository 类通常没有对应的接口，而是直接继承 EntityRepository
        // 2) 命令行测试中需要模拟数据库查询操作，使用具体类更直接
        // 3) 这是测试环境，不影响生产代码的解耦性
        $this->billOrderRepository = $this->createMock(BillOrderRepository::class);

        // 必须使用具体类 BillOrderService 的 Mock，原因：
        // 1. BillOrderService 没有对应的接口，是一个具体的业务服务类
        // 2. 命令行测试需要验证与 cancelBill 方法的具体交互行为
        // 3. 该服务类封装了业务逻辑，使用具体类模拟符合测试需求
        $this->billOrderService = $this->createMock(BillOrderService::class);

        // 将 Mock 服务注册到容器中
        $container = self::getContainer();
        $container->set(BillOrderRepository::class, $this->billOrderRepository);
        $container->set(BillOrderService::class, $this->billOrderService);

        $command = $container->get(BillCleanupCommand::class);
        $this->assertInstanceOf(BillCleanupCommand::class, $command);
        $this->command = $command;
        $this->commandTester = new CommandTester($this->command);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    public function testExecuteWithDryRun(): void
    {
        $bills = [];
        for ($i = 1; $i <= 5; ++$i) {
            // 使用具体类 BillOrder：
            // 1) Entity 类是数据模型，通常没有对应的接口
            // 2) 由于 getId() 和 getCreateTime() 是 final 方法，无法 Mock，使用反射设置属性值
            // 3) 这是测试数据创建，不涉及实际的业务逻辑解耦
            $bill = new BillOrder();
            $bill->setBillNumber('BILL-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT));
            $bill->setTotalAmount('100.00');

            // 使用反射设置 final 方法的属性值
            $reflection = new \ReflectionClass($bill);

            // 设置 ID
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($bill, (string) $i);

            // 设置创建时间
            $createTimeProperty = $reflection->getProperty('createTime');
            $createTimeProperty->setAccessible(true);
            $createTimeProperty->setValue($bill, new \DateTimeImmutable('-10 days'));

            $bills[] = $bill;
        }

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($bills);

        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->billOrderRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('找到 5 个超过 7 天未支付的账单', $output);
        $this->assertStringContainsString('这是演示模式，没有实际执行取消操作', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithoutDryRun(): void
    {
        $bills = [];
        for ($i = 1; $i <= 3; ++$i) {
            // 使用具体类 BillOrder：
            // 1) Entity 类是数据模型，通常没有对应的接口
            // 2) 由于 getId() 是 final 方法，无法 Mock，使用反射设置属性值
            // 3) 这是测试数据创建，不涉及实际的业务逻辑解耦
            $bill = new BillOrder();
            $bill->setBillNumber('BILL-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT));

            // 使用反射设置 final 方法的属性值
            $reflection = new \ReflectionClass($bill);

            // 设置 ID
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($bill, (string) $i);

            $bills[] = $bill;
        }

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($bills);

        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->billOrderRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $this->billOrderService->expects($this->exactly(3))
            ->method('cancelBill')
        ;

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

        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->billOrderRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('没有找到过期的待支付账单', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteWithException(): void
    {
        // 使用具体类 BillOrder：
        // 1) Entity 类是数据模型，通常没有对应的接口
        // 2) 由于 getId() 是 final 方法，无法 Mock，使用反射设置属性值
        // 3) 这是测试数据创建，不涉及实际的业务逻辑解耦
        $bill = new BillOrder();
        $bill->setBillNumber('BILL-00001');

        // 使用反射设置 final 方法的属性值
        $reflection = new \ReflectionClass($bill);

        // 设置 ID
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($bill, '1');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([$bill]);

        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->billOrderRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $this->billOrderService->expects($this->once())
            ->method('cancelBill')
            ->willThrowException(new \Exception('Database error'))
        ;

        $this->commandTester->execute([]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('处理过程中的错误', $output);
        $this->assertStringContainsString('账单 1 (BILL-00001) 取消失败: Database error', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testOptionDays(): void
    {
        $bills = [];
        for ($i = 1; $i <= 3; ++$i) {
            // 使用具体类 BillOrder：
            // 1) Entity 类是数据模型，通常没有对应的接口
            // 2) 由于 getId() 是 final 方法，无法 Mock，使用反射设置属性值
            // 3) 这是测试数据创建，不涉及实际的业务逻辑解耦
            $bill = new BillOrder();
            $bill->setBillNumber('BILL-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT));

            // 使用反射设置 final 方法的属性值
            $reflection = new \ReflectionClass($bill);

            // 设置 ID
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($bill, (string) $i);

            $bills[] = $bill;
        }

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($bills);

        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->billOrderRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $this->billOrderService->expects($this->exactly(3))
            ->method('cancelBill')
        ;

        $this->commandTester->execute([
            '--days' => 14,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('找到 3 个超过 14 天未支付的账单', $output);
        $this->assertStringContainsString('成功取消 3 个过期账单', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testOptionDryRun(): void
    {
        $bills = [];
        for ($i = 1; $i <= 2; ++$i) {
            // 使用具体类 BillOrder：
            // 1) Entity 类是数据模型，通常没有对应的接口
            // 2) 由于 getId() 和 getCreateTime() 是 final 方法，无法 Mock，使用反射设置属性值
            // 3) 这是测试数据创建，不涉及实际的业务逻辑解耦
            $bill = new BillOrder();
            $bill->setBillNumber('BILL-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT));
            $bill->setTotalAmount('150.00');

            // 使用反射设置 final 方法的属性值
            $reflection = new \ReflectionClass($bill);

            // 设置 ID
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($bill, (string) $i);

            // 设置创建时间
            $createTimeProperty = $reflection->getProperty('createTime');
            $createTimeProperty->setAccessible(true);
            $createTimeProperty->setValue($bill, new \DateTimeImmutable('-10 days'));

            $bills[] = $bill;
        }

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($bills);

        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->billOrderRepository->method('createQueryBuilder')->willReturn($queryBuilder);

        $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('找到 2 个超过 7 天未支付的账单', $output);
        $this->assertStringContainsString('这是演示模式，没有实际执行取消操作', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }
}
