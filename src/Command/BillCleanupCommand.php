<?php

namespace Tourze\Symfony\BillOrderBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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
    name: 'bill:cleanup',
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('清理过期未支付账单');

        $days = (int)$input->getOption('days');
        $dryRun = $input->getOption('dry-run');

        if ($days < 1) {
            $io->error('天数必须大于0');
            return Command::FAILURE;
        }

        $date = new \DateTime();
        $date->modify("-{$days} days");

        $qb = $this->billOrderRepository->createQueryBuilder('o')
            ->where('o.status = :status')
            ->andWhere('o.createTime < :date')
            ->setParameter('status', BillOrderStatus::PENDING->value)
            ->setParameter('date', $date);

        $expiredBills = $qb->getQuery()->getResult();
        $count = count($expiredBills);

        if ($count === 0) {
            $io->success('没有找到过期的待支付账单');
            return Command::SUCCESS;
        }

        $io->note(sprintf('找到 %d 个超过 %d 天未支付的账单', $count, $days));

        if ($dryRun) {
            $io->table(
                ['ID', '账单编号', '创建时间', '金额'],
                array_map(function (BillOrder $bill) {
                    return [
                        $bill->getId(),
                        $bill->getBillNumber(),
                        $bill->getCreateTime() ? $bill->getCreateTime()->format('Y-m-d H:i:s') : '-',
                        $bill->getTotalAmount(),
                    ];
                }, $expiredBills)
            );
            $io->warning('这是演示模式，没有实际执行取消操作');
            return Command::SUCCESS;
        }

        $progressBar = $io->createProgressBar($count);
        $progressBar->start();

        $cancelReason = "系统自动取消：{$days}天内未完成支付";
        $cancelledCount = 0;
        $errors = [];

        foreach ($expiredBills as $bill) {
            try {
                $this->billOrderService->cancelBill($bill, $cancelReason);
                $cancelledCount++;
            } catch (\Exception $e) {
                $errors[] = sprintf('账单 %s (%s) 取消失败: %s', $bill->getId(), $bill->getBillNumber(), $e->getMessage());
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        if ($cancelledCount > 0) {
            $io->success(sprintf('成功取消 %d 个过期账单', $cancelledCount));
        }

        if (!empty($errors)) {
            $io->section('处理过程中的错误');
            foreach ($errors as $error) {
                $io->error($error);
            }
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
