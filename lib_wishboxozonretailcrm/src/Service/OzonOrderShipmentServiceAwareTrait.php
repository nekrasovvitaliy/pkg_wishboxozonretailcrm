<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use UnexpectedValueException;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Provides Ozon order shipment service injection.
 *
 * @since 1.0.0
 */
trait OzonOrderShipmentServiceAwareTrait
{
	private ?OzonOrderShipmentService $ozonOrderShipmentService = null;

	/**
	 * Get the Ozon order shipment service.
	 *
	 * @return OzonOrderShipmentService Ozon order shipment service.
	 *
	 * @throws UnexpectedValueException When the service has not been injected.
	 *
	 * @since 1.0.0
	 */
	public function getOzonOrderShipmentService(): OzonOrderShipmentService
	{
		if ($this->ozonOrderShipmentService instanceof OzonOrderShipmentService)
		{
			return $this->ozonOrderShipmentService;
		}

		throw new UnexpectedValueException(
			'OzonOrderShipmentService not set in ' . static::class . '.'
		);
	}

	/**
	 * Set the Ozon order shipment service.
	 *
	 * @param   OzonOrderShipmentService  $ozonOrderShipmentService  Ozon order shipment service.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function setOzonOrderShipmentService(OzonOrderShipmentService $ozonOrderShipmentService): void
	{
		$this->ozonOrderShipmentService = $ozonOrderShipmentService;
	}
}
