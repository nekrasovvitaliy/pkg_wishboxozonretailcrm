<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\Task\WishboxOzonRetailCrm\Extension\WishboxOzonRetailCrm;
use Joomla\Registry\Registry;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderImportService;
use WishboxOzonRetailCrmLibrary\Service\Provider\OzonOrderImportServiceProvider;
use WishboxOzonRetailCrmLibrary\Service\Provider\RetailCrmToOzonProductSyncServiceProvider;
use WishboxOzonRetailCrmLibrary\Service\RetailCrmToOzonProductSyncService;

defined('_JEXEC') or die;

/**
 * Provides the WishBox Ozon RetailCRM task plugin dependencies.
 *
 * @since 1.0.0
 */
return new class implements ServiceProviderInterface
{
	private const string PARAMS_SERVICE = 'wishboxozonretailcrm.params';

	/**
	 * Register the task plugin dependencies.
	 *
	 * @param   Container  $container  Dependency injection container.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function register(Container $container): void
	{
		if (!class_exists(RetailCrmToOzonProductSyncServiceProvider::class)
			|| !class_exists(OzonOrderImportServiceProvider::class))
		{
			throw new RuntimeException(
				'The WishBox Ozon RetailCRM library must be installed before enabling this plugin.'
			);
		}

		$container->set(
			self::PARAMS_SERVICE,
			static function (): Registry {
				$config = (array) PluginHelper::getPlugin('task', 'wishboxozonretailcrm');
				$params = new Registry($config['params'] ?? '');
				$params->merge(ComponentHelper::getParams('com_wishboxozon'));

				return $params;
			}
		);

		$container->registerServiceProvider(new RetailCrmToOzonProductSyncServiceProvider());
		$container->registerServiceProvider(new OzonOrderImportServiceProvider());

		$container->set(
			PluginInterface::class,
			static function (Container $container): PluginInterface {
				$plugin = new WishboxOzonRetailCrm(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('task', 'wishboxozonretailcrm')
				);

				$plugin->setApplication(Factory::getApplication());
				$plugin->setRetailCrmToOzonProductSyncService(
					$container->get(RetailCrmToOzonProductSyncService::class)
				);
				$plugin->setOzonOrderImportService($container->get(OzonOrderImportService::class));

				return $plugin;
			}
		);
	}
};
