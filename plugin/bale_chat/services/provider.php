<?php

declare(strict_types=1);

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\BaleChat\Extension\BaleChat;

defined('_JEXEC') || die;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            static function (Container $container): PluginInterface {
                $plugin = new BaleChat(
                    $container->get(DispatcherInterface::class),
                    (array) PluginHelper::getPlugin('system', 'bale_chat'),
                );

                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            },
        );
    }
};
