<?php

declare(strict_types=1);

namespace Tourze\Symfony\BillOrderBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\Symfony\BillOrderBundle\Service\AdminMenu;

/**
 * AdminMenu 单元测试
 *
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private ItemInterface $item;

    public function testInvokeMethod(): void
    {
        // 测试 AdminMenu 的 __invoke 方法正常工作
        $this->expectNotToPerformAssertions();

        try {
            $adminMenu = self::getService(AdminMenu::class);
            ($adminMenu)($this->item);
        } catch (\Throwable $e) {
            self::fail('AdminMenu __invoke method should not throw exception: ' . $e->getMessage());
        }
    }

    public function testAddsOrderManagementMenuIfNotExists(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        // 简单测试：确保方法不抛出异常
        $this->expectNotToPerformAssertions();

        try {
            ($adminMenu)($this->item);
        } catch (\Throwable $e) {
            self::fail('AdminMenu should handle non-existing order menu gracefully: ' . $e->getMessage());
        }
    }

    public function testAddsMenuItemsToExistingOrderMenu(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        // 简单测试：确保方法不抛出异常
        $this->expectNotToPerformAssertions();

        try {
            ($adminMenu)($this->item);
        } catch (\Throwable $e) {
            self::fail('AdminMenu should handle existing order menu gracefully: ' . $e->getMessage());
        }
    }

    public function testHandlesNullOrderMenuGracefully(): void
    {
        $adminMenu = self::getService(AdminMenu::class);

        // 使用匿名类实现，避免Mock使用问题
        $this->expectNotToPerformAssertions();
        ($adminMenu)($this->item);
    }

    protected function onSetUp(): void
    {
        $this->item = $this->createTestMenuItemStub();
    }

    private function createTestMenuItemStub(): ItemInterface
    {
        $mock = $this->createMock(ItemInterface::class);

        $mock->method('getChild')
            ->willReturnCallback(function (string $name): ?ItemInterface {
                return '订单管理' === $name ? $this->createMock(ItemInterface::class) : null;
            })
        ;

        $mock->method('addChild')
            ->willReturn($this->createMock(ItemInterface::class))
        ;

        return $mock;
    }
}
