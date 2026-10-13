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
use WishboxOzonLibrary\Service\OzonFbsService;
use WishboxOzonLibrary\Service\Provider\OzonApiClientProvider;
use WishboxOzonLibrary\Service\Provider\OzonFbsServiceProvider;
use WishboxOzonRetailCrmLibrary\Repositories\RetailCrmOfferRepository;
use WishboxOzonRetailCrmLibrary\Repositories\RetailCrmOrderRepository;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderImportService;
use WishboxRetailCrmLibrary\Configuration\RetailCrmConfiguration;
use WishboxRetailCrmLibrary\Service\Provider\RetailCrmServiceProvider;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Registers Ozon order import dependencies.
 *
 * @since 1.0.0
 *
 * @noinspection PhpUnused
 */
final class OzonOrderImportServiceProvider implements ServiceProviderInterface
{
	private const string PARAMS_SERVICE = 'wishboxozonretailcrm.params';

	/**
	 * Register the order import service and its dependencies.
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

		if (!$container->has(RetailCrmOfferRepository::class))
		{
			$container->share(
				RetailCrmOfferRepository::class,
				static fn (Container $container): RetailCrmOfferRepository => new RetailCrmOfferRepository(
					$container->get(Client::class),
					$container->get(self::PARAMS_SERVICE)
				)
			);
		}

		if (!$container->has(RetailCrmOrderRepository::class))
		{
			$container->share(
				RetailCrmOrderRepository::class,
				static fn (Container $container): RetailCrmOrderRepository => new RetailCrmOrderRepository(
					$container->get(Client::class)
				)
			);
		}

		if (!$container->has(OzonOrderImportService::class))
		{
			$container->share(
				OzonOrderImportService::class,
				static fn (Container $container): OzonOrderImportService => new OzonOrderImportService(
					$container->get(OzonFbsService::class),
					$container->get(RetailCrmOfferRepository::class),
					$container->get(RetailCrmOrderRepository::class),
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
			|| !class_exists(OzonFbsServiceProvider::class))
		{
			throw new RuntimeException('The WishBox Ozon library must be installed before enabling this plugin.');
		}

		if (!$container->has(OzonApiClient::class))
		{
			$container->registerServiceProvider(new OzonApiClientProvider());
		}

		if (!$container->has(OzonFbsService::class))
		{
			$container->registerServiceProvider(new OzonFbsServiceProvider());
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
		if (!class_exists(RetailCrmServiceProvider::class))
		{
			throw new RuntimeException('The WishBox RetailCRM library must be installed before enabling this plugin.');
		}

		if (!$container->has(Client::class) || !$container->has(RetailCrmConfiguration::class))
		{
			$container->registerServiceProvider(new RetailCrmServiceProvider());
		}
	}
}
