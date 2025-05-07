<?php

namespace Tourze\Symfony\BillOrderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: '账单模块')]
class BillOrderBundle extends Bundle
{
}
