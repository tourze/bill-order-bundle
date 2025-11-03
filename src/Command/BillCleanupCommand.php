<?php

namespace Tourze\Symfony\BillOrderBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\Symfony\BillOrderBundle\Entity\BillOrder;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;
use Tourze\Symfony\BillOrderBundle\Repository\BillOrderRepository;
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;

#[AsCommand(
    name: self::NAME,
    description: '清理处理过期未支付的账单',
)]
#[AsCronTask(expression: '0 1 * * *')] // 每天凌晨1点执行
class BillCleanupCommand extends Command
{
    public const NAME = 'bill:cleanup';

    public function __construct(
        private readonly BillOrderRepository $billOrderRepository,
        private readonly BillOrderService $billOrderService,
    ) {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                '超过几天未支付视为过期',
                7
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                '不实际执行，仅显示将被处理的账单'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('清理过期未支付账单');

        $daysOption = $input->getOption('days');
        \assert(null === $daysOption || \is_numeric($daysOption));
        $days = null !== $daysOption ? (int) $daysOption : 30;
        $dryRun = $input->getOption('dry-run');

        if ($days < 1) {
            $io->error('天数必须大于0');

            return Command::FAILURE;
        }

        $expiredBills = $this->findExpiredBills($days);
        $count = count($expiredBills);

        if (0 === $count) {
            $io->success('没有找到过期的待支付账单');

            return Command::SUCCESS;
        }

        $io->note(sprintf('找到 %d 个超过 %d 天未支付的账单', $count, $days));

        if (true === $dryRun) {
            return $this->displayDryRunResults($io, $expiredBills);
        }

        return $this->processBillCancellation($io, $expiredBills, $days);
    }

    /**
     * @return array<BillOrder>
     */
    private function findExpiredBills(int $days): array
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        /** @var array<BillOrder> $result */
        $result = $this->billOrderRepository->createQueryBuilder('o')
            ->where('o.status = :status')
            ->andWhere('o.createTime < :date')
            ->setParameter('status', BillOrderStatus::PENDING->value)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    /**
     * @param BillOrder[] $expiredBills
     */
    private function displayDryRunResults(SymfonyStyle $io, array $expiredBills): int
    {
        $io->table(
            ['ID', '账单编号', '创建时间', '金额'],
            array_map(function (BillOrder $bill) {
                return [
                    $bill->getId(),
                    $bill->getBillNumber(),
                    null !== $bill->getCreateTime() ? $bill->getCreateTime()->format('Y-m-d H:i:s') : '-',
                    $bill->getTotalAmount(),
                ];
            }, $expiredBills)
        );
        $io->warning('这是演示模式，没有实际执行取消操作');

        return Command::SUCCESS;
    }

    /**
     * @param BillOrder[] $expiredBills
     */
    private function processBillCancellation(SymfonyStyle $io, array $expiredBills, int $days): int
    {
        $count = count($expiredBills);
        $progressBar = $io->createProgressBar($count);
        $progressBar->start();

        $cancelReason = "系统自动取消：{$days}天内未完成支付";
        $result = $this->cancelBills($expiredBills, $cancelReason, $progressBar);

        $progressBar->finish();
        $io->newLine(2);

        return $this->displayResults($io, $result);
    }

    /**
     * @param BillOrder[] $expiredBills
     * @return array{cancelled: int, errors: string[]}
     */
    private function cancelBills(array $expiredBills, string $cancelReason, ProgressBar $progressBar): array
    {
        $cancelledCount = 0;
        $errors = [];

        foreach ($expiredBills as $bill) {
            try {
                $this->billOrderService->cancelBill($bill, $cancelReason);
                ++$cancelledCount;
            } catch (\Throwable $e) {
                $errors[] = sprintf('账单 %s (%s) 取消失败: %s', $bill->getId(), $bill->getBillNumber(), $e->getMessage());
            }
            $progressBar->advance();
        }

        return ['cancelled' => $cancelledCount, 'errors' => $errors];
    }

    /**
     * @param array{cancelled: int, errors: string[]} $result
     */
    private function displayResults(SymfonyStyle $io, array $result): int
    {
        if ($result['cancelled'] > 0) {
            $io->success(sprintf('成功取消 %d 个过期账单', $result['cancelled']));
        }

        if ([] !== $result['errors']) {
            $io->section('处理过程中的错误');
            foreach ($result['errors'] as $error) {
                $io->error($error);
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
