<?php

declare(strict_types=1);

namespace BillOrderBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\Symfony\BillOrderBundle\BillOrderBundle;

/**
 * @internal
 */
#[CoversClass(BillOrderBundle::class)]
#[RunTestsInSeparateProcesses]
final class BillOrderBundleTest extends AbstractBundleTestCase
{
}
