<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

defined('_JEXEC') or die;

interface OzonOrderImportServiceAwareInterface
{
	public function setOzonOrderImportService(OzonOrderImportService $ozonOrderImportService): void;
}
