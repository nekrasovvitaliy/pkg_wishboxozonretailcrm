<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonRetailCrmLibrary\Repositories;

use Joomla\Registry\Registry;
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
use RetailCrm\Api\Model\Entity\Store\OfferPrice;
use RetailCrm\Api\Model\Entity\Store\StoreOffer;
use RetailCrm\Api\Model\Filter\Store\OfferFilterType;
use RetailCrm\Api\Model\Request\Store\OffersRequest;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Reads Ozon mappings, prices, and stock quantities from RetailCRM offers.
 *
 * @since 1.0.0
 */
final class RetailCrmOfferRepository
{
	private const int PAGE_LIMIT = 100;

	public function __construct(
		private readonly Client $retailCrmClient,
		private readonly Registry $params
	) {
	}

    /**
     * Load RetailCRM offers page by page.
     *
     * @param int $maxOffers
     * @return list<StoreOffer>
     *
     * @throws AccountDoesNotExistException
     * @throws ApiErrorException
     * @throws MissingCredentialsException
     * @throws MissingParameterException
     * @throws ValidationException
     * @throws HandlerException
     * @throws HttpClientException
     * @throws ApiExceptionInterface
     * @throws ClientExceptionInterface
     * @since 1.0.0
     */
	public function getOffers(int $maxOffers): array
	{
		$maxOffers = max(1, $maxOffers);
		$page = 1;
		$offers = [];

		do
		{
			$request = new OffersRequest();
			$request->limit = min(self::PAGE_LIMIT, $maxOffers - count($offers));
			$request->page = $page;
			$site = trim((string) $this->params->get('retailcrm_site'));

			if ($site !== '')
			{
				$request->filter = new OfferFilterType();
				$request->filter->sites = [$site];
			}

			$response = $this->retailCrmClient->store->offers($request);
			$pageOffers = is_array($response->offers ?? null) ? $response->offers : [];

			foreach ($pageOffers as $offer)
			{
				if ($offer instanceof StoreOffer)
				{
					$offers[] = $offer;
				}

				if (count($offers) >= $maxOffers)
				{
					break 2;
				}
			}

			$totalPages = $response->pagination->totalPageCount ?? $page;
			$page++;
		}
		while ($page <= $totalPages && $pageOffers !== []);

		return $offers;
	}

    /**
     * Resolve RetailCRM offers by Ozon SKU.
     *
     * @param list<int> $ozonSkus Ozon marketplace SKU values.
     * @param int $maxOffers
     * @return array<int, StoreOffer>
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
     * @since 1.0.0
     */
	public function getOffersByOzonSkus(array $ozonSkus, int $maxOffers): array
	{
		$wantedSkus = array_map('intval', $ozonSkus)
			|> (fn ($values) => array_filter($values, static fn (int $ozonSku): bool => $ozonSku > 0))
			|> array_unique(...)
			|> array_values(...)
			|> (fn ($values) => array_fill_keys($values, true));
		$result = [];

		if ($wantedSkus === [])
		{
			return $result;
		}

		foreach ($this->getOffers($maxOffers) as $offer)
		{
			$ozonSku = $this->getOzonSku($offer);

			if (isset($wantedSkus[$ozonSku]))
			{
				$result[$ozonSku] = $offer;
			}

			if (count($result) === count($wantedSkus))
			{
				break;
			}
		}

		return $result;
	}

	/**
	 * Read the Ozon product ID from the configured RetailCRM property.
	 *
	 * @since 1.0.0
	 */
	public function getOzonProductId(StoreOffer $offer): int
	{
		$propertyCode = trim((string) $this->params->get('ozon_product_id_property', 'ozon_product_id'));
		$value = $this->getOfferOrProductProperty($offer, $propertyCode);

		return is_numeric($value) ? max(0, (int) $value) : 0;
	}

	/**
	 * Read the Ozon marketplace SKU from the configured RetailCRM property.
	 *
	 * @since 1.0.0
	 */
	public function getOzonSku(StoreOffer $offer): int
	{
		$propertyCode = trim((string) $this->params->get('ozon_sku_property', 'ozon_sku'));
		$value = $this->getOfferOrProductProperty($offer, $propertyCode);

		return is_numeric($value) ? max(0, (int) $value) : 0;
	}

	/**
	 * Return the configured RetailCRM price for an offer.
	 *
	 * @since 1.0.0
	 */
	public function getPrice(StoreOffer $offer): ?float
	{
		$priceType = trim((string) $this->params->get('retailcrm_price_type', 'base'));
		$prices = is_array($offer->prices ?? null) ? $offer->prices : [];
		$fallbackPrice = null;

		foreach ($prices as $price)
		{
			if (!$price instanceof OfferPrice || !is_numeric($price->price))
			{
				continue;
			}

			$fallbackPrice ??= (float) $price->price;

			if ($price->priceType === $priceType)
			{
				return max(0.0, (float) $price->price);
			}
		}

		return $fallbackPrice !== null ? max(0.0, $fallbackPrice) : null;
	}

	/**
	 * Return the quantity that should be published to Ozon.
	 *
	 * @since 1.0.0
	 */
	public function getStock(StoreOffer $offer): int
	{
		$offerActive = !isset($offer->active) || $offer->active;
		$productActive = !isset($offer->product->active) || $offer->product->active;

		if (!$offerActive || !$productActive)
		{
			return 0;
		}

		return max(0, (int) floor((float) ($offer->quantity ?? 0)));
	}

	private function getOfferOrProductProperty(StoreOffer $offer, string $propertyCode): mixed
	{
		$value = $this->getPropertyValue($offer->properties ?? [], $propertyCode);

		if ($value === null && isset($offer->product))
		{
			$value = $this->getPropertyValue($offer->product->properties ?? [], $propertyCode);
		}

		return $value;
	}

	private function getPropertyValue(mixed $properties, string $propertyCode): mixed
	{
		if ($propertyCode === '' || !is_array($properties))
		{
			return null;
		}

		if (array_key_exists($propertyCode, $properties))
		{
			return $properties[$propertyCode];
		}

		foreach ($properties as $property)
		{
			if (is_object($property) && (string) ($property->code ?? '') === $propertyCode)
			{
				return $property->value ?? null;
			}
		}

		return null;
	}
}
