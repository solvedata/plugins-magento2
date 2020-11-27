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
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $result = [];

            $product = $this->getProduct();
            $categories = $product->getCategoryIds();

            if (count($categories)>0) {
                foreach($categories as $category){
                    $cat = $objectManager->create('Magento\Catalog\Model\Category')->load($category);
                    $result[] = $cat->getName();
                    $result = array_merge(
                        $result,
                        $this->getMetaKeywordsAsArray($cat->getMetaKeywords())
                    );
                }
            }

            if (!empty($product)) {
                $result = array_merge(
                    $result,
                    $this->getMetaKeywordsAsArray($product->getMetaKeyword())
                );
            }

            $result = self::normalizeTags($result);

            return implode(',', $result);
        } catch (\Throwable $t) {
            return '';
        }
    }

    private static function normalizeTags(array $tags): array
    {
        $tags = array_map(function ($value) { return trim(strtolower($value)); }, $tags);
        $tags = array_filter($tags, function ($value) { return !is_null($value) && $value !== ''; });
        $tags = array_unique($tags);
        sort($tags);

        return $tags;
    }
}
