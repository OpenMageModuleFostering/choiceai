<?php
/**
 * Overrides default layer model to handle custom product collection filtering.
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Search_Model_Catalog_Layer extends Mage_Catalog_Model_Layer
{
    /**
     * Returns product collection for current category.
     *
     * @return ChoiceAI_Search_Model_Resource_Catalog_Product_Collection
     */
    public function getProductCollection()
    {
        /** @var $category Mage_Catalog_Model_Category */
        $category = $this->getCurrentCategory();
        /** @var $collection ChoiceAI_Search_Model_Resource_Catalog_Product_Collection */
        if (isset($this->_productCollections[$category->getId()])) {
            $collection = $this->_productCollections[$category->getId()];
        } else {
            $collection = Mage::getResourceModel('choiceai_search/engine_choiceaisearch')
                ->getResultCollection()
                ->setStoreId($category->getStoreId())
                ->addCategoryId($category->getId())
                ->setQueryType('browse')
                ->addFqFilter(array('store_id' => $category->getStoreId()));
                
            $this->prepareProductCollection($collection);
            $this->_productCollections[$category->getId()] = $collection;
        }

        return $collection;
    }
}
