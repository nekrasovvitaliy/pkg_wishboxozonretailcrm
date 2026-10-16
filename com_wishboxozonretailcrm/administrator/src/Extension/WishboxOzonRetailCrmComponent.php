<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Joomla\Component\WishboxOzonRetailCrm\Administrator\Extension;

use Joomla\CMS\Extension\MVCComponent;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderShipmentServiceAwareInterface;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderShipmentServiceAwareTrait;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * WishBox Ozon RetailCRM administrator component.
 *
 * @since 1.0.0
 */
final class WishboxOzonRetailCrmComponent extends MVCComponent implements OzonOrderShipmentServiceAwareInterface
{
	use OzonOrderShipmentServiceAwareTrait;
}
