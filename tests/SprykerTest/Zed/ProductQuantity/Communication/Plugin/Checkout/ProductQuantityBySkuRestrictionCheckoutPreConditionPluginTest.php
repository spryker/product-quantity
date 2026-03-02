<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductQuantity\Communication\Plugin\Checkout;

use Codeception\Test\Unit;
use Spryker\Zed\ProductQuantity\Communication\Plugin\Checkout\ProductQuantityBySkuRestrictionCheckoutPreConditionPlugin;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group ProductQuantity
 * @group Communication
 * @group Plugin
 * @group Checkout
 * @group ProductQuantityBySkuRestrictionCheckoutPreConditionPluginTest
 * Add your own group annotations below this line
 */
class ProductQuantityBySkuRestrictionCheckoutPreConditionPluginTest extends Unit
{
    /**
     * @var \SprykerTest\Zed\ProductQuantity\ProductQuantityCommunicationTester
     */
    protected $tester;

    /**
     * @var \Spryker\Zed\ProductQuantity\Communication\Plugin\Checkout\ProductQuantityBySkuRestrictionCheckoutPreConditionPlugin
     */
    protected $productQuantityBySkuRestrictionCheckoutPreConditionPlugin;

    public function setUp(): void
    {
        parent::setUp();

        $this->productQuantityBySkuRestrictionCheckoutPreConditionPlugin = new ProductQuantityBySkuRestrictionCheckoutPreConditionPlugin();
    }

    public function testCheckDoesNotThrowExceptionWhenCalledWithEmptyListOfItems(): void
    {
        // Assign
        $quoteTransfer = $this->tester->createEmptyQuoteTransfer();
        $checkoutResponseTransfer = $this->tester->createEmptyCheckoutResponse();

        // Act
        $this->productQuantityBySkuRestrictionCheckoutPreConditionPlugin->checkCondition($quoteTransfer, $checkoutResponseTransfer);

        // Assert
        $this->assertTrue(true);
    }
}
