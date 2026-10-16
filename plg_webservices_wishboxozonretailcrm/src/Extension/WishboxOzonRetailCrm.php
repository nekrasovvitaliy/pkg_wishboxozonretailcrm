<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Plugin\Webservices\WishboxOzonRetailCrm\Extension;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Registers Ozon RetailCRM API routes.
 *
 * @since 1.0.0
 */
final class WishboxOzonRetailCrm extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Return the subscribed Joomla events.
	 *
	 * @return array<string, string>
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onBeforeApiRoute' => 'onBeforeApiRoute',
		];
	}

	/**
	 * Register the Ozon RetailCRM API routes.
	 *
	 * @param   BeforeApiRouteEvent  $event  API routing event.
	 *
	 * @return void
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
	{
		$printRoute = new Route(
			['POST'],
			'v1/wishboxozonretailcrm/print',
			'wishboxozon.print',
			[],
			[
				'component' => 'com_wishboxozonretailcrm',
				'public'    => true,
			]
		);
		$shipRoute = new Route(
			['PATCH'],
			'v1/wishboxozonretailcrm/orders/:ozonorder_id/ship',
			'orders.ship',
			['ozonorder_id' => '(\w+)'],
			['component' => 'com_wishboxozonretailcrm']
		);

		$router = $event->getRouter();
		$router->addRoute($printRoute);
		$router->addRoute($shipRoute);
	}
}
