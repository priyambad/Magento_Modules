<?php
/**
 * @author SpadeWorx Team
 * @copyright Copyright (c) 2019 Redington
 * @package Redington_Catalog
 */

namespace Redington\Catalog\Plugin\Helper\CartReservation;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Model\Product as ProductModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Downloadable\Model\Product\Type as Downloadable;
use Plumrocket\CartReservation\Helper\Data;
use Plumrocket\CartReservation\Model\Attribute\Source\Enable;

class Product extends \Plumrocket\CartReservation\Helper\Product
{
     /**
     * Check if reservation is enabled for product
     *
     * @param Product|int $product
     * @param bool $checkParent
     * @return bool|null
     */
    public function reservationEnabled($product, $checkParent = null)
    { 
        if (is_numeric($product)) {
            $product = $this->productRepository->getById($product);
        }

        if (null === $checkParent) {
            $checkParent = $this->isChild($product);
        }

        $enabled = null;
        if (Enable::INHERITED == $product->getData(self::ATTRIBUTE_CODE)
            || null === $product->getData(self::ATTRIBUTE_CODE)
        ) {
            // Check parents for configurable/bundle products.
            if ($checkParent) {
                $parentIds = $this->getParentIdsByChild($product);
                foreach ($parentIds as $parentId) {
                    $_enabled = $this->reservationEnabled($parentId, false);
                    if (null !== $_enabled) {
                        $enabled = $_enabled;
                        break;
                    }
                }
            }

            // Check categories.
            if (null === $enabled) {
                // Get current category.
                if ($category = $product->getCategory()) {
                    $enabled = $this->reservationEnabledByCategory($category);
                } else {
//                    $categories = $product
//                        ->getCategoryCollection()
//                        ->addAttributeToSelect(self::ATTRIBUTE_CODE)
//                        ->addIsActiveFilter();
//
//                    foreach ($categories as $category) {
//                        $_enabled = $this->reservationEnabledByCategory($category);
//                        if (null !== $_enabled) {
//                            $enabled = $_enabled;
//                            break;
//                        }
//                    }
                }
            }
        } else {
            $enabled = Enable::YES == $product->getData(self::ATTRIBUTE_CODE);
        }

        return $enabled;
    }
}
