<?php

namespace Tourze\Symfony\BillOrderBundle\Entity;

use Brick\Math\BigDecimal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Attribute\CreateIpColumn;
use Tourze\DoctrineIpBundle\Attribute\UpdateIpColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;
use Tourze\Symfony\BillOrderBundle\Repository\BillOrderRepository;

#[ORM\Entity(repositoryClass: BillOrderRepository::class)]
#[ORM\Table(name: 'order_bill_order', options: ['comment' => '账单表'])]
class BillOrder implements \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[ORM\OneToMany(targetEntity: BillItem::class, mappedBy: 'bill', orphanRemoval: true)]
    private Collection $items;

    #[IndexColumn]
    #[ORM\Column(length: 50, options: ['comment' => '账单状态'])]
    private BillOrderStatus $status = BillOrderStatus::DRAFT;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '账单总金额', 'default' => 0])]
    private string $totalAmount = '0';

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '账单标题'])]
    private ?string $title = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '账单编号'])]
    private ?string $billNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '账单备注'])]
    private ?string $remark = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '付款时间'])]
    private ?\DateTimeImmutable $payTime = null;


    #[CreateIpColumn]
    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '创建时IP'])]
    private ?string $createdFromIp = null;

    #[UpdateIpColumn]
    #[ORM\Column(length: 128, nullable: true, options: ['comment' => '更新时IP'])]
    private ?string $updatedFromIp = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }


    public function __toString(): string
    {
        if ($this->getId() === null) {
            return '';
        }

        return "{$this->getId()}";
    }

    /**
     * @return Collection<int, BillItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(BillItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setBill($this);
        }

        return $this;
    }

    public function removeItem(BillItem $item): static
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getBill() === $this) {
                $item->setBill(null);
            }
        }

        return $this;
    }

    /**
     * 获取账单状态
     */
    public function getStatus(): BillOrderStatus
    {
        return $this->status;
    }

    /**
     * 设置账单状态
     */
    public function setStatus(BillOrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBillNumber(): ?string
    {
        return $this->billNumber;
    }

    public function setBillNumber(?string $billNumber): static
    {
        $this->billNumber = $billNumber;

        return $this;
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

    public function getPayTime(): ?\DateTimeImmutable
    {
        return $this->payTime;
    }

    public function setPayTime(?\DateTimeImmutable $payTime): static
    {
        $this->payTime = $payTime;

        return $this;
    }

    public function getCreatedFromIp(): ?string
    {
        return $this->createdFromIp;
    }

    public function setCreatedFromIp(?string $createdFromIp): static
    {
        $this->createdFromIp = $createdFromIp;

        return $this;
    }

    public function getUpdatedFromIp(): ?string
    {
        return $this->updatedFromIp;
    }

    public function setUpdatedFromIp(?string $updatedFromIp): static
    {
        $this->updatedFromIp = $updatedFromIp;

        return $this;
    }

    /**
     * 计算账单总金额
     */
    public function calculateTotalAmount(): void
    {
        $total = '0';

        foreach ($this->items as $item) {
            $subtotal = $item->getSubtotal();
            if ($subtotal) {
                $total = BigDecimal::of($total)->plus($subtotal)->toScale(2);
            }
        }

        $this->setTotalAmount($total);
    }
}
