<?php

namespace Plugin\Rental;

use Eccube\Plugin\AbstractPluginManager;
use Psr\Container\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container)
    {
        // 基本的な有効化処理
    }

    public function disable(array $meta, ContainerInterface $container)
    {
        // 無効化処理
    }
}
