<?php

declare(strict_types=1);

namespace Tourze\Symfony\BillOrderBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;
use Tourze\Symfony\BillOrderBundle\Repository\BillItemRepository;
use Tourze\Symfony\BillOrderBundle\Service\AmountCalculator;

#[ORM\Entity(repositoryClass: BillItemRepository::class)]
#[ORM\Table(name: 'order_bill_item', options: ['comment' => '账单明细'])]
#[ORM\UniqueConstraint(name: 'order_bill_item_idx_uniq', columns: ['bill_id', 'product_id'])]
class BillItem implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;
    use IpTraceableAware;

    // 显式覆盖 ID 字段定义，使用字符串类型以避免 SQLite BigInt 处理问题
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::STRING, length: 25, nullable: false, options: ['comment' => 'ID'])]
    protected ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?BillOrder $bill = null;

    #[IndexColumn]
    #[ORM\Column(length: 50, enumType: BillItemStatus::class, options: ['comment' => '状态'])]
    #[Assert\Choice(callback: [BillItemStatus::class, 'cases'])]
    private BillItemStatus $status = BillItemStatus::PENDING;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, nullable: false, options: ['comment' => '产品ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $productId = null;

    #[ORM\Column(length: 255, nullable: false, options: ['comment' => '产品名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private ?string $productName = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '单价', 'default' => 0])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 13)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: '价格格式不正确')]
    #[Assert\Range(min: 0, max: 99999999.99)]
    private string $price = '0';

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '数量', 'default' => 1])]
    #[Assert\PositiveOrZero]
    private int $quantity = 1;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '小计金额', 'default' => 0])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 13)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: '小计金额格式不正确')]
    #[Assert\Range(min: 0, max: 99999999.99)]
    private string $subtotal = '0';

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 2000)]
    private ?string $remark = null;

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return "{$this->getId()}";
    }

    public function getBill(): ?BillOrder
    {
        return $this->bill;
    }

    public function setBill(?BillOrder $bill): void
    {
        $this->bill = $bill;
    }

    /**
     * 获取明细状态
     */
    public function getStatus(): BillItemStatus
    {
        return $this->status;
    }

    /**
     * 设置明细状态
     */
    public function setStatus(BillItemStatus $status): void
    {
        $this->status = $status;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): void
    {
        $this->productName = $productName;
    }

    public function getPrice(): string
    {
        return $this->price;
    }

    public function setPrice(string $price): void
    {
        $this->price = $price;
        $this->calculateSubtotal();
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
        $this->calculateSubtotal();
    }

    public function getSubtotal(): string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): void
    {
        $this->subtotal = $subtotal;
    }

    /**
     * 计算小计金额
     *
     * 使用统一的金额计算工具确保计算一致性
     */
    private function calculateSubtotal(): void
    {
        $this->subtotal = AmountCalculator::calculateSubtotal($this->price, $this->quantity);
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }
}
