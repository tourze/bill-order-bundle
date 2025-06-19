<?php

namespace Tourze\Symfony\BillOrderBundle\Entity;

use Brick\Math\BigDecimal;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Attribute\CreateIpColumn;
use Tourze\DoctrineIpBundle\Attribute\UpdateIpColumn;
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;
use Tourze\Symfony\BillOrderBundle\Repository\BillItemRepository;

#[ORM\Entity(repositoryClass: BillItemRepository::class)]
#[ORM\Table(name: 'order_bill_item', options: ['comment' => '账单明细'])]
#[ORM\UniqueConstraint(name: 'order_bill_item_idx_uniq', columns: ['bill_id', 'product_id'])]
class BillItem implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?BillOrder $bill = null;

    #[IndexColumn]
    #[ORM\Column(length: 50, enumType: BillItemStatus::class, options: ['comment' => '状态'])]
    private BillItemStatus $status = BillItemStatus::PENDING;
    
    #[IndexColumn]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => '产品ID'])]
    private ?string $productId = null;
    
    #[ORM\Column(length: 255, nullable: false, options: ['comment' => '产品名称'])]
    private ?string $productName = null;
    
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '单价', 'default' => 0])]
    private ?string $price = '0';
    
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '数量', 'default' => 1])]
    private ?int $quantity = 1;
    
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '小计金额', 'default' => 0])]
    private ?string $subtotal = '0';
    
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注'])]
    private ?string $remark = null;

    #[CreateIpColumn]
    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '创建时IP'])]
    private ?string $createdFromIp = null;

    #[UpdateIpColumn]
    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '更新时IP'])]
    private ?string $updatedFromIp = null;


    public function getId(): ?string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        return "{$this->getId()}";
    }

    public function getBill(): ?BillOrder
    {
        return $this->bill;
    }

    public function setBill(?BillOrder $bill): static
    {
        $this->bill = $bill;

        return $this;
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
    public function setStatus(BillItemStatus $status): static
    {
        $this->status = $status;

        return $this;
    }
    
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): static
    {
        $this->productId = $productId;

        return $this;
    }
    
    public function getProductName(): ?string
    {
        return $this->productName;
    }

    public function setProductName(string $productName): static
    {
        $this->productName = $productName;

        return $this;
    }
    
    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;
        $this->calculateSubtotal();

        return $this;
    }
    
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        $this->calculateSubtotal();

        return $this;
    }
    
    public function getSubtotal(): ?string
    {
        return $this->subtotal;
    }

    public function setSubtotal(string $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }
    
    /**
     * 计算小计金额
     */
    private function calculateSubtotal(): void
    {
        $this->subtotal = BigDecimal::of($this->price ?? 0)->multipliedBy($this->quantity ?? 0)->toScale(2);
    }
    
    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): static
    {
        $this->remark = $remark;

        return $this;
    }

    public function setCreatedFromIp(?string $createdFromIp): self
    {
        $this->createdFromIp = $createdFromIp;

        return $this;
    }

    public function getCreatedFromIp(): ?string
    {
        return $this->createdFromIp;
    }

    public function setUpdatedFromIp(?string $updatedFromIp): self
    {
        $this->updatedFromIp = $updatedFromIp;

        return $this;
    }

    public function getUpdatedFromIp(): ?string
    {
        return $this->updatedFromIp;
    }

}
