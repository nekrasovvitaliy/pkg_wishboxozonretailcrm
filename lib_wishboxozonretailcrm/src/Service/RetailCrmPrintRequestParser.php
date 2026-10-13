<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Service;

use InvalidArgumentException;
use JsonException;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Extracts Ozon posting numbers from a RetailCRM print request.
 *
 * @since 1.0.0
 */
final class RetailCrmPrintRequestParser
{
	private const int PRINT_LIMIT = 99;

	/**
	 * Parse the serialized RetailCRM print parameter.
	 *
	 * @param   string  $printPayload  Serialized print request.
	 *
	 * @return list<string>
	 *
	 * @throws InvalidArgumentException When the payload does not match the print contract.
	 *
	 * @since 1.0.0
	 */
	public function parse(string $printPayload): array
	{
		try
		{
			$printRequest = json_decode($printPayload, true, 512, JSON_THROW_ON_ERROR);
		}
		catch (JsonException $exception)
		{
			throw new InvalidArgumentException('The print parameter must contain valid JSON.', 0, $exception);
		}

		if (!is_array($printRequest))
		{
			throw new InvalidArgumentException('The print parameter must contain a JSON object.');
		}

		$deliveryIds = $printRequest['deliveryIds'] ?? null;

		if (!is_array($deliveryIds) || $deliveryIds === [])
		{
			throw new InvalidArgumentException('The print parameter must contain deliveryIds.');
		}

		$postingNumbers = [];

		foreach ($deliveryIds as $orderDeliveryIds)
		{
			if (!is_array($orderDeliveryIds))
			{
				throw new InvalidArgumentException('Each deliveryIds value must be an array.');
			}

			foreach ($orderDeliveryIds as $postingNumber)
			{
				if (!is_string($postingNumber) && !is_int($postingNumber))
				{
					throw new InvalidArgumentException('Every delivery ID must be a string.');
				}

				$postingNumber = trim((string) $postingNumber);

				if ($postingNumber === '')
				{
					throw new InvalidArgumentException('Ozon posting numbers must not be empty.');
				}

				$postingNumbers[] = $postingNumber;
			}
		}

		$postingNumbers = array_values(array_unique($postingNumbers));

		if ($postingNumbers === [])
		{
			throw new InvalidArgumentException('At least one Ozon posting number is required.');
		}

		if (count($postingNumbers) > self::PRINT_LIMIT)
		{
			throw new InvalidArgumentException('No more than 99 Ozon labels can be printed at once.');
		}

		return $postingNumbers;
	}
}
