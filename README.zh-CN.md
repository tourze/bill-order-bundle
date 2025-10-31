# BillOrderBundle 账单管理包

![PHP 版本](https://img.shields.io/badge/php-%5E8.1-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![构建状态](https://img.shields.io/badge/构建-通过-brightgreen)
![代码覆盖率](https://img.shields.io/badge/覆盖率-%3E95%25-brightgreen)

[English](README.md) | [中文](README.zh-CN.md)

用于管理账单订单的 Symfony 包，支持账单明细、状态跟踪和自动计算功能。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [配置](#配置)
- [使用方法](#使用方法)
  - [创建账单](#创建账单)
  - [管理账单状态](#管理账单状态)
  - [控制台命令](#控制台命令)
- [实体类](#实体类)
  - [BillOrder（账单）](#billorder账单)
  - [BillItem（账单明细）](#billitem账单明细)
- [异常类](#异常类)
- [事件](#事件)
- [测试](#测试)
- [依赖项](#依赖项)
  - [核心依赖](#核心依赖)
  - [包依赖](#包依赖)
- [高级用法](#高级用法)
  - [自定义账单编号生成](#自定义账单编号生成)
  - [复杂查询](#复杂查询)
  - [事件集成](#事件集成)
  - [性能优化](#性能优化)
- [许可证](#许可证)

## 功能特性

- 账单订单管理，支持状态流转（草稿 → 待付款 → 已付款 → 已完成）
- 账单明细管理，支持数量和价格计算
- 自动计算账单总金额
- 账单和明细的状态跟踪
- 内置控制台命令用于清理和统计
- IP 地址追踪（创建和更新时）
- 用户追踪（创建人/更新人）
- 完善的异常处理

## 安装

```bash
composer require tourze/bill-order-bundle
```

## 配置

在 `config/bundles.php` 中注册包：

```php
return [
    // ...
    Tourze\Symfony\BillOrderBundle\BillOrderBundle::class => ['all' => true],
];
```

## 使用方法

### 创建账单

```php
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;

// 注入服务
public function __construct(private BillOrderService $billOrderService) {}

// 创建新账单
$bill = $this->billOrderService->createBill(
    title: '月度订阅',
    remark: '2025年1月订阅费用'
);

// 添加账单明细
$this->billOrderService->addItemToBill(
    bill: $bill,
    productId: '12345',
    productName: '高级套餐',
    price: '99.99',
    quantity: 1,
    remark: '月度订阅费'
);

// 确认账单（状态变更为待付款）
$this->billOrderService->confirmBill($bill);
```

### 管理账单状态

```php
// 标记为已付款
$this->billOrderService->markAsPaid($bill);

// 完成账单
$this->billOrderService->completeBill($bill);

// 取消账单
$this->billOrderService->cancelBill($bill);
```

### 控制台命令

```bash
# 清理旧的草稿账单
php bin/console bill:cleanup --days=30

# 显示账单统计信息
php bin/console bill:statistics --format=table
```

## 实体类

### BillOrder（账单）

主账单实体，包含以下属性：
- `id`：雪花 ID（自动生成）
- `status`：账单状态（草稿、待付款、已付款、已完成、已取消）
- `totalAmount`：计算得出的总金额
- `title`：可选的账单标题
- `billNumber`：自动生成的账单编号
- `remark`：可选的备注
- `payTime`：付款时间戳
- `items`：账单明细集合

### BillItem（账单明细）

账单中的单个项目：
- `id`：雪花 ID（自动生成）
- `bill`：父账单引用
- `status`：明细状态（待确认、已确认、已取消）
- `productId`：产品标识
- `productName`：产品名称
- `price`：单价
- `quantity`：数量
- `subtotal`：自动计算的小计
- `remark`：可选的备注

## 异常类

- `BillOrderException`：所有账单相关错误的基础异常
- `EmptyBillException`：尝试确认空账单时抛出
- `InvalidBillStatusException`：尝试无效的状态转换时抛出

## 事件

该包目前不分发事件，但设计上易于扩展以支持事件分发功能。

## 测试

运行测试套件：

```bash
./vendor/bin/phpunit packages/bill-order-bundle/tests
```

## 依赖项

### 核心依赖
- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- Doctrine ORM 3.0 或更高版本
- Brick/Math 精确小数计算

### 包依赖
- `tourze/doctrine-indexed-bundle`: 数据库索引支持
- `tourze/doctrine-snowflake-bundle`: 雪花 ID 生成
- `tourze/doctrine-timestamp-bundle`: 自动时间戳管理
- `tourze/doctrine-user-bundle`: 用户跟踪功能
- `tourze/doctrine-ip-bundle`: IP 地址跟踪
- `tourze/enum-extra`: 增强型枚举功能
- `tourze/symfony-cron-job-bundle`: 定时任务支持

## 高级用法

### 自定义账单编号生成

```php
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;

// 重写账单编号生成
$bill = new BillOrder();
$bill->setBillNumber('BILL-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT));
```

### 复杂查询

```php
use Tourze\Symfony\BillOrderBundle\Repository\BillOrderRepository;

// 按日期范围和状态查找账单
$bills = $repository->createQueryBuilder('b')
    ->where('b.status = :status')
    ->andWhere('b.createTime BETWEEN :start AND :end')
    ->setParameter('status', BillOrderStatus::PAID)
    ->setParameter('start', $startDate)
    ->setParameter('end', $endDate)
    ->orderBy('b.createTime', 'DESC')
    ->getQuery()
    ->getResult();
```

### 事件集成

```php
// 监听账单状态变化（示例实现）
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BillStatusSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'bill.status.changed' => 'onBillStatusChanged',
        ];
    }
    
    public function onBillStatusChanged(BillStatusChangedEvent $event): void
    {
        // 账单状态变化时的自定义逻辑
        $bill = $event->getBill();
        $oldStatus = $event->getOldStatus();
        $newStatus = $event->getNewStatus();
        
        // 发送通知、更新外部系统等
    }
}
```

### 性能优化

```php
// 对大数据集使用批量操作
$batchSize = 100;
for ($i = 0; $i < count($bills); $i += $batchSize) {
    $batch = array_slice($bills, $i, $batchSize);
    foreach ($batch as $bill) {
        $this->billOrderService->processBill($bill);
    }
    $entityManager->flush();
    $entityManager->clear();
}
```

## 许可证

MIT 许可证。详见 LICENSE 文件。