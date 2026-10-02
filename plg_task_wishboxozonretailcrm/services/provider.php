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
use Joomla\Plugin\Task\WishboxOzonRetailCrm\Extension\WishboxOzonRetailCrm;
use Joomla\Registry\Registry;
use RetailCrm\Api\Client;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Service\OzonFbsService;
use WishboxOzonLibrary\Service\OzonProductService;
use WishboxOzonRetailCrmLibrary\Factory\RetailCrmClientFactory;
use WishboxOzonRetailCrmLibrary\Repositories\RetailCrmOfferRepository;
use WishboxOzonRetailCrmLibrary\Repositories\RetailCrmOrderRepository;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderImportService;
use WishboxOzonRetailCrmLibrary\Service\OzonProductSyncService;

defined('_JEXEC') or die;

return new class implements ServiceProviderInterface {
	private const string PARAMS_SERVICE = 'wishboxozonretailcrm.params';

	public function register(Container $container): void
	{
		require_once JPATH_SITE . '/vendor/autoload.php';

		if (!class_exists(OzonApiClient::class))
		{
			throw new RuntimeException('The WishBox Ozon library must be installed before enabling this plugin.');
		}

		$container->set(
			self::PARAMS_SERVICE,
			static function (): Registry {
				$config = (array) PluginHelper::getPlugin('task', 'wishboxozonretailcrm');

				return new Registry($config['params'] ?? '');
			}
		);

		$container->set(
			OzonApiClient::class,
			static fn (Container $container): OzonApiClient => new OzonApiClient(
				clientId: (string) $container->get(self::PARAMS_SERVICE)->get('ozon_client_id'),
				apiKey: (string) $container->get(self::PARAMS_SERVICE)->get('ozon_api_key'),
				apiUrl: (string) $container->get(self::PARAMS_SERVICE)->get(
					'ozon_api_url',
					'https://api-seller.ozon.ru'
				)
			)
		);

		$container->set(
			OzonProductService::class,
			static fn (Container $container): OzonProductService => new OzonProductService(
				$container->get(OzonApiClient::class)
			)
		);
		$container->set(
			OzonFbsService::class,
			static fn (Container $container): OzonFbsService => new OzonFbsService(
				$container->get(OzonApiClient::class)
			)
		);
		$container->set(
			Client::class,
			static function (Container $container): Client {
				$params = $container->get(self::PARAMS_SERVICE);
				$apiUrl = trim((string) $params->get('retailcrm_api_url'));
				$apiKey = trim((string) $params->get('retailcrm_api_key'));

				if ($apiUrl === '' || $apiKey === '')
				{
					throw new RuntimeException('RetailCRM API URL and API key must be configured.');
				}

				return RetailCrmClientFactory::createClient(
					$apiUrl,
					$apiKey,
					(string) $params->get('retailcrm_proxy_token')
				);
			}
		);
		$container->set(
			RetailCrmOfferRepository::class,
			static fn (Container $container): RetailCrmOfferRepository => new RetailCrmOfferRepository(
				$container->get(Client::class),
				$container->get(self::PARAMS_SERVICE)
			)
		);
		$container->set(
			RetailCrmOrderRepository::class,
			static fn (Container $container): RetailCrmOrderRepository => new RetailCrmOrderRepository(
				$container->get(Client::class)
			)
		);
		$container->set(
			OzonProductSyncService::class,
			static fn (Container $container): OzonProductSyncService => new OzonProductSyncService(
				$container->get(RetailCrmOfferRepository::class),
				$container->get(OzonProductService::class),
				$container->get(self::PARAMS_SERVICE)
			)
		);
		$container->set(
			OzonOrderImportService::class,
			static fn (Container $container): OzonOrderImportService => new OzonOrderImportService(
				$container->get(OzonFbsService::class),
				$container->get(RetailCrmOfferRepository::class),
				$container->get(RetailCrmOrderRepository::class),
				$container->get(self::PARAMS_SERVICE)
			)
		);
		$container->set(
			PluginInterface::class,
			static function (Container $container): PluginInterface {
				$plugin = new WishboxOzonRetailCrm(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('task', 'wishboxozonretailcrm')
				);

				$plugin->setApplication(Factory::getApplication());
				$plugin->setOzonProductSyncService($container->get(OzonProductSyncService::class));
				$plugin->setOzonOrderImportService($container->get(OzonOrderImportService::class));

				return $plugin;
			}
		);
	}
};
