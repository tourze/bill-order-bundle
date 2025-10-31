<?php

declare(strict_types=1);

namespace Tourze\Symfony\BillOrderBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;

/**
 * 账单管理菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('订单管理')) {
            $item->addChild('订单管理');
        }

        $orderMenu = $item->getChild('订单管理');
        if (null === $orderMenu) {
            return;
        }

        // 账单管理菜单
        $orderMenu->addChild('账单管理')
            ->setUri($this->linkGenerator->getCurdListPage(BillOrder::class))
            ->setAttribute('icon', 'fas fa-file-invoice-dollar')
        ;

        // 账单明细管理菜单
        $orderMenu->addChild('账单明细管理')
            ->setUri($this->linkGenerator->getCurdListPage(BillItem::class))
            ->setAttribute('icon', 'fas fa-list-ul')
        ;
    }
}
