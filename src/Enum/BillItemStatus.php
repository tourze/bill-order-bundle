<?php

namespace Tourze\Symfony\BillOrderBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 账单明细状态枚举
 */
enum BillItemStatus: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PENDING = 'pending';     // 待处理
    case PROCESSED = 'processed'; // 已处理
    case REFUNDED = 'refunded';   // 已退款
    case CANCELLED = 'cancelled'; // 已取消
    
    /**
     * 获取状态对应的中文描述
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => '待处理',
            self::PROCESSED => '已处理',
            self::REFUNDED => '已退款',
            self::CANCELLED => '已取消',
        };
    }
}
