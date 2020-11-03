<?php

declare(strict_types=1);

namespace SolveData\Events\Block\WebSDK\Meta;

use Magento\Backend\Block\Template;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use SolveData\Events\Model\Config;
use SolveData\Events\Block\WebSDK\MetaAbstract;

abstract class InterestsAbstract extends MetaAbstract
{
    const META_NAME = 'solve:interests';

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;

        parent::__construct(
            $context,
            $config,
            $data
        );
    }

    /**
     * Get category model
     *
     * @return Category
     */
    protected function getCategory()
    {
        if (!$this->hasData('category')) {
            $this->setData('category', $this->registry->registry('current_category'));
        }
        return $this->getData('category');
    }

    /**
     * Get product model
     *
     * @return Product
     */
    protected function getProduct()
    {
        if (!$this->hasData('product')) {
            $this->setData('product', $this->registry->registry('current_product'));
        }
        return $this->getData('product');
    }

    /**
     * Get meta keywords as array
     *
     * @param string $keywords
     *
     * @return array
     */
    public function getMetaKeywordsAsArray($keywords): array
    {
        if (empty($keywords)) {
            return [];
        }

        return explode(',', trim($keywords));
    }
}
