<?php

namespace Tourze\Symfony\BillOrderBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class BillOrderExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
