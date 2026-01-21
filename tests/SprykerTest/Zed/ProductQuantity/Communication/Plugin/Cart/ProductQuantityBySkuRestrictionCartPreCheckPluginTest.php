<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductQuantity\Communication\Plugin\Cart;

use Codeception\Test\Unit;
use Spryker\Zed\ProductQuantity\Communication\Plugin\Cart\ProductQuantityBySkuRestrictionCartPreCheckPlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductQuantity
 * @group Communication
 * @group Plugin
 * @group Cart
 * @group ProductQuantityBySkuRestrictionCartPreCheckPluginTest
 * Add your own group annotations below this line
 */
class ProductQuantityBySkuRestrictionCartPreCheckPluginTest extends Unit
{
    /**
     * @var \SprykerTest\Zed\ProductQuantity\ProductQuantityCommunicationTester
     */
    protected $tester;

    /**
     * @var \Spryker\Zed\ProductQuantity\Communication\Plugin\Cart\ProductQuantityBySkuRestrictionCartPreCheckPlugin
     */
    protected $productQuantityBySkuRestrictionCartPreCheckPlugin;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->productQuantityBySkuRestrictionCartPreCheckPlugin = new ProductQuantityBySkuRestrictionCartPreCheckPlugin();
    }

    /**
     * @return void
     */
    public function testCheckDoesNotThrowExceptionWhenCalledWithEmptyListOfItems(): void
    {
        // Assign
        $cartChangeTransfer = $this->tester->createEmptyCartChangeTransfer();

        // Act
        $this->productQuantityBySkuRestrictionCartPreCheckPlugin->check($cartChangeTransfer);

        // Assert
        $this->assertTrue(true);
    }

    /**
     * @dataProvider itemAdditionQuantities
     *
     * @param bool $expectedIsSuccess
     * @param int $quoteQuantity
     * @param int $changeQuantity
     * @param int|null $minRestriction
     * @param int|null $maxRestriction
     * @param int|null $intervalRestriction
     *
     * @return void
     */
    public function testProductQuantityBySkuRestrictionCartPreCheckPluginValidatesProductsWithProductQuantityRestrictions(
        bool $expectedIsSuccess,
        int $quoteQuantity,
        int $changeQuantity,
        ?int $minRestriction,
        ?int $maxRestriction,
        ?int $intervalRestriction,
        ?string $groupKey = null,
    ): void {
        // Assign
        $productTransfer = $this->tester->createProductWithSpecificProductQuantity($minRestriction, $maxRestriction, $intervalRestriction);

        $cartChangeTransfer = $this->tester->createEmptyCartChangeTransfer();
        if ($quoteQuantity > 0) {
            $this->tester->addSkuToCartChangeTransferQuote($cartChangeTransfer, $productTransfer->getSku(), $quoteQuantity, $groupKey);
        }
        $this->tester->addSkuToCartChangeTransfer($cartChangeTransfer, $productTransfer->getSku(), $changeQuantity);

        // Act
        $cartPreCheckResponseTransfer = $this->productQuantityBySkuRestrictionCartPreCheckPlugin->check($cartChangeTransfer);
        $actualIsSuccess = $cartPreCheckResponseTransfer->getIsSuccess();

        // Assert
        $this->assertSame($expectedIsSuccess, $actualIsSuccess);
    }

    /**
     * @return array
     */
    public function itemAdditionQuantities(): array
    {
        return [
            [true, 5, 2, 1, null, 1], // general rule
            [true, 5, 2, 7, null, 1], // min equals new quantity
            [true, 5, 2, 7, 7, 1], // max equals new quantity
            [true, 5, 2, 7, null, 2], // shifted interval matches new quantity
            [true, 5, 2, 0, null, 7], // interval matches new quantity
            [true, 5, 2, 7, 7, 7], // min, max, interval matches new quantity
            [true, 0, 1, 1, null, 1], // empty quote

            [true, 5, 2, 7, 10, 7, 'test'], // min, max, max lower than new quantity, group key
            [true, 5, 2, 7, 7, 7, 'test'], // min, max, max matches new quantity, group key

            [false, 0, 0, 1, null, 1], // general rule 0 qty
            [false, 0, -4, 1, null, 1], // general rule negative qty
            [false, 5, 2, 8, null, 1], // min above new quantity
            [false, 5, 2, 1, 6, 1], // max below new quantity
            [false, 5, 2, 1, null, 4], // shifted interval does not match new quantity
            [false, 5, 2, 0, null, 2], // interval does not match new quantity

            [false, 5, 3, 7, 7, 7, 'test'], // min, max, max over new quantity, group key
        ];
    }

    /**
     * @dataProvider itemAdditionProductsWithoutProductQuantity
     *
     * @param bool $expectedIsSuccess
     * @param int $quoteQuantity
     * @param string|float|int $changeQuantity
     *
     * @return void
     */
    public function testProductQuantityBySkuRestrictionCartPreCheckPluginValidatesProductsWithoutProductQuantityRestrictions(
        bool $expectedIsSuccess,
        int $quoteQuantity,
        mixed $changeQuantity,
    ): void {
        // Assign
        $productTransfer = $this->tester->haveProduct();

        $cartChangeTransfer = $this->tester->createEmptyCartChangeTransfer();
        if ($quoteQuantity > 0) {
            $this->tester->addSkuToCartChangeTransferQuote($cartChangeTransfer, $productTransfer->getSku(), $quoteQuantity);
        }
        $this->tester->addSkuToCartChangeTransfer($cartChangeTransfer, $productTransfer->getSku(), $changeQuantity);

        // Act
        $cartPreCheckResponseTransfer = $this->productQuantityBySkuRestrictionCartPreCheckPlugin->check($cartChangeTransfer);
        $actualIsSuccess = $cartPreCheckResponseTransfer->getIsSuccess();

        // Assert
        $this->assertSame($expectedIsSuccess, $actualIsSuccess);
    }

    /**
     * @return array
     */
    public function itemAdditionProductsWithoutProductQuantity(): array
    {
        return [
            [true, 0, 1],
            [true, 2, 4],
            [true, 0, '1'],
            [true, 2, '4'],
            [false, 0, 0],
            [false, 0, -1],
            [false, 2, -3],
            [false, 0, 0.99],
            [false, 1, 0.99],
            [false, 1, '0.5'],
        ];
    }
}
