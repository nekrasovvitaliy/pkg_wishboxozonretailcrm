<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Defines the contract for classes that depend on Ozon order shipment.
 *
 * @since 1.0.0
 */
interface OzonOrderShipmentServiceAwareInterface
{
	/**
	 * Get the Ozon order shipment service.
	 *
	 * @return OzonOrderShipmentService Ozon order shipment service.
	 *
	 * @since 1.0.0
	 */
	public function getOzonOrderShipmentService(): OzonOrderShipmentService;

	/**
	 * Set the Ozon order shipment service.
	 *
	 * @param   OzonOrderShipmentService  $ozonOrderShipmentService  Ozon order shipment service.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function setOzonOrderShipmentService(OzonOrderShipmentService $ozonOrderShipmentService): void;
}
