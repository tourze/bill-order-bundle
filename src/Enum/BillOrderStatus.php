<?php

namespace Tourze\Symfony\BillOrderBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 账单状态枚举
 */
enum BillOrderStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case DRAFT = 'draft';       // 草稿
    case PENDING = 'pending';   // 待付款
    case PAID = 'paid';         // 已付款
    case COMPLETED = 'completed'; // 已完成
    case CANCELLED = 'cancelled'; // 已取消
    
    /**
     * 获取状态对应的中文描述
     */
    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => '草稿',
            self::PENDING => '待付款',
            self::PAID => '已付款',
            self::COMPLETED => '已完成',
            self::CANCELLED => '已取消',
        };
    }
}
