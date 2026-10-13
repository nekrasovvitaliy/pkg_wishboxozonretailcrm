<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxOzonRetailCrm\Api\Controller;

use Joomla\CMS\Application\ApiApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\MVC\Controller\ApiController;
use JsonException;
use RuntimeException;
use Throwable;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Service\OzonFbsService;
use WishboxOzonRetailCrmLibrary\Service\OzonIntegrationModuleFactory;
use WishboxOzonRetailCrmLibrary\Service\RetailCrmPrintRequestParser;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Handles RetailCRM requests to the Ozon delivery integration.
 *
 * @since 1.0.0
 *
 * @noinspection PhpUnused
 */
final class WishboxozonController extends ApiController
{
	/**
	 * Print Ozon package labels requested by RetailCRM.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function print(): void
	{
		try
		{
			$this->validateClientId();
			$retailCrmPrintRequestParser = new RetailCrmPrintRequestParser();
			$postingNumbers = $retailCrmPrintRequestParser->parse(
				(string) $this->input->get('print', '', 'raw')
			);
			$pdfContent = $this->createOzonFbsService()
				->getPackageLabels($postingNumbers);

			$this->sendPdfResponse($pdfContent);
		}
		catch (Throwable $throwable)
		{
			Log::add((string) $throwable, Log::ERROR, 'com_wishboxozonretailcrm');
			$this->sendErrorResponse($throwable->getCode() === 403 ? 403 : 400);
		}
	}

	/**
	 * Verify the client identifier registered in RetailCRM.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function validateClientId(): void
	{
		$expectedClientId = OzonIntegrationModuleFactory::createClientId(
			(string) $this->app->get('secret')
		);
		$receivedClientId = $this->input->getString('clientId');

		if ($receivedClientId === '' || !hash_equals($expectedClientId, $receivedClientId))
		{
			throw new RuntimeException('RetailCRM integration clientId does not match.', 403);
		}
	}

	/**
	 * Create the configured Ozon FBS service.
	 *
	 * @return OzonFbsService
	 *
	 * @since 1.0.0
	 */
	private function createOzonFbsService(): OzonFbsService
	{
		$wishboxOzonComponentParams = ComponentHelper::getParams('com_wishboxozon');
		$ozonApiClient = new OzonApiClient(
			clientId: (string) $wishboxOzonComponentParams->get('ozon_client_id'),
			apiKey: (string) $wishboxOzonComponentParams->get('ozon_api_key'),
			apiUrl: (string) $wishboxOzonComponentParams->get(
				'ozon_api_url',
				'https://api-seller.ozon.ru'
			),
			proxyToken: (string) $wishboxOzonComponentParams->get('ozon_proxy_token')
		);

		return new OzonFbsService($ozonApiClient);
	}

	/**
	 * Send a PDF download response.
	 *
	 * @param   string  $pdfContent  Generated PDF content.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function sendPdfResponse(string $pdfContent): void
	{
		/** @var ApiApplication $app */
		$app = $this->app;
		$app->setHeader('status', '200', true);
		$app->setHeader('Content-Type', 'application/pdf', true);
		$app->setHeader('Content-Disposition', 'inline; filename="ozon-package-labels.pdf"', true);
		$app->setHeader('Content-Length', (string) strlen($pdfContent), true);
		$app->sendHeaders();

		echo $pdfContent;
		$app->close();
	}

	/**
	 * Send an error response compatible with RetailCRM.
	 *
	 * @param   int  $statusCode  HTTP status code.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	private function sendErrorResponse(int $statusCode): void
	{
		$body = [
			'success'  => false,
			'errorMsg' => 'Unable to print Ozon package labels.',
			'errors'   => ['print' => 'This value is not valid.'],
		];

		try
		{
			$json = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
		}
		catch (JsonException)
		{
			$json = '{"success":false,"errorMsg":"Print request failed."}';
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
