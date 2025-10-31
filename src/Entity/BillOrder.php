<?php

namespace Tourze\Symfony\BillOrderBundle\Entity;

use Brick\Math\BigDecimal;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineIpBundle\Traits\IpTraceableAware;
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
    use IpTraceableAware;

    /**
     * @var Collection<int, BillItem>
     */
    #[ORM\OneToMany(targetEntity: BillItem::class, mappedBy: 'bill', orphanRemoval: true)]
    private Collection $items;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 50, enumType: BillOrderStatus::class, options: ['comment' => '账单状态'])]
    #[Assert\Choice(callback: [BillOrderStatus::class, 'cases'])]
    private BillOrderStatus $status = BillOrderStatus::DRAFT;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['comment' => '账单总金额', 'default' => 0])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 13)]
    #[Assert\Regex(pattern: '/^\d+(\.\d{1,2})?$/', message: '总金额格式不正确')]
    #[Assert\Range(min: 0, max: 99999999.99)]
    private string $totalAmount = '0';

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '账单标题'])]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '账单编号'])]
    #[Assert\Length(max: 50)]
    private ?string $billNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '账单备注'])]
    #[Assert\Length(max: 2000)]
    private ?string $remark = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '付款时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $payTime = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (null === $this->getId()) {
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

    public function addItem(BillItem $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setBill($this);
        }
    }

    public function removeItem(BillItem $item): void
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getBill() === $this) {
                $item->setBill(null);
            }
        }
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
    public function setStatus(BillOrderStatus $status): void
    {
        $this->status = $status;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getBillNumber(): ?string
    {
        return $this->billNumber;
    }

    public function setBillNumber(?string $billNumber): void
    {
        $this->billNumber = $billNumber;
    }

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function getPayTime(): ?\DateTimeImmutable
    {
        return $this->payTime;
    }

    public function setPayTime(?\DateTimeImmutable $payTime): void
    {
        $this->payTime = $payTime;
    }

    /**
     * 计算账单总金额
     */
    public function calculateTotalAmount(): void
    {
        $total = '0';

        foreach ($this->items as $item) {
            $subtotal = $item->getSubtotal();
            if ('' !== $subtotal) {
                $total = BigDecimal::of($total)->plus($subtotal)->toScale(2);
            }
        }

        $this->setTotalAmount($total);
    }
}
