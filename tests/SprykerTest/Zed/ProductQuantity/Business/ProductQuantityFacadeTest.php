<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\ProductQuantity\Business;

use Codeception\Test\Unit;
use Spryker\Service\UtilQuantity\UtilQuantityConfig;
use Spryker\Service\UtilQuantity\UtilQuantityService;
use Spryker\Service\UtilQuantity\UtilQuantityServiceFactory;
use Spryker\Zed\Kernel\Container;
use Spryker\Zed\ProductQuantity\Business\ProductQuantityBusinessFactory;
use Spryker\Zed\ProductQuantity\Business\ProductQuantityFacadeInterface;
use Spryker\Zed\ProductQuantity\Dependency\Service\ProductQuantityToUtilQuantityServiceBridge;
use Spryker\Zed\ProductQuantity\ProductQuantityDependencyProvider;

/**
 * Auto-generated group annotations
 * @group SprykerTest
 * @group Zed
 * @group ProductQuantity
 * @group Business
 * @group Facade
 * @group ProductQuantityFacadeTest
 * Add your own group annotations below this line
 */
class ProductQuantityFacadeTest extends Unit
{
    /**
     * @var \SprykerTest\Zed\ProductQuantity\ProductQuantityBusinessTester
     */
    protected $tester;

    /**
     * @var \Spryker\Zed\ProductQuantity\Business\ProductQuantityFacadeInterface|\Spryker\Zed\Kernel\Business\AbstractFacade
     */
    protected $productQuantityFacade;

    /**
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->productQuantityFacade = $this->createProductQuantityFacade();
    }

    /**
     * @return \Spryker\Zed\ProductQuantity\Business\ProductQuantityFacadeInterface
     */
    protected function createProductQuantityFacade(): ProductQuantityFacadeInterface
    {
        $utilQuantityConfigMock = $this->getMockBuilder(UtilQuantityConfig::class)
            ->setMethods([
                'getQuantityRoundingPrecision',
            ])
            ->getMock();
        $utilQuantityConfigMock->method('getQuantityRoundingPrecision')->will($this->returnValue(2));
        $utilQuantityServiceFactory = new UtilQuantityServiceFactory();
        $utilQuantityServiceFactory->setConfig($utilQuantityConfigMock);
        $utilQuantityService = new UtilQuantityService();
        $utilQuantityService->setFactory($utilQuantityServiceFactory);

        $container = new Container();
        $container[ProductQuantityDependencyProvider::SERVICE_UTIL_QUANTITY] = function () use ($utilQuantityService) {
            return new ProductQuantityToUtilQuantityServiceBridge(
                $utilQuantityService
            );
        };
        $productQuantityBusinessFactory = new ProductQuantityBusinessFactory();
        $productQuantityBusinessFactory->setContainer($container);
        $productQuantityFacade = $this->tester->getFacade();
        $productQuantityFacade->setFactory($productQuantityBusinessFactory);

        return $productQuantityFacade;
    }

    /**
     * @dataProvider itemRemovalQuantities
     *
     * @param bool $expectedIsSuccess
     * @param float $quoteQuantity
     * @param float $changeQuantity
     * @param int|null $minRestriction
     * @param int|null $maxRestriction
     * @param int|null $intervalRestriction
     *
     * @return void
     */
    public function testValidateItemRemoveProductQuantityRestrictionsValidatesProductsWithProductQuantityRestrictions(
        $expectedIsSuccess,
        $quoteQuantity,
        $changeQuantity,
        $minRestriction,
        $maxRestriction,
        $intervalRestriction
    ) {
        // Assign
        $productTransfer = $this->tester->createProductWithSpecificProductQuantity($minRestriction, $maxRestriction, $intervalRestriction);

        $cartChangeTransfer = $this->tester->createEmptyCartChangeTransfer();
        if ($quoteQuantity > 0) {
            $this->tester->addSkuToCartChangeTransferQuote($cartChangeTransfer, $productTransfer->getSku(), $quoteQuantity);
        }
        $this->tester->addSkuToCartChangeTransfer($cartChangeTransfer, $productTransfer->getSku(), $changeQuantity);

        // Act
        $cartPreCheckResponseTransfer = $this->productQuantityFacade->validateItemRemoveProductQuantityRestrictions($cartChangeTransfer);
        $actualIsSuccess = $cartPreCheckResponseTransfer->getIsSuccess();

        // Assert
        $this->assertSame($expectedIsSuccess, $actualIsSuccess);
    }

    /**
     * @return array
     */
    public function itemRemovalQuantities()
    {
        return [
            [true, 5, 2, 1, null, 1], // general rule
            [true, 5.5, 2.5, 1, null, 1],
            [true, 5, 2, 3, null, 1], // min equals new quantity
            [true, 5.5, 2.5, 3, null, 1],
            [true, 5, 2, 1, 3,    1], // max equals new quantity
            [true, 5.5, 2.5, 1, 3, 1],
            [true, 5, 2, 1, null, 2], // shifted interval matches new quantity
            [true, 5.5, 2.5, 1, null, 2],
            [true, 5, 2, 0, null, 3], // interval matches new quantity
            [true, 5.5, 2.5, 0, null, 3],
            [true, 5, 2, 3, 3,    3], // min, max, interval matches new quantity
            [true, 5.5, 2.5, 3, 3,    3],
            [true, 5, 5, 2, 4,    3], // can remove all items regardless rules
            [true, 5.5, 5.5, 2, 4,    3],

            [false, 5, 6, 1, null, 1], // general rule
            [false, 5.5, 6.5, 1, null, 1],
            [false, 5, 2, 4, null, 1], // min above new quantity
            [false, 5.5, 2.5, 4, null, 1],
            [false, 5, 2, 1, 2,    1], // max below new quantity
            [false, 5.5, 2.5, 1, 2,    1],
            [false, 5, 2, 1, null, 3], // shifted interval does not match new quantity
            [false, 5.5, 2.5, 1, null, 3],
            [false, 5, 2, 0, null, 2], // interval does not match new quantity
            [false, 5.5, 2.5, 0, null, 2],
            [false, 0, 1, 1, null, 1], // empty quote
            [false, 0, 1.5, 1, null, 1],
        ];
    }

    /**
     * @dataProvider itemRemovalProductsWithoutProductQuantity
     *
     * @param bool $expectedIsSuccess
     * @param float $quoteQuantity
     * @param float $changeQuantity
     *
     * @return void
     */
    public function testValidateItemRemoveProductQuantityRestrictionsValidatesProductsWithoutProductQuantityRestrictions(
        $expectedIsSuccess,
        $quoteQuantity,
        $changeQuantity
    ) {
        // Assign
        $productTransfer = $this->tester->haveProduct();

        $cartChangeTransfer = $this->tester->createEmptyCartChangeTransfer();
        if ($quoteQuantity > 0) {
            $this->tester->addSkuToCartChangeTransferQuote($cartChangeTransfer, $productTransfer->getSku(), $quoteQuantity);
        }
        $this->tester->addSkuToCartChangeTransfer($cartChangeTransfer, $productTransfer->getSku(), $changeQuantity);

        // Act
        $cartPreCheckResponseTransfer = $this->productQuantityFacade->validateItemRemoveProductQuantityRestrictions($cartChangeTransfer);
        $actualIsSuccess = $cartPreCheckResponseTransfer->getIsSuccess();

        // Assert
        $this->assertSame($expectedIsSuccess, $actualIsSuccess);
    }

    /**
     * @return array
     */
    public function itemRemovalProductsWithoutProductQuantity()
    {
        return [
            [true,  5, 4],
            [true,  5.5, 4.5],
            [true,  5, 5],
            [true,  5.5, 5.5],
            [false, 0, 1],
            [false, 0, 1.5],
            [false, 5, 6],
            [false, 5.5, 6.5],
        ];
    }

    /**
     * @dataProvider itemAdditionQuantities
     *
     * @param bool $expectedIsSuccess
     * @param float $quoteQuantity
     * @param float $changeQuantity
     * @param int|null $minRestriction
     * @param int|null $maxRestriction
     * @param int|null $intervalRestriction
     *
     * @return void
     */
    public function testValidateItemAddProductQuantityRestrictionsValidatesProductsWithProductQuantityRestrictions(
        $expectedIsSuccess,
        $quoteQuantity,
        $changeQuantity,
        $minRestriction,
        $maxRestriction,
        $intervalRestriction
    ) {
        // Assign
        $productTransfer = $this->tester->createProductWithSpecificProductQuantity($minRestriction, $maxRestriction, $intervalRestriction);

        $cartChangeTransfer = $this->tester->createEmptyCartChangeTransfer();
        if ($quoteQuantity > 0) {
            $this->tester->addSkuToCartChangeTransferQuote($cartChangeTransfer, $productTransfer->getSku(), $quoteQuantity);
        }
        $this->tester->addSkuToCartChangeTransfer($cartChangeTransfer, $productTransfer->getSku(), $changeQuantity);

        // Act
        $cartPreCheckResponseTransfer = $this->productQuantityFacade->validateItemAddProductQuantityRestrictions($cartChangeTransfer);
        $actualIsSuccess = $cartPreCheckResponseTransfer->getIsSuccess();

        // Assert
        $this->assertSame($expectedIsSuccess, $actualIsSuccess);
    }

    /**
     * @return array
     */
    public function itemAdditionQuantities()
    {
        return [
            [true, 5, 2, 1, null, 1], // general rule
            [true, 5.5, 2.5, 1, null, 1],
            [true, 5, 2, 7, null, 1], // min equals new quantity
            [true, 5.5, 2.5, 8, null, 1],
            [true, 5, 2, 7, 7,    1], // max equals new quantity
            [true, 5.5, 2.5, 7, 8,    1],
            [true, 5, 2, 7, null, 2], // shifted interval matches new quantity
            [true, 5.5, 2.5, 8, null, 2],
            [true, 5, 2, 0, null, 7], // interval matches new quantity
            [true, 5.5, 2.5, 0, null, 8],
            [true, 5, 2, 7, 7,    7], // min, max, interval matches new quantity
            [true, 5.5, 2.5, 8, 8,    8],
            [true, 0, 1, 1, null, 1], // empty quote
            [true, 0, 1.5, 1, null, 1],

            [false, 0, 0, 1, null, 1], // general rule 0 qty
            [false, 0, -4, 1, null, 1], // general rule negative qty
            [false, 0, -4.5, 1, null, 1],
            [false, 5, 2, 8, null, 1], // min above new quantity
            [false, 5.5, 2.3, 8, null, 1],
            [false, 5, 2, 1, 6,    1], // max below new quantity
            [false, 5.5, 2.5, 1, 6,    1],
            [false, 5, 2, 1, null, 4], // shifted interval does not match new quantity
            [false, 5.5, 2.5, 1, null, 4],
            [false, 5, 2, 0, null, 2], // interval does not match new quantity
            [false, 5.5, 2.5, 0, null, 3],
        ];
    }

    /**
     * @dataProvider itemAdditionProductsWithoutProductQuantity
     *
     * @param bool $expectedIsSuccess
     * @param float $quoteQuantity
     * @param float $changeQuantity
     *
     * @return void
     */
    public function testValidateItemAddProductQuantityRestrictionsValidatesProductsWithoutProductQuantityRestrictions(
        $expectedIsSuccess,
        $quoteQuantity,
        $changeQuantity
    ) {
        // Assign
        $productTransfer = $this->tester->haveProduct();

        $cartChangeTransfer = $this->tester->createEmptyCartChangeTransfer();
        if ($quoteQuantity > 0) {
            $this->tester->addSkuToCartChangeTransferQuote($cartChangeTransfer, $productTransfer->getSku(), $quoteQuantity);
        }
        $this->tester->addSkuToCartChangeTransfer($cartChangeTransfer, $productTransfer->getSku(), $changeQuantity);

        // Act
        $cartPreCheckResponseTransfer = $this->productQuantityFacade->validateItemAddProductQuantityRestrictions($cartChangeTransfer);
        $actualIsSuccess = $cartPreCheckResponseTransfer->getIsSuccess();

        // Assert
        $this->assertSame($expectedIsSuccess, $actualIsSuccess);
    }

    /**
     * @return array
     */
    public function itemAdditionProductsWithoutProductQuantity()
    {
        return [
            [true, 0, 1],
            [true, 0, 1.5],
            [true, 2, 4],
            [true, 2.5, 4.5],
            [false, 0, 0],
            [false, 0, -1],
            [false, 0, -1.5],
        ];
    }

    /**
     * @return void
     */
    public function testFindProductQuantityTransfersByProductIdsFindsAllExistingItems()
    {
        // Assign
        $productIds = [
            $this->tester->createProductWithProductQuantity()->getIdProductConcrete(),
            $this->tester->createProductWithProductQuantity()->getIdProductConcrete(),
        ];
        $expectedCount = count($productIds);

        // Act
        $productQuantityTransfers = $this->productQuantityFacade->findProductQuantityTransfersByProductIds($productIds);
        $actualCount = count($productQuantityTransfers);

        // Assert
        $this->assertSame($expectedCount, $actualCount);
    }

    /**
     * @return void
     */
    public function testFindProductQuantityTransfersByProductIdsReturnsEmptyArrayWhenProductsWereNotFound()
    {
        // Assign
        $dummyProductIds = [999999991, 999999992];
        $expectedCount = 0;

        // Act
        $productQuantityTransfers = $this->productQuantityFacade->findProductQuantityTransfersByProductIds($dummyProductIds);
        $actualCount = count($productQuantityTransfers);

        // Assert
        $this->assertSame($expectedCount, $actualCount);
    }
}
