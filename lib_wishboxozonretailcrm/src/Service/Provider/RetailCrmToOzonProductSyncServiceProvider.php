<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service\Provider;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use RetailCrm\Api\Client;
use RuntimeException;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Service\OzonProductService;
use WishboxOzonLibrary\Service\Provider\OzonApiClientProvider;
use WishboxOzonLibrary\Service\Provider\OzonProductServiceProvider;
use WishboxOzonRetailCrmLibrary\Service\RetailCrmToOzonProductSyncService;
use WishboxRetailCrmLibrary\Configuration\RetailCrmConfiguration;
use WishboxRetailCrmLibrary\Service\Provider\RetailCrmProductServiceProvider;
use WishboxRetailCrmLibrary\Service\Provider\RetailCrmServiceProvider;
use WishboxRetailCrmLibrary\Service\RetailCrmProductService;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Registers RetailCRM to Ozon product synchronization dependencies.
 *
 * @since 1.0.0
 *
 * @noinspection PhpUnused
 */
final class RetailCrmToOzonProductSyncServiceProvider implements ServiceProviderInterface
{
	private const string PARAMS_SERVICE = 'wishboxozonretailcrm.params';

	/**
	 * Register the product synchronization service and its dependencies.
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
		$this->registerOzonServices($container);
		$this->registerRetailCrmServices($container);

		if (!$container->has(RetailCrmToOzonProductSyncService::class))
		{
			$container->share(
				RetailCrmToOzonProductSyncService::class,
				static fn (Container $container): RetailCrmToOzonProductSyncService =>
					new RetailCrmToOzonProductSyncService(
						$container->get(RetailCrmProductService::class),
						$container->get(OzonProductService::class),
						$container->get(self::PARAMS_SERVICE)
					)
			);
		}
	}

	/**
	 * Register services supplied by the Ozon library.
	 *
	 * @param   Container  $container  Dependency injection container.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function registerOzonServices(Container $container): void
	{
		if (!class_exists(OzonApiClientProvider::class)
			|| !class_exists(OzonProductServiceProvider::class))
		{
			throw new RuntimeException('The WishBox Ozon library must be installed before enabling this plugin.');
		}

		if (!$container->has(OzonApiClient::class))
		{
			$container->registerServiceProvider(new OzonApiClientProvider());
		}

		if (!$container->has(OzonProductService::class))
		{
			$container->registerServiceProvider(new OzonProductServiceProvider());
		}
	}

	/**
	 * Register services supplied by the RetailCRM library.
	 *
	 * @param   Container  $container  Dependency injection container.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function registerRetailCrmServices(Container $container): void
	{
		if (!class_exists(RetailCrmServiceProvider::class)
			|| !class_exists(RetailCrmProductServiceProvider::class))
		{
			throw new RuntimeException('The WishBox RetailCRM library must be installed before enabling this plugin.');
		}

		if (!$container->has(Client::class) || !$container->has(RetailCrmConfiguration::class))
		{
			$container->registerServiceProvider(new RetailCrmServiceProvider());
		}

		if (!$container->has(RetailCrmProductService::class))
		{
			$container->registerServiceProvider(new RetailCrmProductServiceProvider());
		}
	}
}
