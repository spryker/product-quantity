<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductQuantity;

use ArrayObject;
use Codeception\Actor;
use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CheckoutResponseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductConcreteTransfer;
use Generated\Shared\Transfer\ProductQuantityTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

/**
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class ProductQuantityCommunicationTester extends Actor
{
    use _generated\ProductQuantityCommunicationTesterActions;

    /**
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function createEmptyCartChangeTransfer(): CartChangeTransfer
    {
        return (new CartChangeTransfer())
            ->setQuote(
                (new QuoteTransfer())
                    ->setItems(new ArrayObject([])),
            )
            ->setItems(new ArrayObject([]));
    }

    /**
     * @param int|null $min
     * @param int|null $max
     * @param int|null $interval
     *
     * @return \Generated\Shared\Transfer\ProductConcreteTransfer
     */
    public function createProductWithSpecificProductQuantity(?int $min, ?int $max, ?int $interval): ProductConcreteTransfer
    {
        $productQuantityOverride = [
            ProductQuantityTransfer::QUANTITY_INTERVAL => $interval,
            ProductQuantityTransfer::QUANTITY_MIN => $min,
            ProductQuantityTransfer::QUANTITY_MAX => $max,
        ];

        $productTransfer = $this->haveProduct();
        $this->haveProductQuantity($productTransfer->getIdProductConcrete(), $productQuantityOverride);

        return $productTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     * @param string $sku
     * @param int $quantity
     * @param string|null $groupKey
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function addSkuToCartChangeTransferQuote(
        CartChangeTransfer $cartChangeTransfer,
        string $sku,
        int $quantity,
        ?string $groupKey = null
    ): CartChangeTransfer {
        $cartChangeTransfer->getQuote()->addItem(
            (new ItemTransfer())
                ->setSku($sku)
                ->setGroupKey($groupKey ?: $sku)
                ->setQuantity($quantity),
        );

        return $cartChangeTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     * @param string $sku
     * @param string|float|int $quantity
     * @param string|null $groupKey
     *
     * @return \Generated\Shared\Transfer\CartChangeTransfer
     */
    public function addSkuToCartChangeTransfer(CartChangeTransfer $cartChangeTransfer, string $sku, $quantity, ?string $groupKey = null): CartChangeTransfer
    {
        $itemTransfer = (new ItemTransfer())
            ->setSku($sku)
            ->setQuantity($quantity);

        if ($groupKey) {
            $itemTransfer->setGroupKey($groupKey);
        }

        $cartChangeTransfer->addItem($itemTransfer);

        return $cartChangeTransfer;
    }

    public function createEmptyQuoteTransfer(): QuoteTransfer
    {
        return (new QuoteTransfer())
            ->setItems(new ArrayObject([]));
    }

    public function createEmptyCheckoutResponse(): CheckoutResponseTransfer
    {
        return (new CheckoutResponseTransfer())
            ->setIsSuccess(true);
    }
}
