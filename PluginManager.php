<?php

namespace Plugin\Rental;

use Eccube\Plugin\AbstractPluginManager;
use Psr\Container\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function install(array $meta, ContainerInterface $container) {}
    public function uninstall(array $meta, ContainerInterface $container) {}
    public function enable(array $meta, ContainerInterface $container) {}
    public function disable(array $meta, ContainerInterface $container) {}
    public function update(array $meta, ContainerInterface $container) {}
}
