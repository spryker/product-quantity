<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductQuantity\Business\Model\Normalizer;

use Generated\Shared\Transfer\CartChangeTransfer;

interface CartChangeTransferQuantityNormalizerInterface
{
    public function normalizeCartChangeTransferItems(CartChangeTransfer $cartChangeTransfer): CartChangeTransfer;
}
