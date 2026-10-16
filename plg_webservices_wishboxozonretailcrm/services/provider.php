<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Webservices\WishboxOzonRetailCrm\Extension\WishboxOzonRetailCrm;

defined('_JEXEC') or die;

/**
 * Provides the WishBox Ozon RetailCRM webservices plugin dependencies.
 *
 * @since 1.0.0
 */
return new class implements ServiceProviderInterface
{
	/**
	 * Register the plugin dependencies.
	 *
	 * @param Container $container Dependency injection container.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function register(Container $container): void
	{
		$container->set(
			PluginInterface::class,
			static function (Container $container): PluginInterface {
				$plugin = new WishboxOzonRetailCrm(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('webservices', 'wishboxozonretailcrm')
				);

				$plugin->setApplication(Factory::getApplication());

				return $plugin;
			}
		);
	}
};
