<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use RetailCrm\Api\Model\Entity\Integration\Delivery\DeliveryConfiguration;
use RetailCrm\Api\Model\Entity\Integration\Delivery\Plate;
use RetailCrm\Api\Model\Entity\Integration\IntegrationModule;
use RetailCrm\Api\Model\Entity\Integration\Integrations;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Builds the Ozon delivery module definition for RetailCRM.
 *
 * @since 1.0.0
 */
final class OzonIntegrationModuleFactory
{
	public const string CODE = 'wishboxozonshipping';

	/**
	 * Build the stable RetailCRM client identifier for this Joomla site.
	 *
	 * @param   string  $siteSecret  Joomla site secret.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function createClientId(string $siteSecret): string
	{
		return hash_hmac('sha256', self::CODE, $siteSecret);
	}

	/**
	 * Build the expected integration module.
	 *
	 * @param   string  $baseUrl     Public integration API base URL.
	 * @param   string  $accountUrl  Integration management page URL.
	 * @param   string  $logoUrl     Public SVG logo URL.
	 * @param   string  $clientId    Stable client identifier.
	 *
	 * @return IntegrationModule
	 *
	 * @since 1.0.0
	 */
	public function create(string $baseUrl, string $accountUrl, string $logoUrl, string $clientId): IntegrationModule
	{
		$plate = new Plate();
		$plate->type = 'order';
		$plate->code = 'package-label';
		$plate->label = 'Штрих-код места';

		$delivery = new DeliveryConfiguration();
		$delivery->actions = ['print' => '/print'];
		$delivery->payerType = ['sender'];
		$delivery->platePrintLimit = 99;
		$delivery->plateList = [$plate];

		$integrations = new Integrations();
		$integrations->delivery = $delivery;

		$module = new IntegrationModule();
		$module->code = self::CODE;
		$module->integrationCode = self::CODE;
		$module->active = true;
		$module->name = 'Ozon.ru';
		$module->logo = $logoUrl;
		$module->clientId = $clientId;
		$module->baseUrl = rtrim($baseUrl, '/');
		$module->actions = [];
		$module->availableCountries = ['RU'];
		$module->accountUrl = $accountUrl;
		$module->integrations = $integrations;

		return $module;
	}
}
