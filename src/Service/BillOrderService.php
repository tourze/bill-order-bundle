<?php

namespace Tourze\Symfony\BillOrderBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Tourze\Symfony\BillOrderBundle\Entity\BillItem;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillItemStatus;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;
use Tourze\Symfony\BillOrderBundle\Repository\BillItemRepository;
use Tourze\Symfony\BillOrderBundle\Repository\BillOrderRepository;

/**
 * 账单服务类，处理账单相关业务逻辑
 */
class BillOrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BillOrderRepository $billOrderRepository,
        private readonly BillItemRepository $billItemRepository,
        private readonly LoggerInterface $logger,
    ) {
    }
    
    /**
     * 创建新账单
     *
     * @param string|null $title 账单标题
     * @param string|null $remark 备注
     * @return BillOrder 创建的账单对象
     */
    public function createBill(?string $title = null, ?string $remark = null): BillOrder
    {
        $bill = new BillOrder();
        $bill->setStatus(BillOrderStatus::DRAFT);
        $bill->setTitle($title);
        $bill->setRemark($remark);
        $bill->setBillNumber($this->generateBillNumber());
        
        $this->entityManager->persist($bill);
        $this->entityManager->flush();
        
        $this->logger->info('创建新账单', [
            'id' => $bill->getId(),
            'billNumber' => $bill->getBillNumber(),
        ]);
        
        return $bill;
    }
    
    /**
     * 生成账单编号
     */
    private function generateBillNumber(): string
    {
        $prefix = 'BILL';
        $date = date('Ymd');
        $random = substr(Uuid::v4()->toRfc4122(), 0, 8);
        
        return $prefix . $date . $random;
    }
    
    /**
     * 添加账单项目
     *
     * @param BillOrder $bill 账单对象
     * @param string $productId 产品ID
     * @param string $productName 产品名称
     * @param string $price 单价
     * @param int $quantity 数量
     * @param string|null $remark 备注
     * @return BillItem 创建的账单项目
     */
    public function addBillItem(
        BillOrder $bill,
        string $productId,
        string $productName,
        string $price,
        int $quantity = 1,
        ?string $remark = null
    ): BillItem {
        // 检查是否已存在该产品
        $existingItem = $this->billItemRepository->findOneByBillAndProduct(
            $bill->getId(),
            $productId
        );
        
        if ($existingItem !== null) {
            // 如果已存在，则更新数量
            $newQuantity = $existingItem->getQuantity() + $quantity;
            $existingItem->setQuantity($newQuantity);
            $item = $existingItem;
            
            $this->logger->info('更新账单项目数量', [
                'billId' => $bill->getId(),
                'itemId' => $item->getId(),
                'productId' => $productId,
                'quantity' => $newQuantity,
            ]);
        } else {
            // 创建新的账单项目
            $item = new BillItem();
            $item->setBill($bill);
            $item->setProductId($productId);
            $item->setProductName($productName);
            $item->setPrice($price);
            $item->setQuantity($quantity);
            $item->setStatus(BillItemStatus::PENDING);
            $item->setRemark($remark);
            
            $this->entityManager->persist($item);
            
            $this->logger->info('添加新账单项目', [
                'billId' => $bill->getId(),
                'productId' => $productId,
                'price' => $price,
                'quantity' => $quantity,
            ]);
        }
        
        $this->entityManager->flush();
        
        // 重新计算账单总金额
        $this->recalculateBillTotal($bill);
        
        return $item;
    }
    
    /**
     * 更新账单项目
     *
     * @param BillItem $item 账单项目
     * @param string|null $price 新单价
     * @param int|null $quantity 新数量
     * @param BillItemStatus|null $status 新状态
     * @return BillItem 更新后的账单项目
     */
    public function updateBillItem(
        BillItem $item,
        ?string $price = null,
        ?int $quantity = null,
        ?BillItemStatus $status = null
    ): BillItem {
        $changes = [];
        
        if ($price !== null) {
            $oldPrice = $item->getPrice();
            $item->setPrice($price);
            $changes['price'] = ['from' => $oldPrice, 'to' => $price];
        }
        
        if ($quantity !== null) {
            $oldQuantity = $item->getQuantity();
            $item->setQuantity($quantity);
            $changes['quantity'] = ['from' => $oldQuantity, 'to' => $quantity];
        }
        
        if ($status !== null) {
            $oldStatus = $item->getStatusEnum();
            $item->setStatus($status);
            $changes['status'] = ['from' => $oldStatus->value, 'to' => $status->value];
        }
        
        if (!empty($changes)) {
            $this->logger->info('更新账单项目', [
                'itemId' => $item->getId(),
                'billId' => $item->getBill()->getId(),
                'changes' => $changes,
            ]);
        }
        
        $this->entityManager->flush();
        
        // 重新计算账单总金额
        $this->recalculateBillTotal($item->getBill());
        
        return $item;
    }
    
    /**
     * 移除账单项目
     *
     * @param BillOrder $bill 账单对象
     * @param BillItem $item 要移除的账单项目
     * @return bool 是否成功移除
     */
    public function removeBillItem(BillOrder $bill, BillItem $item): bool
    {
        if ($item->getBill() !== $bill) {
            $this->logger->warning('尝试移除不属于该账单的项目', [
                'billId' => $bill->getId(),
                'itemId' => $item->getId(),
                'itemBillId' => $item->getBill() ? $item->getBill()->getId() : null,
            ]);
            return false;
        }
        
        $this->logger->info('移除账单项目', [
            'billId' => $bill->getId(),
            'itemId' => $item->getId(),
            'productId' => $item->getProductId(),
            'productName' => $item->getProductName(),
        ]);
        
        $bill->removeItem($item);
        $this->entityManager->remove($item);
        $this->entityManager->flush();
        
        // 重新计算账单总金额
        $this->recalculateBillTotal($bill);
        
        return true;
    }
    
    /**
     * 重新计算账单总金额
     *
     * @param BillOrder $bill 账单对象
     */
    public function recalculateBillTotal(BillOrder $bill): void
    {
        $oldTotal = $bill->getTotalAmount();
        $totalAmount = $this->billItemRepository->getTotalAmountByBillId($bill->getId());
        $bill->setTotalAmount($totalAmount);
        
        // 不在这里调用flush，让调用方负责调用flush
        
        if ($oldTotal !== $totalAmount) {
            $this->logger->info('更新账单总金额', [
                'billId' => $bill->getId(),
                'from' => $oldTotal,
                'to' => $totalAmount,
            ]);
        }
    }
    
    /**
     * 更新账单状态
     *
     * @param BillOrder $bill 账单对象
     * @param BillOrderStatus $status 新状态
     * @return BillOrder 更新后的账单
     */
    public function updateBillStatus(BillOrder $bill, BillOrderStatus $status): BillOrder
    {
        $oldStatus = $bill->getStatus();
        
        if ($oldStatus === $status) {
            return $bill;
        }
        
        $bill->setStatus($status);
        
        // 如果是变为已支付状态，记录支付时间
        if ($status === BillOrderStatus::PAID) {
            $bill->setPayTime(new \DateTime());
        }
        
        $this->entityManager->flush();
        
        $this->logger->info('更新账单状态', [
            'billId' => $bill->getId(),
            'billNumber' => $bill->getBillNumber(),
            'from' => $oldStatus->value,
            'to' => $status->value,
        ]);
        
        return $bill;
    }
    
    /**
     * 支付账单
     *
     * @param BillOrder $bill 账单对象
     * @return BillOrder 更新后的账单
     */
    public function payBill(BillOrder $bill): BillOrder
    {
        if ($bill->getStatus() !== BillOrderStatus::PENDING) {
            $message = '只有待支付状态的账单可以进行支付操作';
            $this->logger->warning($message, [
                'billId' => $bill->getId(),
                'currentStatus' => $bill->getStatus(),
            ]);
            throw new \InvalidArgumentException($message);
        }
        
        $this->logger->info('账单支付', [
            'billId' => $bill->getId(),
            'billNumber' => $bill->getBillNumber(),
            'amount' => $bill->getTotalAmount(),
        ]);
        
        return $this->updateBillStatus($bill, BillOrderStatus::PAID);
    }
    
    /**
     * 完成账单
     *
     * @param BillOrder $bill 账单对象
     * @return BillOrder 更新后的账单
     */
    public function completeBill(BillOrder $bill): BillOrder
    {
        if ($bill->getStatus() !== BillOrderStatus::PAID) {
            $message = '只有已支付状态的账单可以标记为完成';
            $this->logger->warning($message, [
                'billId' => $bill->getId(),
                'currentStatus' => $bill->getStatus(),
            ]);
            throw new \InvalidArgumentException($message);
        }
        
        // 将所有账单项目标记为已处理
        foreach ($bill->getItems() as $item) {
            $item->setStatus(BillItemStatus::PROCESSED);
        }
        
        $this->entityManager->flush();
        
        $this->logger->info('完成账单', [
            'billId' => $bill->getId(),
            'billNumber' => $bill->getBillNumber(),
            'itemCount' => $bill->getItems()->count(),
        ]);
        
        return $this->updateBillStatus($bill, BillOrderStatus::COMPLETED);
    }
    
    /**
     * 取消账单
     *
     * @param BillOrder $bill 账单对象
     * @param string|null $reason 取消原因
     * @return BillOrder 更新后的账单
     */
    public function cancelBill(BillOrder $bill, ?string $reason = null): BillOrder
    {
        $currentStatus = $bill->getStatus();
        
        // 只有草稿、待支付状态的账单可以取消
        if (!in_array($currentStatus, [BillOrderStatus::DRAFT, BillOrderStatus::PENDING])) {
            $message = '只有草稿或待支付状态的账单可以取消';
            $this->logger->warning($message, [
                'billId' => $bill->getId(),
                'currentStatus' => $bill->getStatus(),
            ]);
            throw new \InvalidArgumentException($message);
        }
        
        if ($reason !== null) {
            $oldRemark = $bill->getRemark() ?? '';
            $newRemark = $oldRemark . "\n取消原因: " . $reason;
            $bill->setRemark($newRemark);
        }
        
        // 将所有账单项目标记为已取消
        foreach ($bill->getItems() as $item) {
            $item->setStatus(BillItemStatus::CANCELLED);
        }
        
        $this->entityManager->flush();
        
        $this->logger->info('取消账单', [
            'billId' => $bill->getId(),
            'billNumber' => $bill->getBillNumber(),
            'reason' => $reason,
            'fromStatus' => $currentStatus->value,
        ]);
        
        return $this->updateBillStatus($bill, BillOrderStatus::CANCELLED);
    }
    
    /**
     * 提交账单（从草稿变为待付款）
     *
     * @param BillOrder $bill 账单对象
     * @return BillOrder 更新后的账单
     */
    public function submitBill(BillOrder $bill): BillOrder
    {
        if ($bill->getStatus() !== BillOrderStatus::DRAFT) {
            $message = '只有草稿状态的账单可以提交';
            $this->logger->warning($message, [
                'billId' => $bill->getId(),
                'currentStatus' => $bill->getStatus(),
            ]);
            throw new \InvalidArgumentException($message);
        }
        
        if ($bill->getItems()->isEmpty()) {
            $message = '账单必须至少包含一个项目才能提交';
            $this->logger->warning($message, [
                'billId' => $bill->getId(),
            ]);
            throw new \InvalidArgumentException($message);
        }
        
        $this->logger->info('提交账单', [
            'billId' => $bill->getId(),
            'billNumber' => $bill->getBillNumber(),
            'amount' => $bill->getTotalAmount(),
            'itemCount' => $bill->getItems()->count(),
        ]);
        
        return $this->updateBillStatus($bill, BillOrderStatus::PENDING);
    }
    
    /**
     * 获取按状态分组的账单统计
     *
     * @return array 统计数据
     */
    public function getBillStatistics(): array
    {
        $statistics = $this->billOrderRepository->getStatistics();
        
        $this->logger->debug('获取账单统计', [
            'statistics' => $statistics,
        ]);
        
        return $statistics;
    }
}
