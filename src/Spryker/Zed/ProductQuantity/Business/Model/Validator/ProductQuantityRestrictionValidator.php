<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductQuantity\Business\Model\Validator;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CartPreCheckResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\ProductQuantityTransfer;
use Spryker\Zed\ProductQuantity\Business\Model\ProductQuantityReaderInterface;

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

    /**
     * @param \Spryker\Zed\ProductQuantity\Business\Model\ProductQuantityReaderInterface $productQuantityReader
     */
    public function __construct(ProductQuantityReaderInterface $productQuantityReader)
    {
        $this->productQuantityReader = $productQuantityReader;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartPreCheckResponseTransfer
     */
    public function validateItemAddition(CartChangeTransfer $cartChangeTransfer): CartPreCheckResponseTransfer
    {
        $responseTransfer = (new CartPreCheckResponseTransfer())->setIsSuccess(true);

        $changedSkuMapByGroupKey = $this->getChangedSkuMap($cartChangeTransfer);
        $itemQuantitiesIndexedBySku = $this->getItemQuantitiesIndexedBySku($cartChangeTransfer);
        $cartQuantityMapByGroupKey = $this->getItemAddCartQuantityMap($cartChangeTransfer);
        $productQuantityTransferMapBySku = $this->getProductQuantityTransferMap($cartChangeTransfer);

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

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartPreCheckResponseTransfer
     */
    public function validateItemRemoval(CartChangeTransfer $cartChangeTransfer): CartPreCheckResponseTransfer
    {
        $responseTransfer = (new CartPreCheckResponseTransfer())->setIsSuccess(true);

        $changedSkuMapByGroupKey = $this->getChangedSkuMap($cartChangeTransfer);
        $itemQuantitiesIndexedBySku = $this->getItemQuantitiesIndexedBySku($cartChangeTransfer);
        $cartQuantityMapByGroupKey = $this->getItemRemoveCartQuantityMap($cartChangeTransfer);
        $productQuantityTransferMap = $this->getProductQuantityTransferMap($cartChangeTransfer);

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

    /**
     * @param string $sku
     * @param int $quantity
     * @param \Generated\Shared\Transfer\ProductQuantityTransfer $productQuantityTransfer
     * @param \Generated\Shared\Transfer\CartPreCheckResponseTransfer $responseTransfer
     *
     * @return void
     */
    protected function validateItem(
        string $sku,
        int $quantity,
        ProductQuantityTransfer $productQuantityTransfer,
        CartPreCheckResponseTransfer $responseTransfer
    ): void {
        $min = $productQuantityTransfer->getQuantityMin();
        $max = $productQuantityTransfer->getQuantityMax();
        $interval = $productQuantityTransfer->getQuantityInterval();

        if ($quantity !== 0 && $quantity < $min) {
            $this->addViolation(static::ERROR_QUANTITY_MIN_NOT_FULFILLED, $sku, $min, $quantity, $responseTransfer);
        }

        if ($quantity !== 0 && ($quantity - $min) % $interval !== 0) {
            $this->addViolation(static::ERROR_QUANTITY_INTERVAL_NOT_FULFILLED, $sku, $interval, $quantity, $responseTransfer);
        }

        if ($max !== null && $quantity > $max) {
            $this->addViolation(static::ERROR_QUANTITY_MAX_NOT_FULFILLED, $sku, $max, $quantity, $responseTransfer);
        }
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
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return array<string, int|string|float>
     */
    protected function getItemQuantitiesIndexedBySku(CartChangeTransfer $cartChangeTransfer): array
    {
        $itemQuantitiesIndexedBySku = [];
        foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
            $itemQuantitiesIndexedBySku[$itemTransfer->getSku()] = $itemTransfer->getQuantity();
        }

        return $itemQuantitiesIndexedBySku;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return array<\Generated\Shared\Transfer\ProductQuantityTransfer> Keys are product SKUs.
     */
    protected function getProductQuantityTransferMap(CartChangeTransfer $cartChangeTransfer): array
    {
        $skus = $this->getChangedSkuMap($cartChangeTransfer);
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

    /**
     * @return \Generated\Shared\Transfer\ProductQuantityTransfer
     */
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
     * @param string $sku
     * @param string|float|int $quantity
     * @param \Generated\Shared\Transfer\CartPreCheckResponseTransfer $responseTransfer
     *
     * @return bool
     */
    protected function validateQuantityIsPositiveInteger(string $sku, $quantity, CartPreCheckResponseTransfer $responseTransfer): bool
    {
        if ($quantity <= 0 || !ctype_digit((string)$quantity)) {
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
                ->setParameters(['%sku%' => $sku, '%restrictionValue%' => $restrictionValue, '%actualValue%' => $actualValue]),
        );
    }
}
