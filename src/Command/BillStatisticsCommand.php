<?php

namespace Tourze\Symfony\BillOrderBundle\Command;

use Brick\Math\BigDecimal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\Symfony\BillOrderBundle\Enum\BillOrderStatus;
use Tourze\Symfony\BillOrderBundle\Service\BillOrderService;
use Tourze\Symfony\CronJob\Attribute\AsCronTask;

#[AsCommand(
    name: self::NAME,
    description: '获取账单统计信息',
)]
#[AsCronTask(expression: '@daily')]
class BillStatisticsCommand extends Command
{
    public const NAME = 'bill:statistics';

    public function __construct(
        private readonly BillOrderService $billOrderService,
    )
    {
        parent::__construct(self::NAME);
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_OPTIONAL,
                '输出格式 (table, json)',
                'table'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('账单统计信息');

        $statistics = $this->billOrderService->getBillStatistics();
        $format = $input->getOption('format');

        if ($format === 'json') {
            $io->writeln(json_encode($statistics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $table = new Table($output);
            $table->setHeaders(['状态', '数量', '总金额']);

            $totalCount = 0;
            $totalAmount = '0';

            // 使用枚举值的label来获取状态文本
            foreach ($statistics as $status => $data) {
                $statusLabel = $this->getStatusLabel($status);

                $table->addRow([
                    $statusLabel,
                    $data['count'],
                    number_format((float)$data['totalAmount'], 2) . ' 元',
                ]);

                $totalCount += $data['count'];
                $totalAmount = BigDecimal::of($totalAmount)->plus($data['totalAmount'])->toScale(2);
            }

            $table->addRow(['<info>总计</info>', $totalCount, number_format((float)$totalAmount, 2) . ' 元']);
            $table->render();
        }

        return Command::SUCCESS;
    }

    /**
     * 获取状态对应的中文标签
     */
    private function getStatusLabel(string $status): string
    {
        try {
            $enum = BillOrderStatus::from($status);
            return $enum->getLabel();
        } catch (\ValueError $e) {
            return $status;
        }
    }
}
