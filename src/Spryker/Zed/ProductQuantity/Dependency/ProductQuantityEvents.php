<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductQuantity\Dependency;

interface ProductQuantityEvents
{
   /**
     * Specification
     * -
     *
     * @api
     */
    const PRODUCT_QUANTITY_PUBLISH = 'ProductQuantity.product_quantity.publish';

    /**
     * Specification
     * -
     *
     * @api
     */
    const PRODUCT_QUANTITY_UNPUBLISH = 'ProductQuantity.product_quantity.unpublish';

    /**
     * Specification
     * -
     *
     * @api
     */
    const ENTITY_SPY_PRODUCT_QUANTITY_CREATE = 'Entity.spy_product_quantity.create';

    /**
     * Specification
     * -
     *
     * @api
     */
    const ENTITY_SPY_PRODUCT_QUANTITY_UPDATE = 'Entity.spy_product_quantity.update';

    /**
     * Specification
     * -
     *
     * @api
     */
    const ENTITY_SPY_PRODUCT_QUANTITY_DELETE = 'Entity.spy_product_quantity.delete';
}
