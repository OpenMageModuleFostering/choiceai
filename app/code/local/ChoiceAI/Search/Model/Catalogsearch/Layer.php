<?php

/**
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ChoiceAI_Search_Model_Catalogsearch_Layer extends Mage_CatalogSearch_Model_Layer
{
    public function getProductCollection()
    {
        $category = $this->getCurrentCategory();
        if (isset($this->_productCollections[$category->getId()])) {
            $collection = $this->_productCollections[$category->getId()];
        } else {
            /** @var $collection ChoiceAI_Search_Model_Resource_Catalog_Product_Collection */
            $collection = Mage::getResourceModel('choiceai_search/engine_choiceaisearch')
                //->getEngine()
                ->getResultCollection()
                ->setStoreId($category->getStoreId())
                ->setQueryType('search')
                ->addFqFilter(array('store_id' => $category->getStoreId()));
            $this->prepareProductCollection($collection);
            $this->_productCollections[$category->getId()] = $collection;
        }

        return $collection;
    }
}
