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
use Joomla\Plugin\Webservices\WishboxOzonRetailCrm\Extension\WishboxOzonRetailCrm;
use Joomla\Registry\Registry;
use RetailCrm\Api\Client;
use WishboxOzonLibrary\Service\OzonFbsService;
use WishboxOzonLibrary\Service\Provider\OzonApiClientProvider;
use WishboxOzonLibrary\Service\Provider\OzonFbsServiceProvider;
use WishboxOzonRetailCrmLibrary\Factory\RetailCrmClientFactory;
use WishboxOzonRetailCrmLibrary\Repositories\RetailCrmOfferRepository;
use WishboxOzonRetailCrmLibrary\Repositories\RetailCrmOrderRepository;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderImportService;

defined('_JEXEC') or die;

/**
 * Provides the WishBox Ozon RetailCRM webservices plugin dependencies.
 *
 * @since 1.0.0
 */
return new class implements ServiceProviderInterface
{
	private const string PLUGIN_PARAMS_SERVICE = 'wishboxozonretailcrm.webservices.params';
	private const string OZON_PARAMS_SERVICE = 'wishboxozon.component.params';
	private const string RETAIL_CRM_PARAMS_SERVICE = 'wishboxretailcrm.system.params';
	private const string RUNTIME_PARAMS_SERVICE = 'wishboxozonretailcrm.runtime.params';

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
		require_once JPATH_SITE . '/vendor/autoload.php';

		if (!class_exists(OzonApiClientProvider::class)
			|| !class_exists(OzonFbsServiceProvider::class))
		{
			throw new RuntimeException('The WishBox Ozon library must be installed before enabling this plugin.');
		}

		$container->registerServiceProvider(new OzonApiClientProvider());
		$container->registerServiceProvider(new OzonFbsServiceProvider());

		$container->set(
			self::PLUGIN_PARAMS_SERVICE,
			fn (): Registry => $this->getPluginParams('webservices', 'wishboxozonretailcrm')
		);
		$container->set(
			self::OZON_PARAMS_SERVICE,
			static fn(): Registry => ComponentHelper::getParams('com_wishboxozon')
		);
		$container->set(
			self::RETAIL_CRM_PARAMS_SERVICE,
			fn (): Registry => $this->getPluginParams('system', 'wishboxretailcrm')
		);
		$container->set(
			self::RUNTIME_PARAMS_SERVICE,
			static function (Container $container): Registry {
				$params = new Registry(
					$container->get(self::PLUGIN_PARAMS_SERVICE)->toArray()
				);
				$params->merge($container->get(self::OZON_PARAMS_SERVICE));
				$params->merge($container->get(self::RETAIL_CRM_PARAMS_SERVICE));

				return $params;
			}
		);
		$container->set(
			Client::class,
			static function (Container $container): Client {
				$retailCrmParams = $container->get(self::RETAIL_CRM_PARAMS_SERVICE);
				$apiUrl = trim((string) $retailCrmParams->get('retailcrm_api_url'));
				$apiKey = trim((string) $retailCrmParams->get('retailcrm_api_key'));

				if ($apiUrl === '' || $apiKey === '')
				{
					throw new RuntimeException('RetailCRM API URL and API key must be configured.');
				}

				return RetailCrmClientFactory::createClient(
					$apiUrl,
					$apiKey,
					(string) $retailCrmParams->get('retailcrm_proxy_token')
				);
			}
		);
		$container->set(
			RetailCrmOfferRepository::class,
			static fn (Container $container): RetailCrmOfferRepository => new RetailCrmOfferRepository(
				$container->get(Client::class),
				$container->get(self::RUNTIME_PARAMS_SERVICE)
			)
		);
		$container->set(
			RetailCrmOrderRepository::class,
			static fn (Container $container): RetailCrmOrderRepository => new RetailCrmOrderRepository(
				$container->get(Client::class)
			)
		);
		$container->set(
			OzonOrderImportService::class,
			static fn (Container $container): OzonOrderImportService => new OzonOrderImportService(
				$container->get(OzonFbsService::class),
				$container->get(RetailCrmOfferRepository::class),
				$container->get(RetailCrmOrderRepository::class),
				$container->get(self::RUNTIME_PARAMS_SERVICE)
			)
		);
		$container->set(
			PluginInterface::class,
			static function (Container $container): PluginInterface {
				$plugin = new WishboxOzonRetailCrm(
					$container->get(DispatcherInterface::class),
					(array) PluginHelper::getPlugin('webservices', 'wishboxozonretailcrm')
				);

				$plugin->setApplication(Factory::getApplication());
				$plugin->setOzonOrderImportService($container->get(OzonOrderImportService::class));

				return $plugin;
			}
		);
	}

	/**
	 * Load enabled Joomla plugin parameters.
	 *
	 * @param string $group   Joomla plugin group.
	 * @param string $element Joomla plugin element.
	 *
	 * @return Registry Plugin parameters.
	 *
	 * @since 1.0.0
	 */
	private function getPluginParams(string $group, string $element): Registry
	{
		$plugin = PluginHelper::getPlugin($group, $element);

		if (!is_object($plugin))
		{
			throw new RuntimeException(
				sprintf('The %s/%s plugin must be enabled.', $group, $element)
			);
		}

		return new Registry($plugin->params ?? '');
	}
};
