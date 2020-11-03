<?php

declare(strict_types=1);

namespace SolveData\Events\Block\WebSDK\Meta\Interests;

use SolveData\Events\Block\WebSDK\Meta\InterestsAbstract;

class Category extends InterestsAbstract
{
    /**
     * Get meta content
     *
     * @return string
     */
    public function getMetaContent(): string
    {
        $result = [];
        $category = $this->getCategory();
        if (empty($category)) {
            return '';
        }
        $result[] = $category->getName();
        $result = array_merge(
            $result,
            $this->getMetaKeywordsAsArray($category->getMetaKeywords())
        );

        return implode(',', $result);
    }
}
