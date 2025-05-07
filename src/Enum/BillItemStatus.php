<?php

namespace Tourze\Symfony\BillOrderBundle\Enum;

/**
 * 账单明细状态枚举
 */
enum BillItemStatus: string
{
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
