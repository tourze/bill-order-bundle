# BillOrderBundle

![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Build Status](https://img.shields.io/badge/build-passing-brightgreen)
![Code Coverage](https://img.shields.io/badge/coverage-%3E95%25-brightgreen)

[English](README.md) | [中文](README.zh-CN.md)

A Symfony bundle for managing bill orders with items, status tracking, and automated calculations.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Creating a Bill](#creating-a-bill)
  - [Managing Bill Status](#managing-bill-status)
  - [Console Commands](#console-commands)
- [Entities](#entities)
  - [BillOrder](#billorder)
  - [BillItem](#billitem)
- [Exceptions](#exceptions)
- [Events](#events)
- [Testing](#testing)
- [Dependencies](#dependencies)
  - [Core Dependencies](#core-dependencies)
  - [Bundle Dependencies](#bundle-dependencies)
- [Advanced Usage](#advanced-usage)
  - [Custom Bill Number Generation](#custom-bill-number-generation)
  - [Complex Queries](#complex-queries)
  - [Event Integration](#event-integration)
  - [Performance Optimization](#performance-optimization)
- [License](#license)

## Features

- Bill order management with status workflow (Draft → Pending → Paid → Completed)
- Bill items with quantity and price calculations
- Automatic total amount calculation
- Status tracking for both bills and items
- Built-in console commands for cleanup and statistics
- IP tracking for creation and updates
- User tracking (created by/updated by)
- Comprehensive exception handling

## Installation

```bash
composer require tourze/bill-order-bundle
```

## Configuration

Register the bundle in your `config/bundles.php`:

```php
return [
    // ...
    Tourze\Symfony\BillOrderBundle\BillOrderBundle::class => ['all' => true],
];
```

## Usage

### Creating a Bill

```php
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;

// Inject the service
public function __construct(private BillOrderService $billOrderService) {}

// Create a new bill
$bill = $this->billOrderService->createBill(
    title: 'Monthly Subscription',
    remark: 'January 2025 subscription'
);

// Add items to the bill
$this->billOrderService->addItemToBill(
    bill: $bill,
    productId: '12345',
    productName: 'Premium Plan',
    price: '99.99',
    quantity: 1,
    remark: 'Monthly subscription fee'
);

// Confirm the bill (change status to pending)
$this->billOrderService->confirmBill($bill);
```

### Managing Bill Status

```php
// Mark bill as paid
$this->billOrderService->markAsPaid($bill);

// Complete the bill
$this->billOrderService->completeBill($bill);

// Cancel the bill
$this->billOrderService->cancelBill($bill);
```

### Console Commands

```bash
# Clean up old draft bills
php bin/console bill:cleanup --days=30

# Show bill statistics
php bin/console bill:statistics --format=table
```

## Entities

### BillOrder

The main bill entity with the following properties:
- `id`: Snowflake ID (auto-generated)
- `status`: Bill status (draft, pending, paid, completed, cancelled)
- `totalAmount`: Calculated total amount
- `title`: Optional bill title
- `billNumber`: Auto-generated bill number
- `remark`: Optional remark
- `payTime`: Payment timestamp
- `items`: Collection of bill items

### BillItem

Individual items within a bill:
- `id`: Snowflake ID (auto-generated)
- `bill`: Parent bill reference
- `status`: Item status (pending, confirmed, cancelled)
- `productId`: Product identifier
- `productName`: Product name
- `price`: Unit price
- `quantity`: Item quantity
- `subtotal`: Auto-calculated subtotal
- `remark`: Optional remark

## Exceptions

- `BillOrderException`: Base exception for all bill-related errors
- `EmptyBillException`: Thrown when attempting to confirm an empty bill
- `InvalidBillStatusException`: Thrown when invalid status transitions are attempted

## Events

The bundle does not currently dispatch events but is designed to be easily extended with event dispatching capabilities.

## Testing

Run the test suite:

```bash
./vendor/bin/phpunit packages/bill-order-bundle/tests
```

## Dependencies

### Core Dependencies
- PHP 8.1 or higher
- Symfony 6.4 or higher
- Doctrine ORM 3.0 or higher
- Brick/Math for precise decimal calculations

### Bundle Dependencies
- `tourze/doctrine-indexed-bundle`: Database indexing support
- `tourze/doctrine-snowflake-bundle`: Snowflake ID generation
- `tourze/doctrine-timestamp-bundle`: Automatic timestamp management
- `tourze/doctrine-user-bundle`: User tracking capabilities
- `tourze/doctrine-ip-bundle`: IP address tracking
- `tourze/enum-extra`: Enhanced enum functionality
- `tourze/symfony-cron-job-bundle`: Scheduled task support

## Advanced Usage

### Custom Bill Number Generation

```php
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;

// Override the bill number generation
$bill = new BillOrder();
$bill->setBillNumber('BILL-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT));
```

### Complex Queries

```php
use Tourze\Symfony\BillOrderBundle\Repository\BillOrderRepository;

// Find bills by date range and status
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

### Event Integration

```php
// Listen for bill status changes (example implementation)
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
        // Custom logic when bill status changes
        $bill = $event->getBill();
        $oldStatus = $event->getOldStatus();
        $newStatus = $event->getNewStatus();
        
        // Send notifications, update external systems, etc.
    }
}
```

### Performance Optimization

```php
// Use bulk operations for large datasets
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

## License

MIT License. See LICENSE file for details.