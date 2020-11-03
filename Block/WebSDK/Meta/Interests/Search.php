<?php

declare(strict_types=1);

namespace SolveData\Events\Block\WebSDK\Meta\Interests;

use SolveData\Events\Block\WebSDK\Meta\InterestsAbstract;

class Search extends InterestsAbstract
{
    /**
     * Get meta content
     *
     * @return string
     */
    public function getMetaContent(): string
    {
        return $this->getRequest()->getParam('q');
    }
}
