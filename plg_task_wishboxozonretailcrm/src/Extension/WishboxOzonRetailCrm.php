<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license         GNU General Public License version 2 or later;
 */

namespace Joomla\Plugin\Task\WishboxOzonRetailCrm\Extension;

use Exception;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Event\SubscriberInterface;
use Throwable;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderImportServiceAwareInterface;
use WishboxOzonRetailCrmLibrary\Service\OzonOrderImportServiceAwareTrait;
use WishboxOzonRetailCrmLibrary\Service\RetailCrmToOzonProductSyncServiceAwareInterface;
use WishboxOzonRetailCrmLibrary\Service\RetailCrmToOzonProductSyncServiceAwareTrait;

defined('_JEXEC') or die;

/**
 * Provides scheduled synchronization between RetailCRM and Ozon.
 *
 * @since 1.0.0
 */
final class WishboxOzonRetailCrm extends CMSPlugin implements
	SubscriberInterface,
	RetailCrmToOzonProductSyncServiceAwareInterface,
	OzonOrderImportServiceAwareInterface
{
	use TaskPluginTrait;
	use RetailCrmToOzonProductSyncServiceAwareTrait;
	use OzonOrderImportServiceAwareTrait;

	protected const array TASKS_MAP = [
		'plg_task_wishboxozonretailcrm_update_products'   => [
			'langConstPrefix' => 'PLG_TASK_WISHBOXOZONRETAILCRM_UPDATE_PRODUCTS',
			'form'            => 'update_products',
			'method'          => 'updateProducts',
		],
		'plg_task_wishboxozonretailcrm_import_new_orders' => [
			'langConstPrefix' => 'PLG_TASK_WISHBOXOZONRETAILCRM_IMPORT_NEW_ORDERS',
			'form'            => 'import_new_orders',
			'method'          => 'importNewOrders',
		],
	];

	protected $autoloadLanguage = true;

	/**
	 * @return array<string, string>
	 *
	 * @since 1.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onTaskOptionsList'    => 'advertiseRoutines',
			'onExecuteTask'        => 'standardRoutineHandler',
			'onContentPrepareForm' => 'enhanceTaskItemForm',
		];
	}

	/**
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return int
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	private function updateProducts(ExecuteTaskEvent $event): int
	{
		try
		{
			$params = $event->getArgument('params');
			$limit  = (int) ($params->limit ?? $this->params->get('product_limit', 1000));
			$result = $this->getRetailCrmToOzonProductSyncService()
				->updateProducts($limit);

			$this->logTask(
				Text::sprintf(
					'PLG_TASK_WISHBOXOZONRETAILCRM_UPDATE_PRODUCTS_RESULT',
					$result->updated,
					$result->skipped,
					$result->failed
				),
				$result->failed > 0 ? 'warning' : 'info'
			);

			foreach ($result->errors as $message)
			{
				$this->logTask($message, 'error');
			}

			return $result->failed > 0 ? Status::KNOCKOUT : Status::OK;
		}
		catch (Throwable $throwable)
		{
			$this->logTask((string) $throwable, 'error');

			return Status::KNOCKOUT;
		}
	}

	/**
	 * @param   ExecuteTaskEvent  $event
	 *
	 * @return int
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	private function importNewOrders(ExecuteTaskEvent $event): int
	{
		try
		{
			$params = $event->getArgument('params');
			$limit  = (int) ($params->limit ?? $this->params->get('order_import_limit', 10));
			$result = $this->getOzonOrderImportService()
				->importNewOrders($limit);

			$this->logTask(
				Text::sprintf(
					'PLG_TASK_WISHBOXOZONRETAILCRM_IMPORT_NEW_ORDERS_RESULT',
					$result->created,
					$result->skipped,
					$result->failed
				),
				$result->failed > 0 ? 'warning' : 'info'
			);

			foreach ($result->errors as $postingNumber => $message)
			{
				$this->logTask('Ozon posting ' . $postingNumber . ': ' . $message, 'error');
			}

			return $result->failed > 0 ? Status::KNOCKOUT : Status::OK;
		}
		catch (Throwable $throwable)
		{
			$this->logTask((string) $throwable, 'error');

			return Status::KNOCKOUT;
		}
	}
}
