<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductQuantity\Business\Model\Validator;

use ArrayObject;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CartPreCheckResponseTransfer;
use Generated\Shared\Transfer\CheckoutErrorTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\ProductQuantityTransfer;
use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Zed\ProductQuantity\Business\Model\ProductQuantityReaderInterface;
use Spryker\Zed\ProductQuantity\ProductQuantityConfig;

class ProductQuantityRestrictionValidator implements ProductQuantityRestrictionValidatorInterface
{
    /**
     * @var string
     */
    protected const ERROR_QUANTITY_MIN_NOT_FULFILLED = 'cart.pre.check.quantity.min.failed';

    /**
     * @var string
     */
    protected const ERROR_QUANTITY_MAX_NOT_FULFILLED = 'cart.pre.check.quantity.max.failed';

    /**
     * @var string
     */
    protected const ERROR_QUANTITY_INTERVAL_NOT_FULFILLED = 'cart.pre.check.quantity.interval.failed';

    /**
     * @var string
     */
    protected const ERROR_QUANTITY_INCORRECT = 'cart.pre.check.quantity.value.failed';

    /**
     * @var string
     */
    protected const GLOSSARY_KEY_PARAMETER_SKU = '%sku%';

    /**
     * @var string
     */
    protected const RESTRICTION_MIN = 'min';

    /**
     * @var string
     */
    protected const RESTRICTION_MAX = 'max';

    /**
     * @var string
     */
    protected const RESTRICTION_INTERVAL = 'interval';

    /**
     * @var \Spryker\Zed\ProductQuantity\Business\Model\ProductQuantityReaderInterface
     */
    protected $productQuantityReader;

    public function __construct(ProductQuantityReaderInterface $productQuantityReader, protected ProductQuantityConfig $productQuantityConfig)
    {
        $this->productQuantityReader = $productQuantityReader;
    }

    public function validateItemAddition(CartChangeTransfer $cartChangeTransfer): CartPreCheckResponseTransfer
    {
        $responseTransfer = (new CartPreCheckResponseTransfer())->setIsSuccess(true);

        $changedSkuMapByGroupKey = $this->getChangedSkuMap($cartChangeTransfer);
        $itemQuantitiesIndexedBySku = $this->getItemQuantitiesIndexedBySku($cartChangeTransfer->getItems());
        $cartQuantityMapByGroupKey = $this->getItemAddCartQuantityMap($cartChangeTransfer);
        $productQuantityTransferMapBySku = $this->getProductQuantityTransferMap(array_values($changedSkuMapByGroupKey));

        foreach ($cartQuantityMapByGroupKey as $productGroupKey => $productQuantity) {
            $productSku = $changedSkuMapByGroupKey[$productGroupKey];
            $cartItemQuantity = $itemQuantitiesIndexedBySku[$productSku];
            if (
                !$this->validateQuantityIsNonNegativeInteger($productSku, $cartItemQuantity, $responseTransfer) ||
                !$this->validateQuantityIsPositiveInteger($productSku, $productQuantity, $responseTransfer)
            ) {
                continue;
            }
            $this->validateItem($productSku, $productQuantity, $productQuantityTransferMapBySku[$productSku], $responseTransfer);
        }

        return $responseTransfer;
    }

    public function validateItemRemoval(CartChangeTransfer $cartChangeTransfer): CartPreCheckResponseTransfer
    {
        $responseTransfer = (new CartPreCheckResponseTransfer())->setIsSuccess(true);

        $changedSkuMapByGroupKey = $this->getChangedSkuMap($cartChangeTransfer);
        $itemQuantitiesIndexedBySku = $this->getItemQuantitiesIndexedBySku($cartChangeTransfer->getItems());
        $cartQuantityMapByGroupKey = $this->getItemRemoveCartQuantityMap($cartChangeTransfer);
        $productQuantityTransferMap = $this->getProductQuantityTransferMap(array_values($changedSkuMapByGroupKey));

        foreach ($cartQuantityMapByGroupKey as $productGroupKey => $productQuantity) {
            $productSku = $changedSkuMapByGroupKey[$productGroupKey];
            $cartItemQuantity = $itemQuantitiesIndexedBySku[$productSku];
            if (
                !$this->validateQuantityIsNonNegativeInteger($productSku, $cartItemQuantity, $responseTransfer) ||
                !$this->validateQuantityIsNonNegativeInteger($productSku, $productQuantity, $responseTransfer)
            ) {
                continue;
            }
            $this->validateItem($productSku, $productQuantity, $productQuantityTransferMap[$productSku], $responseTransfer);
        }

        return $responseTransfer;
    }

    public function isValidItemQuantitiesOnCheckout(
        QuoteTransfer $quoteTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer
    ): bool {
        $errorsCount = $checkoutResponseTransfer->getErrors()->count();
        $itemSkus = $this->extractItemSkus($quoteTransfer->getItems());
        $productQuantityTransfersIndexedBySku = $this->getProductQuantityTransferMap($itemSkus);

        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $productQuantityTransfer = $productQuantityTransfersIndexedBySku[$itemTransfer->getSkuOrFail()] ?? null;
            if (!$productQuantityTransfer) {
                continue;
            }

            $checkoutResponseTransfer = $this->validateItemPreCheckout(
                $itemTransfer,
                $productQuantityTransfer,
                $checkoutResponseTransfer,
            );
        }

        return $checkoutResponseTransfer->getErrors()->count() === $errorsCount;
    }

    public function isValidItemQuantitiesBySkuOnCheckout(
        QuoteTransfer $quoteTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer
    ): bool {
        $errorsCount = $checkoutResponseTransfer->getErrors()->count();
        $itemSkus = $this->extractItemSkus($quoteTransfer->getItems());
        $productQuantityTransfersIndexedBySku = $this->getProductQuantityTransferMap($itemSkus);

        $groupedItemsBySku = $this->getItemQuantitiesIndexedBySku($quoteTransfer->getItems());

        $checkedSkus = [];
        foreach ($quoteTransfer->getItems() as $itemTransfer) {
            $productQuantityTransfer = $productQuantityTransfersIndexedBySku[$itemTransfer->getSkuOrFail()] ?? null;
            if (!$productQuantityTransfer || in_array($itemTransfer->getSku(), $checkedSkus)) {
                continue;
            }

            $checkoutResponseTransfer = $this->validateItemPreCheckout(
                $itemTransfer,
                $productQuantityTransfer,
                $checkoutResponseTransfer,
                $groupedItemsBySku[$itemTransfer->getSku()] ?? null,
            );

            $checkedSkus[] = $itemTransfer->getSku();
        }

        return $checkoutResponseTransfer->getErrors()->count() === $errorsCount;
    }

    public function validateItemAdditionBySku(CartChangeTransfer $cartChangeTransfer): CartPreCheckResponseTransfer
    {
        $responseTransfer = (new CartPreCheckResponseTransfer())->setIsSuccess(true);

        $itemQuantitiesIndexedBySku = $this->getItemQuantitiesIndexedBySku($cartChangeTransfer->getQuote()->getItems());
        $changedItemQuantitiesIndexedBySku = $this->getItemQuantitiesIndexedBySku($cartChangeTransfer->getItems());

        /** @var array<string> $uniqueSkuAffected */
        $uniqueSkuAffected = array_keys($itemQuantitiesIndexedBySku) + array_keys($changedItemQuantitiesIndexedBySku);

        $productQuantityTransferMapBySku = $this->getProductQuantityTransferMap(array_values($uniqueSkuAffected));

        foreach ($uniqueSkuAffected as $productSku) {
            $currentQuantity = $itemQuantitiesIndexedBySku[$productSku] ?? 0;
            $increasingQuantity = $changedItemQuantitiesIndexedBySku[$productSku] ?? 0;
            $newItemQuantity = $currentQuantity + $increasingQuantity;

            if (
                !$this->validateQuantityIsNonNegativeInteger($productSku, $newItemQuantity, $responseTransfer) ||
                !$this->validateQuantityIsPositiveInteger($productSku, $newItemQuantity, $responseTransfer)
            ) {
                continue;
            }
            $this->validateItem($productSku, $newItemQuantity, $productQuantityTransferMapBySku[$productSku], $responseTransfer);
        }

        return $responseTransfer;
    }

    protected function validateItem(
        string $sku,
        int $quantity,
        ProductQuantityTransfer $productQuantityTransfer,
        CartPreCheckResponseTransfer $responseTransfer
    ): void {
        $min = $productQuantityTransfer->getQuantityMin();
        $max = $productQuantityTransfer->getQuantityMax();
        $interval = $productQuantityTransfer->getQuantityInterval();

        if (!$this->isMinQuantityFulfilled($quantity, $min)) {
            $this->addViolation(static::ERROR_QUANTITY_MIN_NOT_FULFILLED, $sku, $min, $quantity, $responseTransfer);
        }

        if (!$this->isQuantityIntervalFulfilled($quantity, $min, $interval)) {
            $this->addViolation(static::ERROR_QUANTITY_INTERVAL_NOT_FULFILLED, $sku, $interval, $quantity, $responseTransfer);
        }

        if (!$this->isMaxQuantityFulfilled($quantity, $max)) {
            $this->addViolation(static::ERROR_QUANTITY_MAX_NOT_FULFILLED, $sku, $max, $quantity, $responseTransfer);
        }
    }

    protected function isItemInBundle(ItemTransfer $itemTransfer): bool
    {
        return $itemTransfer->getRelatedBundleItemIdentifier() !== null;
    }

    protected function validateItemPreCheckout(
        ItemTransfer $itemTransfer,
        ProductQuantityTransfer $productQuantityTransfer,
        CheckoutResponseTransfer $checkoutResponseTransfer,
        ?int $realQuantity = null,
    ): CheckoutResponseTransfer {
        if ($this->isItemInBundle($itemTransfer)) {
            return $checkoutResponseTransfer;
        }

        $itemQuantity = $realQuantity ?: $itemTransfer->getQuantityOrFail();
        $min = $productQuantityTransfer->getQuantityMin();
        $max = $productQuantityTransfer->getQuantityMax();
        $interval = $productQuantityTransfer->getQuantityInterval();

        if (!$this->isQuantityPositiveInteger($itemQuantity)) {
            $checkoutResponseTransfer->addError(
                $this->createCheckoutErrorTransfer(
                    static::ERROR_QUANTITY_INCORRECT,
                    [static::GLOSSARY_KEY_PARAMETER_SKU => $itemTransfer->getSkuOrFail()],
                    $itemTransfer,
                ),
            );

            return $checkoutResponseTransfer;
        }

        if (!$this->isMinQuantityFulfilled($itemQuantity, $min)) {
            $checkoutResponseTransfer->addError(
                $this->createCheckoutErrorTransfer(
                    static::ERROR_QUANTITY_MIN_NOT_FULFILLED,
                    [static::GLOSSARY_KEY_PARAMETER_SKU => $itemTransfer->getSkuOrFail()],
                    $itemTransfer,
                ),
            );
        }

        if (!$this->isQuantityIntervalFulfilled($itemQuantity, $min, $interval)) {
            $checkoutResponseTransfer->addError(
                $this->createCheckoutErrorTransfer(
                    static::ERROR_QUANTITY_INTERVAL_NOT_FULFILLED,
                    [static::GLOSSARY_KEY_PARAMETER_SKU => $itemTransfer->getSkuOrFail()],
                    $itemTransfer,
                ),
            );
        }

        if (!$this->isMaxQuantityFulfilled($itemQuantity, $max)) {
            $checkoutResponseTransfer->addError(
                $this->createCheckoutErrorTransfer(
                    static::ERROR_QUANTITY_MAX_NOT_FULFILLED,
                    [static::GLOSSARY_KEY_PARAMETER_SKU => $itemTransfer->getSkuOrFail()],
                    $itemTransfer,
                ),
            );
        }

        return $checkoutResponseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return array<int> Keys are product group keys, values are product quantities as 'quote.quantity + change.quantity'
     */
    protected function getItemAddCartQuantityMap(CartChangeTransfer $cartChangeTransfer): array
    {
        $quoteQuantityMapByGroupKey = $this->getQuoteQuantityMap($cartChangeTransfer);

        $cartQuantityMap = [];
        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $productGroupKey = $itemTransfer->getGroupKey() ?? $itemTransfer->getSku();
            $cartQuantityMap[$productGroupKey] = $itemTransfer->getQuantity();

            if (isset($quoteQuantityMapByGroupKey[$productGroupKey])) {
                $cartQuantityMap[$productGroupKey] += $quoteQuantityMapByGroupKey[$productGroupKey];
            }
        }

        /** @phpstan-var array<int> */
        return $cartQuantityMap;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return array<int> Keys are product group keys, values are product quantities as 'quote.quantity - change.quantity'
     */
    protected function getItemRemoveCartQuantityMap(CartChangeTransfer $cartChangeTransfer): array
    {
        $quoteQuantityMapByGroupKey = $this->getQuoteQuantityMap($cartChangeTransfer);

        $cartQuantityMap = [];
        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $productGroupKey = $itemTransfer->getGroupKey() ?? $itemTransfer->getSku();
            $cartQuantityMap[$productGroupKey] = -$itemTransfer->getQuantity();

            if (isset($quoteQuantityMapByGroupKey[$productGroupKey])) {
                $cartQuantityMap[$productGroupKey] += $quoteQuantityMapByGroupKey[$productGroupKey];
            }
        }

        return $cartQuantityMap;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return array<string, int>
     */
    protected function getQuoteQuantityMap(CartChangeTransfer $cartChangeTransfer): array
    {
        $quoteQuantityMap = [];
        foreach ($cartChangeTransfer->getQuote()->getItems() as $itemTransfer) {
            $quoteQuantityMap[$itemTransfer->getGroupKey()] = $itemTransfer->getQuantity();
        }

        return $quoteQuantityMap;
    }

    /**
     * @param \ArrayObject<int, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return array<string, int>
     */
    protected function getItemQuantitiesIndexedBySku(ArrayObject $itemTransfers): array
    {
        $itemQuantitiesIndexedBySku = [];
        foreach ($itemTransfers as $itemTransfer) {
            if (array_key_exists($itemTransfer->getSku(), $itemQuantitiesIndexedBySku)) {
                $itemQuantitiesIndexedBySku[$itemTransfer->getSku()] += $itemTransfer->getQuantity();

                continue;
            }

            $itemQuantitiesIndexedBySku[$itemTransfer->getSku()] = $itemTransfer->getQuantity();
        }

        return $itemQuantitiesIndexedBySku;
    }

    /**
     * @param list<string> $skus
     *
     * @return array<\Generated\Shared\Transfer\ProductQuantityTransfer> Keys are product SKUs.
     */
    protected function getProductQuantityTransferMap(array $skus): array
    {
        $productQuantityTransfers = $this->productQuantityReader->findProductQuantityTransfersByProductSku($skus);

        $productQuantityTransferMap = $this->mapProductQuantityTransfersBySku($productQuantityTransfers);
        $productQuantityTransferMap = $this->replaceMissingSkus($productQuantityTransferMap, $skus);

        return $productQuantityTransferMap;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return array<string> Keys are group keys, values are skus
     */
    protected function getChangedSkuMap(CartChangeTransfer $cartChangeTransfer)
    {
        $skuMap = [];
        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $skuMap[$itemTransfer->getGroupKey() ?? $itemTransfer->getSku()] = $itemTransfer->getSku();
        }

        return $skuMap;
    }

    protected function getDefaultProductQuantityTransfer(): ProductQuantityTransfer
    {
        return (new ProductQuantityTransfer())
            ->setQuantityInterval(1)
            ->setQuantityMin(1);
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductQuantityTransfer> $productQuantityTransferMap
     * @param array<string> $requiredSkus
     *
     * @return array<\Generated\Shared\Transfer\ProductQuantityTransfer>
     */
    protected function replaceMissingSkus(array $productQuantityTransferMap, array $requiredSkus): array
    {
        $defaultProductQuantityTransfer = $this->getDefaultProductQuantityTransfer();

        foreach ($requiredSkus as $sku) {
            if (isset($productQuantityTransferMap[$sku])) {
                continue;
            }

            $productQuantityTransferMap[$sku] = $defaultProductQuantityTransfer;
        }

        return $productQuantityTransferMap;
    }

    /**
     * @param array<\Generated\Shared\Transfer\ProductQuantityTransfer> $productQuantityTransfers
     *
     * @return array<\Generated\Shared\Transfer\ProductQuantityTransfer>
     */
    protected function mapProductQuantityTransfersBySku(array $productQuantityTransfers): array
    {
        $productQuantityTransferMap = [];
        foreach ($productQuantityTransfers as $productQuantityTransfer) {
            $productQuantityTransferMap[$productQuantityTransfer->getProduct()->getSku()] = $productQuantityTransfer;
        }

        return $productQuantityTransferMap;
    }

    /**
     * @param \ArrayObject<array-key, \Generated\Shared\Transfer\ItemTransfer> $itemTransfers
     *
     * @return list<string>
     */
    protected function extractItemSkus(ArrayObject $itemTransfers): array
    {
        $itemSkus = [];
        foreach ($itemTransfers as $itemTransfer) {
            $itemSkus[] = $itemTransfer->getSkuOrFail();
        }

        return $itemSkus;
    }

    /**
     * @param string $sku
     * @param string|float|int $quantity
     * @param \Generated\Shared\Transfer\CartPreCheckResponseTransfer $responseTransfer
     *
     * @return bool
     */
    protected function validateQuantityIsPositiveInteger(string $sku, $quantity, CartPreCheckResponseTransfer $responseTransfer): bool
    {
        if (!$this->isQuantityPositiveInteger($quantity)) {
            $this->addViolation(static::ERROR_QUANTITY_INCORRECT, $sku, 1, $quantity, $responseTransfer);

            return false;
        }

        return true;
    }

    /**
     * @param string $sku
     * @param string|float|int $quantity
     * @param \Generated\Shared\Transfer\CartPreCheckResponseTransfer $responseTransfer
     *
     * @return bool
     */
    protected function validateQuantityIsNonNegativeInteger(string $sku, $quantity, CartPreCheckResponseTransfer $responseTransfer): bool
    {
        if ($quantity < 0 || !ctype_digit((string)$quantity)) {
            $this->addViolation(static::ERROR_QUANTITY_INCORRECT, $sku, 1, $quantity, $responseTransfer);

            return false;
        }

        return true;
    }

    /**
     * @param string $message
     * @param string $sku
     * @param int $restrictionValue
     * @param string|float|int $actualValue
     * @param \Generated\Shared\Transfer\CartPreCheckResponseTransfer $responseTransfer
     *
     * @return void
     */
    protected function addViolation(string $message, string $sku, int $restrictionValue, $actualValue, CartPreCheckResponseTransfer $responseTransfer): void
    {
        $responseTransfer->setIsSuccess(false);
        $responseTransfer->addMessage(
            (new MessageTransfer())
                ->setValue($message)
                ->setParameters([static::GLOSSARY_KEY_PARAMETER_SKU => $sku, '%restrictionValue%' => $restrictionValue, '%actualValue%' => $actualValue]),
        );
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function createCheckoutErrorTransfer(string $message, array $parameters, ItemTransfer $itemTransfer): CheckoutErrorTransfer
    {
        return (new CheckoutErrorTransfer())
            ->setMessage($message)
            ->setParameters($parameters)
            ->setErrorType($this->productQuantityConfig->getCheckoutErrorType())
            ->setGroupKey($itemTransfer->getGroupKey());
    }

    /**
     * @param string|float|int $quantity
     *
     * @return bool
     */
    protected function isQuantityPositiveInteger(float|int|string $quantity): bool
    {
        return $quantity > 0 && ctype_digit((string)$quantity);
    }

    protected function isMinQuantityFulfilled(int $quantity, ?int $min): bool
    {
        return $quantity === 0 || $quantity >= $min;
    }

    protected function isQuantityIntervalFulfilled(int $quantity, ?int $min, ?int $interval): bool
    {
        return $quantity === 0 || ($quantity - $min) % $interval === 0;
    }

    protected function isMaxQuantityFulfilled(int $quantity, ?int $max): bool
    {
        return $max === null || $quantity <= $max;
    }
}
