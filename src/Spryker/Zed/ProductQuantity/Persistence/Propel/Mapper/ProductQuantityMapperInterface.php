<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductQuantity\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\ProductQuantityTransfer;
use Generated\Shared\Transfer\SpyProductQuantityEntityTransfer;

interface ProductQuantityMapperInterface
{
    public function mapProductQuantityTransfer(
        SpyProductQuantityEntityTransfer $productQuantityEntityTransfer,
        ProductQuantityTransfer $productQuantityTransfer
    ): ProductQuantityTransfer;
}
