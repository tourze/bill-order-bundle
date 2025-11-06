<?php

declare(strict_types=1);

namespace Tourze\Symfony\BillOrderBundle\Service;

use Brick\Math\BigDecimal;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;

/**
 * 金额计算工具类
 *
 * 提供统一的金额计算方法，确保计算的精确性和一致性
 */
class AmountCalculator
{
    /**
     * 计算账单项目的总金额
     *
     * @param BillItem[] $items 账单项目数组
     *
     * @return string 格式化为两位小数的金额字符串
     */
    public static function calculateTotalAmount(array $items): string
    {
        $total = BigDecimal::of('0');

        foreach ($items as $item) {
            $subtotal = $item->getSubtotal();
            if ('' !== $subtotal && is_numeric($subtotal)) {
                $total = $total->plus($subtotal);
            }
        }

        return $total->toScale(2)->__toString();
    }

    /**
     * 计算单个账单项目的小计金额
     *
     * @param string $price    单价
     * @param int    $quantity 数量
     *
     * @return string 格式化为两位小数的金额字符串
     */
    public static function calculateSubtotal(string $price, int $quantity): string
    {
        return BigDecimal::of($price)->multipliedBy($quantity)->toScale(2)->__toString();
    }

    /**
     * 验证金额格式是否正确
     *
     * @param string $amount 金额字符串
     *
     * @return bool 是否为有效的金额格式
     */
    public static function isValidAmount(string $amount): bool
    {
        return preg_match('/^\d+(\.\d{1,2})?$/', $amount) === 1;
    }

    /**
     * 验证金额是否非负
     *
     * @param string $amount 金额字符串
     *
     * @return bool 金额是否大于等于0
     */
    public static function isNonNegativeAmount(string $amount): bool
    {
        return self::isValidAmount($amount) && (float) $amount >= 0;
    }

    /**
     * 验证金额是否为正数
     *
     * @param string $amount 金额字符串
     *
     * @return bool 金额是否大于0
     */
    public static function isPositiveAmount(string $amount): bool
    {
        return self::isValidAmount($amount) && (float) $amount > 0;
    }

    /**
     * 格式化金额为标准格式
     *
     * @param string|int|float $amount 金额
     *
     * @return string 格式化为两位小数的金额字符串
     */
    public static function formatAmount(string|int|float $amount): string
    {
        return BigDecimal::of($amount)->toScale(2)->__toString();
    }
}