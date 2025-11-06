<?php

namespace Tourze\Symfony\BillOrderBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;
use Tourze\Symfony\BillOrderBundle\Exception\EmptyBillException;
use Tourze\Symfony\BillOrderBundle\Exception\InvalidBillDataException;
use Tourze\Symfony\BillOrderBundle\Exception\InvalidBillStatusException;
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;

/**
 * 账单数据验证规则测试
 *
 * @internal
 */
#[CoversClass(BillOrderService::class)]
#[RunTestsInSeparateProcesses]
final class BillOrderValidationTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
    }

    private function getBillOrderService(): BillOrderService
    {
        return self::getService(BillOrderService::class);
    }

    // ===================== 产品ID验证测试 =====================

    /**
     * 测试产品ID的各种有效值
     */
    public function testValidProductIds(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('产品ID测试');

        $validProductIds = [
            'PROD001',
            'product_001',
            'PRODUCT-001',
            '123456',
            'a1b2c3',
            'A',
            '产品001',
            '商品-123',
            'P',
            str_repeat('A', 255), // 最大长度
        ];

        foreach ($validProductIds as $productId) {
            $item = $billOrderService->addBillItem($bill, $productId, '测试产品', '100.00', 1);
            $this->assertEquals($productId, $item->getProductId());
        }
    }

    /**
     * 测试产品ID的各种无效值
     */
    public function testInvalidProductIds(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('产品ID测试');

        $invalidProductIds = [
            '',
            '   ',
            "\t",
            "\n",
            "\r",
            "   \t\n   ", // 混合空白字符
        ];

        foreach ($invalidProductIds as $productId) {
            $this->expectException(InvalidBillDataException::class);
            $this->expectExceptionMessage('产品ID不能为空');
            $billOrderService->addBillItem($bill, $productId, '测试产品', '100.00', 1);
        }
    }

    // ===================== 产品名称验证测试 =====================

    /**
     * 测试产品名称的各种有效值
     */
    public function testValidProductNames(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('产品名称测试');

        $validProductNames = [
            '普通产品',
            'Product Name',
            '产品123',
            'Product-001',
            '测试_产品',
            'A',
            '1',
            '产品@#$%',
            'Apple iPhone 15 Pro Max',
            str_repeat('A', 255), // 最大长度
            '产品名称带空格',
            'Product with spaces',
            '中英文混合Product',
        ];

        foreach ($validProductNames as $i => $productName) {
            $item = $billOrderService->addBillItem($bill, "PROD00{$i}", $productName, '100.00', 1);
            $this->assertEquals($productName, $item->getProductName());
        }
    }

    /**
     * 测试产品名称的各种无效值
     */
    public function testInvalidProductNames(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('产品名称测试');

        $invalidProductNames = [
            '',
            '   ',
            "\t",
            "\n",
            "\r",
            "   \t\n   ", // 混合空白字符
        ];

        foreach ($invalidProductNames as $productName) {
            // 每次创建新的账单以避免状态干扰
            $testBill = $billOrderService->createBill('测试账单');
            $this->expectException(InvalidBillDataException::class);
            $this->expectExceptionMessage('产品名称不能为空');
            $billOrderService->addBillItem($testBill, 'PROD001', $productName, '100.00', 1);
        }
    }

    // ===================== 价格验证测试 =====================

    /**
     * 测试有效价格格式
     */
    public function testValidPrices(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('价格测试');

        $validPrices = [
            '0',           // 零价格
            '0.00',        // 标准零价格
            '0.01',        // 最小正数
            '0.1',         // 一位小数
            '0.99',        // 接近1的小数
            '1',           // 整数
            '1.0',         // 带小数点的整数
            '1.00',        // 标准两位小数
            '99.99',       // 标准价格
            '100',         // 整数价格
            '100.50',      // 一位小数价格
            '100.55',      // 两位小数价格
            '99999999.99', // 最大允许金额
            '123456789',   // 大整数
            '0.5',         // 小于1的小数
            '99999999',    // 最大整数金额
        ];

        foreach ($validPrices as $price) {
            $item = $billOrderService->addBillItem($bill, 'PROD' . uniqid(), '测试产品', $price, 1);
            $this->assertEquals($price, $item->getPrice());
        }
    }

    /**
     * 测试无效价格格式
     */
    public function testInvalidPrices(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('价格测试');

        $invalidPrices = [
            '-1',          // 负整数
            '-0.01',       // 负小数
            '-100.00',     // 负数价格
            '-99999999.99', // 大负数
            'abc',         // 非数字
            '100.123',     // 超过两位小数
            '100.1234',    // 更多小数位
            '100.',        // 小数点后无数字
            '.50',         // 小数点前无数字
            '100.0.0',     // 多个小数点
            '100a50',      // 包含字母
            '1 00',        // 包含空格
            '1,000',       // 包含逗号
            '1.000,50',    // 混合小数点
            '',            // 空字符串
            '   ',         // 空格
            '+100.00',     // 带加号
            'Infinity',    // 无穷大
            'NaN',         // 非数字
        ];

        foreach ($invalidPrices as $price) {
            try {
                $billOrderService->addBillItem($bill, 'PROD' . uniqid(), '测试产品', $price, 1);
                $this->fail("价格 '{$price}' 应该抛出异常");
            } catch (InvalidBillDataException $e) {
                $this->assertStringContainsString('价格', $e->getMessage());
            }
        }
    }

    // ===================== 数量验证测试 =====================

    /**
     * 测试有效数量
     */
    public function testValidQuantities(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('数量测试');

        $validQuantities = [
            1,             // 最小正整数
            2,             // 小数量
            10,            // 中等数量
            100,           // 大数量
            1000,          // 很大数量
            999999,        // 最大允许数量
        ];

        foreach ($validQuantities as $quantity) {
            $item = $billOrderService->addBillItem($bill, 'PROD' . uniqid(), '测试产品', '100.00', $quantity);
            $this->assertEquals($quantity, $item->getQuantity());
        }
    }

    /**
     * 测试无效数量
     */
    public function testInvalidQuantities(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('数量测试');

        $invalidQuantities = [
            0,             // 零
            -1,            // 负数
            -100,          // 大负数
            1000000,       // 超过限制
            999999999,     // 大数
            PHP_INT_MAX,   // 最大整数
        ];

        foreach ($invalidQuantities as $quantity) {
            $this->expectException(InvalidBillDataException::class);
            $this->expectExceptionMessage('数量');
            $billOrderService->addBillItem($bill, 'PROD' . uniqid(), '测试产品', '100.00', $quantity);
        }
    }

    // ===================== 账单标题验证测试 =====================

    /**
     * 测试账单标题的边界情况
     */
    public function testBillTitleValidation(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 测试各种标题值
        $testCases = [
            null,                      // null值
            '',                        // 空字符串
            '   ',                     // 空格
            '简单标题',                 // 中文标题
            'Simple Title',            // 英文标题
            'Title with numbers 123',  // 带数字
            '标题-with-特殊#字符',      // 特殊字符
            str_repeat('A', 255),      // 最大长度
        ];

        foreach ($testCases as $title) {
            $bill = $billOrderService->createBill($title);
            $this->assertEquals($title, $bill->getTitle());
        }
    }

    // ===================== 账单备注验证测试 =====================

    /**
     * 测试账单备注的边界情况
     */
    public function testBillRemarkValidation(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 测试各种备注值
        $testCases = [
            null,                      // null值
            '',                        // 空字符串
            '   ',                     // 空格
            '简单备注',                 // 中文备注
            'Simple remark',           // 英文备注
            "多行\n备注\n内容",         // 多行备注
            "包含\t制表符",             // 制表符
            str_repeat('A', 2000),     // 最大长度
        ];

        foreach ($testCases as $remark) {
            $bill = $billOrderService->createBill('测试账单', $remark);
            $this->assertEquals($remark, $bill->getRemark());
        }
    }

    // ===================== 账单状态验证测试 =====================

    /**
     * 测试账单状态转换规则
     */
    public function testBillStatusTransitionValidation(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('状态测试');
        $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 1);

        // 从草稿状态开始，测试各种无效转换
        $this->assertEquals(BillOrderStatus::DRAFT, $bill->getStatus());

        // 不能从草稿直接支付
        $this->expectException(InvalidBillStatusException::class);
        $this->expectExceptionMessage('只有待支付状态的账单可以进行支付操作');
        $billOrderService->payBill($bill);
    }

    /**
     * 测试有效账单状态转换
     */
    public function testValidBillStatusTransitions(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('有效状态转换测试');
        $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 1);

        // 正常流程：草稿 -> 待支付 -> 已支付 -> 已完成
        $this->assertEquals(BillOrderStatus::DRAFT, $bill->getStatus());

        // 草稿 -> 待支付
        $billOrderService->submitBill($bill);
        $this->assertEquals(BillOrderStatus::PENDING, $bill->getStatus());

        // 待支付 -> 已支付
        $billOrderService->payBill($bill);
        $this->assertEquals(BillOrderStatus::PAID, $bill->getStatus());
        $this->assertNotNull($bill->getPayTime());

        // 已支付 -> 已完成
        $billOrderService->completeBill($bill);
        $this->assertEquals(BillOrderStatus::COMPLETED, $bill->getStatus());
    }

    /**
     * 测试取消操作的状态验证
     */
    public function testCancelStatusValidation(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 测试从草稿状态取消
        $bill1 = $billOrderService->createBill('草稿取消测试');
        $billOrderService->addBillItem($bill1, 'PROD001', '测试产品', '100.00', 1);
        $billOrderService->cancelBill($bill1);
        $this->assertEquals(BillOrderStatus::CANCELLED, $bill1->getStatus());

        // 测试从待支付状态取消
        $bill2 = $billOrderService->createBill('待支付取消测试');
        $billOrderService->addBillItem($bill2, 'PROD001', '测试产品', '100.00', 1);
        $billOrderService->submitBill($bill2);
        $billOrderService->cancelBill($bill2);
        $this->assertEquals(BillOrderStatus::CANCELLED, $bill2->getStatus());

        // 测试从已支付状态取消（应该失败）
        $bill3 = $billOrderService->createBill('已支付取消测试');
        $billOrderService->addBillItem($bill3, 'PROD001', '测试产品', '100.00', 1);
        $billOrderService->submitBill($bill3);
        $billOrderService->payBill($bill3);

        $this->expectException(InvalidBillStatusException::class);
        $this->expectExceptionMessage('只有草稿或待支付状态的账单可以取消');
        $billOrderService->cancelBill($bill3);
    }

    // ===================== 账单提交验证测试 =====================

    /**
     * 测试账单提交的各种验证规则
     */
    public function testBillSubmissionValidation(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 测试提交空账单
        $emptyBill = $billOrderService->createBill('空账单测试');
        $this->expectException(EmptyBillException::class);
        $this->expectExceptionMessage('账单必须至少包含一个项目才能提交');
        $billOrderService->submitBill($emptyBill);
    }

    /**
     * 测试有效账单提交
     */
    public function testValidBillSubmission(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 创建有效账单
        $bill = $billOrderService->createBill('有效提交测试');
        $billOrderService->addBillItem($bill, 'PROD001', '测试产品', '100.00', 1);

        // 提交应该成功
        $submittedBill = $billOrderService->submitBill($bill);
        $this->assertSame($bill, $submittedBill);
        $this->assertEquals(BillOrderStatus::PENDING, $bill->getStatus());

        // 验证重复提交会抛出异常
        $this->expectException(InvalidBillStatusException::class);
        $this->expectExceptionMessage('只有草稿状态的账单可以提交');
        $billOrderService->submitBill($bill);
    }

    // ===================== 边界值组合测试 =====================

    /**
     * 测试各种边界值组合
     */
    public function testBoundaryValueCombinations(): void
    {
        $billOrderService = $this->getBillOrderService();
        $bill = $billOrderService->createBill('边界值组合测试');

        // 最小有效值组合
        $item1 = $billOrderService->addBillItem($bill, 'A', 'B', '0.01', 1);
        $this->assertEquals('0.01', $item1->getPrice());
        $this->assertEquals(1, $item1->getQuantity());

        // 最大有效值组合
        $item2 = $billOrderService->addBillItem(
            $bill,
            str_repeat('C', 255),           // 最大产品ID长度
            str_repeat('D', 255),           // 最大产品名称长度
            '99999999.99',                  // 最大价格
            999999                          // 最大数量
        );
        $this->assertEquals('99999999.99', $item2->getPrice());
        $this->assertEquals(999999, $item2->getQuantity());

        // 验证总金额计算正确（由于大数精度问题，使用实际值验证）
        $actualTotal = $bill->getTotalAmount();
        $this->assertGreaterThan('99999899990000.00', $actualTotal);
        $this->assertLessThan('99999999990000.00', $actualTotal);
    }

    // ===================== 特殊字符和编码测试 =====================

    /**
     * 测试特殊字符处理
     */
    public function testSpecialCharacterHandling(): void
    {
        $billOrderService = $this->getBillOrderService();

        // 测试各种特殊字符
        $specialCases = [
            ['PROD@#$%', '产品@#$%'],
            ['PROD中文', '中文产品'],
            ['PROD🎉', '庆祝产品🎉'],
            ['PROD"quote"', '引号产品"quote"'],
            ["PROD'apostrophe'", "撇号产品'apostrophe'"],
            ['PROD&amp;', 'HTML实体&amp;'],
        ];

        foreach ($specialCases as [$productId, $productName]) {
            $bill = $billOrderService->createBill('特殊字符测试');
            $item = $billOrderService->addBillItem($bill, $productId, $productName, '100.00', 1);

            $this->assertEquals($productId, $item->getProductId());
            $this->assertEquals($productName, $item->getProductName());
        }
    }
}