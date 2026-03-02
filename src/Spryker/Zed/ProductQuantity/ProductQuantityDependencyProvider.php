<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductQuantity;

use Spryker\Zed\Kernel\AbstractBundleDependencyProvider;
use Spryker\Zed\Kernel\Container;

/**
 * @method \Spryker\Zed\ProductQuantity\ProductQuantityConfig getConfig()
 */
class ProductQuantityDependencyProvider extends AbstractBundleDependencyProvider
{
    /**
     * @var string
     */
    public const SERVICE_PRODUCT_QUANTITY = 'SERVICE_PRODUCT_QUANTITY';

    public function provideBusinessLayerDependencies(Container $container): Container
    {
        $container = parent::provideBusinessLayerDependencies($container);
        $container = $this->addProductQuantityService($container);

        return $container;
    }

    public function providePersistenceLayerDependencies(Container $container): Container
    {
        $container = parent::providePersistenceLayerDependencies($container);

        return $container;
    }

    protected function addProductQuantityService(Container $container): Container
    {
        $container->set(static::SERVICE_PRODUCT_QUANTITY, function (Container $container) {
            return $container->getLocator()->productQuantity()->service();
        });

        return $container;
    }
}
