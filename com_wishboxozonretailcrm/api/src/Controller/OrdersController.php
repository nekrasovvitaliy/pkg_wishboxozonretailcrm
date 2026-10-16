<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxOzonRetailCrm\Api\Controller;

use InvalidArgumentException;
use Joomla\CMS\Application\ApiApplication;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\WishboxOzonRetailCrm\Administrator\Extension\WishboxOzonRetailCrmComponent;
use JsonException;
use Throwable;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Handles RetailCRM requests to the Ozon delivery integration.
 *
 * @since        1.0.0
 *
 * @noinspection PhpUnused
 */
final class OrdersController extends ApiController
{
	/**
	 * Ship an Ozon order requested by RetailCRM.
	 *
	 * @param   int|null  $ozonOrderId  RetailCRM order identifier.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function ship(?int $ozonOrderId = null): void
	{
		try
		{
			if ($ozonOrderId === null)
			{
				$ozonOrderId = $this->input->getInt('ozonorder_id');
			}

			if ($ozonOrderId <= 0)
			{
				throw new InvalidArgumentException('ozonorder_id must be greater than zero.');
			}

			/** @var ApiApplication $app */
			$app = $this->app;

			/** @var WishboxOzonRetailCrmComponent $wishboxOzonRetailCrmComponent */
			$wishboxOzonRetailCrmComponent = $app->bootComponent('com_wishboxozonretailcrm');

			$ozonOrderShipmentService = $wishboxOzonRetailCrmComponent->getOzonOrderShipmentService();
			$ozonOrderShipmentService->ship($ozonOrderId);
		}
		catch (Throwable $throwable)
		{
			Log::add((string) $throwable, Log::ERROR, 'com_wishboxozonretailcrm');
			$this->sendJsonResponse(
				[
					'success'  => false,
					'errorMsg' => $throwable->getMessage() ?: 'Unable to ship the Ozon order.',
				],
				400
			);

			return;
		}

		$this->sendJsonResponse(['success' => true], 200);
	}

	/**
	 * Send a JSON response compatible with RetailCRM.
	 *
	 * @param   array<string, mixed>  $body        Response body.
	 * @param   int                   $statusCode  HTTP status code.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function sendJsonResponse(array $body, int $statusCode): void
	{
		try
		{
			$json = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
		}
		catch (JsonException)
		{
			$json = empty($body['success'])
				? '{"success":false,"errorMsg":"Unable to ship the Ozon order."}'
				: '{"success":true}';
		}

		/** @var ApiApplication $app */
		$app = $this->app;
		$app->setHeader('status', (string) $statusCode, true);
		$app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
		$app->sendHeaders();

		echo $json;
		$app->close();
	}
}
