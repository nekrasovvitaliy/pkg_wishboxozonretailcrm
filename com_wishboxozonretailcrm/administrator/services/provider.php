<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\WishboxOzonRetailCrm\Administrator\Extension\WishboxOzonRetailCrmComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use WishboxOzonLibrary\Service\OzonFbsService;
use WishboxOzonLibrary\Service\Provider\OzonApiClientProvider;
use WishboxOzonLibrary\Service\Provider\OzonFbsServiceProvider;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderShipmentService;
use WishboxRetailCrmLibrary\Service\Provider\RetailCrmOrderServiceProvider;
use WishboxRetailCrmLibrary\Service\Provider\RetailCrmServiceProvider;
use WishboxRetailCrmLibrary\Service\RetailCrmOrderService;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

return new class implements ServiceProviderInterface
{
	/**
	 * Register the component services.
	 *
	 * @param   Container  $container  Dependency injection container.
	 *
	 * @return void
	 *
	 * @noinspection PhpUnused
	 *
	 * @since 1.0.0
	 */
	public function register(Container $container): void
	{
		require_once JPATH_SITE . '/vendor/autoload.php';

		if (!class_exists(OzonApiClientProvider::class)
			|| !class_exists(OzonFbsServiceProvider::class))
		{
			throw new RuntimeException(
				'The WishBox Ozon library must be installed before using this component.'
			);
		}

		if (!class_exists(RetailCrmServiceProvider::class)
			|| !class_exists(RetailCrmOrderServiceProvider::class))
		{
			throw new RuntimeException(
				'The WishBox RetailCRM library must be installed before using this component.'
			);
		}

		$container->registerServiceProvider(new OzonApiClientProvider());
		$container->registerServiceProvider(new OzonFbsServiceProvider());
		$container->registerServiceProvider(new RetailCrmServiceProvider());
		$container->registerServiceProvider(new RetailCrmOrderServiceProvider());
		$container->share(
			OzonOrderShipmentService::class,
			static fn (Container $container): OzonOrderShipmentService => new OzonOrderShipmentService(
				$container->get(RetailCrmOrderService::class),
				$container->get(OzonFbsService::class)
			)
		);

		$container->registerServiceProvider(
			new MVCFactory('\\Joomla\\Component\\WishboxOzonRetailCrm')
		);
		$container->registerServiceProvider(
			new ComponentDispatcherFactory('\\Joomla\\Component\\WishboxOzonRetailCrm')
		);

		$container->set(
			ComponentInterface::class,
			static function (Container $container): ComponentInterface {
				$component = new WishboxOzonRetailCrmComponent(
					$container->get(ComponentDispatcherFactoryInterface::class)
				);
				$component->setMVCFactory($container->get(MVCFactoryInterface::class));
				$component->setOzonOrderShipmentService(
					$container->get(OzonOrderShipmentService::class)
				);

				return $component;
			}
		);
	}
};
