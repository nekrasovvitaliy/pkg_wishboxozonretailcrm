<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use RetailCrm\Api\Client;
use RetailCrm\Api\Exception\Api\AccountDoesNotExistException;
use RetailCrm\Api\Exception\Api\ApiErrorException;
use RetailCrm\Api\Exception\Api\MissingCredentialsException;
use RetailCrm\Api\Exception\Api\MissingParameterException;
use RetailCrm\Api\Exception\Api\ValidationException;
use RetailCrm\Api\Exception\Client\HandlerException;
use RetailCrm\Api\Exception\Client\HttpClientException;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
use RetailCrm\Api\Model\Entity\Integration\IntegrationModule;
use RetailCrm\Api\Model\Request\Integration\IntegrationModulesEditRequest;
use WishboxOzonRetailCrmLibrary\Dto\IntegrationModuleStatus;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Reads and updates the Ozon integration module in RetailCRM.
 *
 * @since 1.0.0
 */
final readonly class RetailCrmIntegrationModuleService
{
	/**
	 * Create the integration module service.
	 *
	 * @param   Client             $client    RetailCRM API client.
	 * @param   IntegrationModule  $expected  Expected module definition.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function __construct(
		private Client $client,
		private IntegrationModule $expected
	) {
	}

	/**
	 * Read and compare the integration module.
	 *
	 * @return IntegrationModuleStatus
	 *
	 * @throws ApiExceptionInterface
	 * @throws AccountDoesNotExistException
	 * @throws ApiErrorException
	 * @throws MissingCredentialsException
	 * @throws MissingParameterException
	 * @throws ValidationException
	 * @throws HandlerException
	 * @throws HttpClientException
	 * @throws ClientExceptionInterface
	 *
	 * @since 1.0.0
	 */
	public function getStatus(): IntegrationModuleStatus
	{
		try
		{
			$current = $this->client->integration
				->get($this->expected->code)
				->integrationModule;
		}
		catch (ApiExceptionInterface $exception)
		{
			if ($exception->getStatusCode() !== 404)
			{
				throw $exception;
			}

			return new IntegrationModuleStatus(
				false,
				false,
				[],
				null,
				$this->expected
			);
		}

		$differences = self::compare($current, $this->expected);

		return new IntegrationModuleStatus(
			true,
			$differences !== [],
			$differences,
			$current,
			$this->expected
		);
	}

	/**
	 * Create or update the integration module.
	 *
	 * @return void
	 *
	 * @throws AccountDoesNotExistException
	 * @throws ApiErrorException
	 * @throws ApiExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws HandlerException
	 * @throws HttpClientException
	 * @throws MissingCredentialsException
	 * @throws MissingParameterException
	 * @throws ValidationException
	 *
	 * @since 1.0.0
	 */
	public function update(): void
	{
		$this->client->integration
			->edit(
				$this->expected->code,
				new IntegrationModulesEditRequest($this->expected)
			);
	}

	/**
	 * Compare fields controlled by this package.
	 *
	 * @param   IntegrationModule  $current   Current module definition.
	 * @param   IntegrationModule  $expected  Expected module definition.
	 *
	 * @return array<int, string>
	 *
	 * @since 1.0.0
	 */
	public static function compare(IntegrationModule $current, IntegrationModule $expected): array
	{
		$currentValues = self::managedValues($current);
		$expectedValues = self::managedValues($expected);
		$differences = [];

		foreach ($expectedValues as $path => $value)
		{
			if (($currentValues[$path] ?? null) !== $value)
			{
				$differences[] = $path;
			}
		}

		return $differences;
	}

	/**
	 * Flatten managed module fields for comparison.
	 *
	 * @param   IntegrationModule  $module  Integration module definition.
	 *
	 * @return array<string, mixed>
	 *
	 * @since 1.0.0
	 */
	private static function managedValues(IntegrationModule      $module): array
	{
		$delivery = $module->integrations?->delivery;
		$plate = $delivery?->plateList[0] ?? null;

		return [
			'code'                            => $module->code,
			'integrationCode'                 => $module->integrationCode,
			'active'                          => $module->active,
			'name'                            => $module->name,
			'logo'                            => $module->logo,
			'clientId'                        => $module->clientId,
			'baseUrl'                         => rtrim($module->baseUrl, '/'),
			'actions'                         => $module->actions ?? [],
			'availableCountries'              => $module->availableCountries ?? [],
			'accountUrl'                      => $module->accountUrl,
			'integrations.delivery.actions'   => $delivery?->actions ?? [],
			'integrations.delivery.payerType' => $delivery?->payerType ?? [],
			'integrations.delivery.limit'     => $delivery?->platePrintLimit,
			'integrations.delivery.plate.type'  => $plate?->type,
			'integrations.delivery.plate.code'  => $plate?->code,
			'integrations.delivery.plate.label' => $plate?->label,
		];
	}
}
