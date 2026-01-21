<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductQuantity\Communication\Plugin\Cart;

use Generated\Shared\Transfer\CartChangeTransfer;
use Generated\Shared\Transfer\CartPreCheckResponseTransfer;
use Spryker\Zed\CartExtension\Dependency\Plugin\CartPreCheckPluginInterface;
use Spryker\Zed\CartExtension\Dependency\Plugin\TerminationAwareCartPreCheckPluginInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;

/**
 * @method \Spryker\Zed\ProductQuantity\Business\ProductQuantityFacadeInterface getFacade()
 * @method \Spryker\Zed\ProductQuantity\Business\ProductQuantityBusinessFactory getBusinessFactory()
 * @method \Spryker\Zed\ProductQuantity\Communication\ProductQuantityCommunicationFactory getFactory()
 * @method \Spryker\Zed\ProductQuantity\ProductQuantityConfig getConfig()
 */
class ProductQuantityBySkuRestrictionCartPreCheckPlugin extends AbstractPlugin implements CartPreCheckPluginInterface, TerminationAwareCartPreCheckPluginInterface
{
    /**
     * {@inheritDoc}
     * - Checks if the quantity is positive.
     * - Identifies and groups Line items with the same product concrete SKU.
     * - Validates product quantities if they fulfill all quantity restriction rules during item addition.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\CartChangeTransfer $cartChangeTransfer
     *
     * @return \Generated\Shared\Transfer\CartPreCheckResponseTransfer
     */
    public function check(CartChangeTransfer $cartChangeTransfer): CartPreCheckResponseTransfer
    {
        return $this->getBusinessFactory()
            ->createProductQuantityRestrictionValidator()
            ->validateItemAdditionBySku($cartChangeTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     *
     * @return bool
     */
    public function terminateOnFailure(): bool
    {
        return true;
    }
}
