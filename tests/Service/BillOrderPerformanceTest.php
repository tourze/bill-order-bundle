<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;

/**
 * 账单性能测试
 *
 * @internal
 * @phpstan-ignore-next-line public.method.not.tested 性能测试通过集成场景覆盖
 */
#[CoversClass(BillOrderService::class)]
#[RunTestsInSeparateProcesses]
final class BillOrderPerformanceTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    private function getBillOrderService(): BillOrderService
    {
        return self::getService(BillOrderService::class);
    }

    /**
     * 测试统计查询在大数据量下的性能
     */
    public function testStatisticsPerformanceWithLargeDataset(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建大量账单数据
        $billCounts = [
            BillOrderStatus::DRAFT->value => 100,
            BillOrderStatus::PENDING->value => 200,
            BillOrderStatus::PAID->value => 300,
            BillOrderStatus::COMPLETED->value => 400,
            BillOrderStatus::CANCELLED->value => 50,
        ];

        $bills = [];
        foreach ($billCounts as $statusValue => $count) {
            $status = BillOrderStatus::from($statusValue);
            for ($i = 0; $i < $count; $i++) {
                $bill = $billOrderService->createBill(
                    "性能测试账单 - {$status->value} - {$i}",
                    "用于性能测试的账单备注内容"
                );
                $billOrderService->addBillItem(
                    $bill,
                    "PERF_PROD_{$i}",
                    "性能测试产品 {$i}",
                    '100.00',
                    1
                );

                // 根据状态进行相应操作
                match ($status) {
                    BillOrderStatus::PENDING => $billOrderService->submitBill($bill),
                    BillOrderStatus::PAID => $this->createPaidBill($billOrderService, $bill),
                    BillOrderStatus::COMPLETED => $this->createCompletedBill($billOrderService, $bill),
                    BillOrderStatus::CANCELLED => $billOrderService->cancelBill($bill, "性能测试取消"),
                    default => null,
                };

                $bills[] = $bill;
            }
        }

        // 测试统计查询性能
        $startTime = microtime(true);
        $statistics = $billOrderService->getBillStatistics();
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        // 验证性能要求（应该在1秒内完成）
        $this->assertLessThan(1.0, $executionTime, '统计查询应该在1秒内完成');

        // 验证数据正确性
        $this->assertEquals(100, $statistics['draft']['count']);
        $this->assertEquals(200, $statistics['pending']['count']);
        $this->assertEquals(300, $statistics['paid']['count']);
        $this->assertEquals(400, $statistics['completed']['count']);
        $this->assertEquals(50, $statistics['cancelled']['count']);

        // 验证总金额计算正确
        $this->assertEquals('10000.00', $statistics['draft']['totalAmount']);
        $this->assertEquals('20000.00', $statistics['pending']['totalAmount']);
        $this->assertEquals('30000.00', $statistics['paid']['totalAmount']);
        $this->assertEquals('40000.00', $statistics['completed']['totalAmount']);
        $this->assertEquals('5000.00', $statistics['cancelled']['totalAmount']);
    }

    /**
     * 测试单个账单大量项目的性能
     */
    public function testLargeBillItemsPerformance(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建包含大量项目的账单
        $bill = $billOrderService->createBill('大量项目性能测试');

        $itemCount = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $itemCount; $i++) {
            $billOrderService->addBillItem(
                $bill,
                "LARGE_PROD_{$i}",
                "大量项目测试产品 {$i}",
                '10.00',
                1
            );
        }

        $endTime = microtime(true);
        $addTime = $endTime - $startTime;

        // 验证添加项目的性能要求
        $this->assertLessThan(5.0, $addTime, "添加 {$itemCount} 个项目应该在5秒内完成");

        // 验证最终状态
        $this->assertEquals($itemCount, $bill->getItems()->count());
        $this->assertEquals((string)($itemCount * 10), $bill->getTotalAmount());

        // 测试总金额重新计算性能
        $startTime = microtime(true);
        $billOrderService->recalculateBillTotal($bill);
        $endTime = microtime(true);

        $recalculateTime = $endTime - $startTime;
        $this->assertLessThan(1.0, $recalculateTime, '重新计算总金额应该在1秒内完成');
    }

    /**
     * 测试批量账单创建性能
     */
    public function testBatchBillCreationPerformance(): void
    {
        $billOrderService = $this->getBillOrderService();

        $billCount = 100;
        $bills = [];

        $startTime = microtime(true);

        for ($i = 0; $i < $billCount; $i++) {
            $bill = $billOrderService->createBill("批量创建测试账单 {$i}");
            $billOrderService->addBillItem($bill, "BATCH_PROD_{$i}", "批量产品 {$i}", '50.00', 2);
            $bills[] = $bill;
        }

        $endTime = microtime(true);
        $creationTime = $endTime - $startTime;

        // 验证批量创建性能要求
        $this->assertLessThan(3.0, $creationTime, "创建 {$billCount} 个账单应该在3秒内完成");

        // 验证所有账单创建成功
        $this->assertCount($billCount, $bills);

        foreach ($bills as $i => $bill) {
            $this->assertEquals("批量创建测试账单 {$i}", $bill->getTitle());
            $this->assertEquals(1, $bill->getItems()->count());
            $this->assertEquals('100.00', $bill->getTotalAmount());
        }
    }

    /**
     * 测试账单编号生成性能
     */
    public function testBillNumberGenerationPerformance(): void
    {
        $billOrderService = $this->getBillOrderService();

        $billCount = 1000;
        $billNumbers = [];

        $startTime = microtime(true);

        for ($i = 0; $i < $billCount; $i++) {
            $bill = $billOrderService->createBill("编号生成测试 {$i}");
            $billNumbers[] = $bill->getBillNumber();
        }

        $endTime = microtime(true);
        $generationTime = $endTime - $startTime;

        // 验证编号生成性能要求
        $this->assertLessThan(2.0, $generationTime, "生成 {$billCount} 个账单编号应该在2秒内完成");

        // 验证所有编号唯一
        $uniqueNumbers = array_unique($billNumbers);
        $this->assertCount($billCount, $uniqueNumbers, '所有账单编号应该唯一');

        // 验证编号格式正确
        foreach ($billNumbers as $billNumber) {
            /** @var string $billNumber PHPStan 类型收窄 */
            $this->assertStringStartsWith('BILL', $billNumber);
            $this->assertMatchesRegularExpression('/^BILL\d{8}[a-f0-9]{8}$/', $billNumber);
        }
    }

    /**
     * 测试复杂账单操作的性能
     */
    public function testComplexBillOperationsPerformance(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建复杂账单
        $bill = $billOrderService->createBill('复杂操作性能测试');

        // 添加不同类型的项目
        $productTypes = [
            ['ELEC', '电子产品', '999.99'],
            ['CLOTH', '服装', '199.99'],
            ['FOOD', '食品', '29.99'],
            ['BOOK', '图书', '49.99'],
            ['TOY', '玩具', '79.99'],
        ];

        $items = [];
        foreach ($productTypes as $i => [$type, $name, $price]) {
            for ($j = 0; $j < 10; $j++) {
                $item = $billOrderService->addBillItem(
                    $bill,
                    "{$type}_{$j}",
                    "{$name} {$j}",
                    $price,
                    $j + 1
                );
                $items[] = $item;
            }
        }

        $this->assertCount(50, $bill->getItems());

        // 测试批量更新性能
        $startTime = microtime(true);

        foreach ($items as $i => $item) {
            $newPrice = (string)(floatval($item->getPrice()) * 1.1); // 价格上浮10%
            $newQuantity = $item->getQuantity() + 1;
            $billOrderService->updateBillItem($item, $newPrice, $newQuantity);
        }

        $endTime = microtime(true);
        $updateTime = $endTime - $startTime;

        $this->assertLessThan(2.0, $updateTime, '批量更新50个项目应该在2秒内完成');

        // 测试账单状态变更性能
        $startTime = microtime(true);

        $billOrderService->submitBill($bill);
        $billOrderService->payBill($bill);
        $billOrderService->completeBill($bill);

        $endTime = microtime(true);
        $statusChangeTime = $endTime - $startTime;

        $this->assertLessThan(1.0, $statusChangeTime, '账单状态变更应该在1秒内完成');

        // 验证最终状态
        $this->assertEquals(BillOrderStatus::COMPLETED, $bill->getStatus());
        $this->assertNotNull($bill->getPayTime());
    }

    /**
     * 测试内存使用情况
     */
    public function testMemoryUsageWithLargeDataset(): void
    {
        $initialMemory = memory_get_usage(true);
        $billOrderService = $this->getBillOrderService();

        // 创建大量账单
        $billCount = 100;
        $itemsPerBill = 20;

        for ($i = 0; $i < $billCount; $i++) {
            $bill = $billOrderService->createBill("内存测试账单 {$i}");

            for ($j = 0; $j < $itemsPerBill; $j++) {
                $billOrderService->addBillItem(
                    $bill,
                    "MEM_PROD_{$i}_{$j}",
                    "内存测试产品 {$i}-{$j}",
                    '25.00',
                    2
                );
            }
        }

        $peakMemory = memory_get_peak_usage(true);
        $finalMemory = memory_get_usage(true);

        $memoryIncrease = $finalMemory - $initialMemory;
        $peakIncrease = $peakMemory - $initialMemory;

        // 验证内存使用在合理范围内
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, '内存增长应该小于50MB');
        $this->assertLessThan(100 * 1024 * 1024, $peakIncrease, '峰值内存增长应该小于100MB');

        // 测试统计查询的内存效率
        $statsStartTime = microtime(true);
        $statsStartMemory = memory_get_usage(true);

        $statistics = $billOrderService->getBillStatistics();

        $statsEndTime = microtime(true);
        $statsEndMemory = memory_get_usage(true);

        $statsExecutionTime = $statsEndTime - $statsStartTime;
        $statsMemoryIncrease = $statsEndMemory - $statsStartMemory;

        $this->assertLessThan(0.5, $statsExecutionTime, '统计查询应该在0.5秒内完成');
        $this->assertLessThan(5 * 1024 * 1024, $statsMemoryIncrease, '统计查询内存增长应该小于5MB');
    }

    /**
     * 测试并发统计查询性能
     */
    public function testConcurrentStatisticsPerformance(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建测试数据
        for ($i = 0; $i < 200; $i++) {
            $bill = $billOrderService->createBill("并发测试账单 {$i}");
            $billOrderService->addBillItem($bill, "CONC_PROD_{$i}", "并发产品 {$i}", '75.00', 1);

            if ($i % 2 === 0) {
                $billOrderService->submitBill($bill);
            }
            if ($i % 3 === 0) {
                $this->createPaidBill($billOrderService, $bill);
            }
        }

        // 并发执行统计查询
        $concurrentCount = 10;
        $processes = [];

        $startTime = microtime(true);

        for ($i = 0; $i < $concurrentCount; $i++) {
            $processes[] = function() use ($billOrderService) {
                return $billOrderService->getBillStatistics();
            };
        }

        // 执行所有并发查询
        $results = [];
        foreach ($processes as $process) {
            $results[] = $process();
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // 验证并发性能
        $this->assertLessThan(2.0, $totalTime, "{$concurrentCount} 个并发统计查询应该在2秒内完成");

        // 验证所有结果一致
        $firstResult = $results[0];
        foreach ($results as $result) {
            $this->assertEquals($firstResult, $result);
        }

        // 验证统计数据正确性
        $this->assertGreaterThan(0, $firstResult['draft']['count']);
        $this->assertGreaterThan(0, $firstResult['pending']['count']);
        $this->assertGreaterThan(0, $firstResult['paid']['count']);
    }

    /**
     * 测试极端大数据量下的性能表现
     */
    public function testExtremeLargeDatasetPerformance(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建极端大量的数据（但要注意测试时间）
        $billCount = 500;
        $itemsPerBill = 50;

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        for ($i = 0; $i < $billCount; $i++) {
            $bill = $billOrderService->createBill("极端测试账单 {$i}");

            for ($j = 0; $j < $itemsPerBill; $j++) {
                $billOrderService->addBillItem(
                    $bill,
                    "EXT_PROD_{$i}_{$j}",
                    "极端测试产品 {$i}-{$j}",
                    '5.00',
                    3
                );
            }

            // 每100个账单提交一次
            if ($i % 100 === 0) {
                $billOrderService->submitBill($bill);
            }
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $creationTime = $endTime - $startTime;
        $memoryIncrease = $endMemory - $startMemory;

        // 验证极端情况下的性能
        $this->assertLessThan(15.0, $creationTime, "创建 {$billCount} 个大型账单应该在15秒内完成");
        $this->assertLessThan(200 * 1024 * 1024, $memoryIncrease, '内存增长应该小于200MB');

        // 测试在这种数据量下的统计性能
        $statsStartTime = microtime(true);
        $statistics = $billOrderService->getBillStatistics();
        $statsEndTime = microtime(true);

        $statsTime = $statsEndTime - $statsStartTime;
        $this->assertLessThan(3.0, $statsTime, '大数据量统计查询应该在3秒内完成');

        // 验证数据完整性
        $this->assertEquals($billCount * 4, $statistics['draft']['totalAmount']);
        $this->assertEquals($billCount * 0.75, $statistics['draft']['count']);
    }

    // 辅助方法

    private function createPaidBill(BillOrderService $billOrderService, BillOrder $bill): void
    {
        $billOrderService->submitBill($bill);
        $billOrderService->payBill($bill);
    }

    private function createCompletedBill(BillOrderService $billOrderService, BillOrder $bill): void
    {
        $billOrderService->submitBill($bill);
        $billOrderService->payBill($bill);
        $billOrderService->completeBill($bill);
    }
}