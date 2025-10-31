<?php

declare(strict_types=1);

namespace Tourze\Symfony\BillOrderBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;

class BillOrderBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
        ];
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if ($container->hasParameter('kernel.environment') && 'test' === $container->getParameter('kernel.environment')) {
            $container->prependExtensionConfig('twig', [
                'paths' => [
                    __DIR__ . '/../templates' => 'BillOrder',
                ],
            ]);
        }
    }
}
