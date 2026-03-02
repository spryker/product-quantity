<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductQuantity\Business\Model\Checker;

use Generated\Shared\Transfer\CartChangeTransfer;

class CartChangeTransferNormalizerPreChecker implements CartChangeTransferNormalizerPreCheckerInterface
{
    public function hasNormalizableItems(CartChangeTransfer $cartChangeTransfer, array $normalizableFields): bool
    {
        foreach ($normalizableFields as $normalizableField) {
            foreach ($cartChangeTransfer->getItems() as $itemTransfer) {
                $itemTransferNormalizableFields = $itemTransfer->getNormalizableFields();

                if (count($itemTransferNormalizableFields) > 0 && in_array($normalizableField, $itemTransferNormalizableFields)) {
                    return true;
                }
            }
        }

        return false;
    }
}
