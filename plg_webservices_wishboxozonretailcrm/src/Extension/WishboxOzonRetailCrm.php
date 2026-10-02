<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Plugin\Webservices\WishboxOzonRetailCrm\Extension;

use Joomla\CMS\Application\ApiApplication;
use Joomla\CMS\Event\Application\AfterApiRouteEvent;
use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;
use JsonException;
use RuntimeException;
use Throwable;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderImportServiceAwareInterface;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderImportServiceAwareTrait;

defined('_JEXEC') or die;

/**
 * Exposes a public Ozon push-notification endpoint.
 *
 * @since 1.0.0
 */
final class WishboxOzonRetailCrm extends CMSPlugin implements
	SubscriberInterface,
	OzonOrderImportServiceAwareInterface
{
	use OzonOrderImportServiceAwareTrait;

	private const string COMPONENT = 'com_wishboxozonretailcrm';
	private const string CONTROLLER = 'callback';
	private const string ROUTE = 'v1/wishboxozonretailcrm/callback';

	/**
	 * @return array<string, string>
	 *
	 * @since 1.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onBeforeApiRoute' => 'onBeforeApiRoute',
			'onAfterApiRoute'  => 'onAfterApiRoute',
		];
	}

	/**
	 * Register the Ozon notification route.
	 *
	 * @param   BeforeApiRouteEvent  $event  API routing event.
	 *
	 * @since 1.0.0
	 */
	public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
	{
		$route = new Route(
			['POST'],
			self::ROUTE,
			self::CONTROLLER . '.handle',
			[],
			[
				'component' => self::COMPONENT,
				'public'    => true,
				'format'    => ['application/json', 'application/vnd.api+json'],
			]
		);

		$event->getRouter()
			->addRoute($route);
	}

	/**
	 * Handle a matched Ozon callback before Joomla dispatches a controller.
	 *
	 * @param   AfterApiRouteEvent  $event  API routing event.
	 *
	 * @since 1.0.0
	 */
	public function onAfterApiRoute(AfterApiRouteEvent $event): void
	{
		$app = $this->getApplication();

		if (
			$app->getInput()->getCmd('option') !== self::COMPONENT
			|| $app->getInput()->getCmd('controller') !== self::CONTROLLER
		)
		{
			return;
		}

		try
		{
			$this->validateToken();
			$payload = json_decode($app->getInput()->json->getRaw(), false, 512, JSON_THROW_ON_ERROR);

			if (!is_object($payload))
			{
				throw new RuntimeException('Ozon notification payload must be a JSON object.');
			}

			$this->handlePayload($payload);
			$this->sendResponse($this->buildResponse());
		}
		catch (Throwable $throwable)
		{
			Log::add((string) $throwable, Log::ERROR, 'wishboxozonretailcrm');
			$this->sendResponse(['error' => 'notification_failed'], 500);
		}
	}

	private function validateToken(): void
	{
		$configuredToken = (string) $this->params->get('webhook_token');
		$receivedToken = (string) $this->getApplication()->getInput()->getString('token');

		if ($configuredToken === '' || !hash_equals($configuredToken, $receivedToken))
		{
			throw new RuntimeException('Ozon webhook token does not match.');
		}
	}

	private function handlePayload(object $payload): void
	{
		$type = (string) ($payload->message_type ?? $payload->type ?? '');

		if ($type !== 'TYPE_NEW_POSTING')
		{
			return;
		}

		$result = $this->getOzonOrderImportService()
			->importNewOrders((int) $this->params->get('order_import_limit', 10));

		if ($result->failed > 0)
		{
			throw new RuntimeException(implode('; ', $result->errors));
		}
	}

	/**
	 * @return array{version: string, name: string, time: string}
	 */
	private function buildResponse(): array
	{
		return [
			'version' => '1.0',
			'name'    => 'wishboxozonretailcrm',
			'time'    => gmdate('Y-m-d\TH:i:s\Z'),
		];
	}

	/**
	 * @param   array<string, mixed>  $body
	 *
	 * @throws JsonException
	 */
	private function sendResponse(array $body, int $statusCode = 200): void
	{
		/** @var ApiApplication $app */
		$app = $this->getApplication();
		$app->setHeader('status', (string) $statusCode, true);
		$app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$app->sendHeaders();

		echo json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
		$app->close();
	}
}
