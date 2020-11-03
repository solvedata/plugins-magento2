<?php

declare(strict_types=1);

namespace SolveData\Events\Block\WebSDK\Meta\Interests;

use SolveData\Events\Block\WebSDK\Meta\InterestsAbstract;

class Product extends InterestsAbstract
{
    /**
     * Get meta content
     *
     * @return string
     */
    public function getMetaContent(): string
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $result = [];

        $product = $this->getProduct();
        $categories = $product->getCategoryIds();

        if (count($categories)>0) {
            foreach($categories as $category){
                $cat = $objectManager->create('Magento\Catalog\Model\Category')->load($category);
                $result[] = $cat->getName();
                $result[] = $cat->getMetaKeywords();
            }
        }

        if (!empty($product)) {
            $result = array_merge(
                $result,
                $this->getMetaKeywordsAsArray($product->getMetaKeyword())
            );
        }

        return implode(',', $result);
    }
}
