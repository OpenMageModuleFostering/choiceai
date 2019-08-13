<?php
/**
 * Created by PhpStorm.
 * User: Harkirat
 * Date: 12/5/17
 * Time: 12:47 PM
 */

class ChoiceAI_Search_Block_Catalogsearch_Result extends Mage_CatalogSearch_Block_Result {
    const IS_ACTIVE = 'choiceai_personalisation/settings/active';
    /**
     * Set search available list orders
     *
     * @return Mage_CatalogSearch_Block_Result
     */
    public function setListOrders()
    {
        if(Mage::helper('choiceai_search')->isActiveEngine()) {
            $category = Mage::getSingleton('catalog/layer')
                ->getCurrentCategory();
            /* @var $category Mage_Catalog_Model_Category */
            $availableOrders = $category->getAvailableSortByOptions();
            unset($availableOrders['position']);

//          Removed addition of 'Relevance' option here, which is in core functionality
            $this->getListBlock()
                ->setAvailableOrders($availableOrders)
                ->setDefaultDirection('desc');
//                ->setSortBy('relevance');
        }else{
            return parent::setListOrders();
        }

        return $this;
    }
}