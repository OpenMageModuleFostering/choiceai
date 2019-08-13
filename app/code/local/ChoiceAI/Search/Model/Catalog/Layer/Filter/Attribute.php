<?php

/**
 * Handles attribute filtering in layered navigation.
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Search_Model_Catalog_Layer_Filter_Attribute extends Mage_Catalog_Model_Layer_Filter_Attribute
{
    const MULTI_SELECT_FACET_SPLIT = '_';

    /**
     * Adds facet condition to product collection.
     *
     * @see ChoiceAI_Search_Model_Resource_Catalog_Product_Collection::addFacetCondition()
     * @return ChoiceAI_Search_Model_Catalog_Layer_Filter_Attribute
     */
    public function addFacetCondition()
    {
        $this->getLayer()
            ->getProductCollection()
            ->addFacetCondition($this->_getFilterField());

        return $this;
    }

    /**
     * Retrieves request parameter and applies it to product collection.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Mage_Core_Block_Abstract $filterBlock
     * @return ChoiceAI_Search_Model_Catalog_Layer_Filter_Attribute
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $filter = $request->getParam($this->_requestVar);
        if (is_array($filter) || null === $filter || strlen($filter) == 0) {
            return $this;
        }

        $filterValues = explode(self::MULTI_SELECT_FACET_SPLIT, $filter);
        $this->applyFilterToCollection($this, $filterValues);
        foreach ($filterValues as $eachFilterValue) {
            $this->getLayer()->getState()->addFilter($this->_createItem($eachFilterValue, $eachFilterValue));
        }

        $this->_items = null;

        return $this;
    }

    /**
     * Applies filter to product collection.
     *
     * @param $filter
     * @param $value
     * @return ChoiceAI_Search_Model_Catalog_Layer_Filter_Attribute
     */
    public function applyFilterToCollection($filter, $value)
    {
        if (!is_array($value)) {
            return $this;
        }

        $attribute = $filter->getAttributeModel();
        $param = Mage::helper('choiceai_search')->getSearchParam($attribute, $value);

        $this->getLayer()
            ->getProductCollection()
            ->addSearchQfFilter($param);

        return $this;
    }

    /**
     * Returns facets data of current attribute.
     *
     * @return array
     */
    protected function _getFacets()
    {
        /** @var $productCollection ChoiceAI_Search_Model_Resource_Catalog_Product_Collection */
        $productCollection = $this->getLayer()->getProductCollection();
        $fieldName = $this->_getFilterField();
        $facets = $productCollection->getFacetedData($fieldName);
        return $facets;
    }


    public function getMaxPriceInt()
    {
        $priceStat = Mage::getSingleton('choiceai_search/catalog_layer')->getProductCollection()->getStats('price');
        $productCollection = $this->getLayer()->getProductCollection();
        return isset($priceStat["max"]) ? $priceStat["max"] : 0;
    }


    /**
     * Returns attribute field name.
     *
     * @return string
     */
    protected function _getFilterField()
    {
        /** @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
        $attribute = $this->getAttributeModel();
        $fieldName = Mage::helper('choiceai_search')->getAttributeFieldName($attribute);

        return $fieldName;
    }

    /**
     * Retrieves current items data.
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $params = Mage::app()->getRequest()->getParams();
        $filter = array_key_exists($this->_requestVar, $params) ? $params[$this->_requestVar] : '';
        $filterValues = explode(self::MULTI_SELECT_FACET_SPLIT, $filter);
        /** @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
        $attribute = $this->getAttributeModel();
        $this->_requestVar = $attribute->getAttributeCode();
        $facets = $this->_getFacets();
        $data = array();

        if (array_sum($facets) > 0) {
            foreach ($facets as $label => $count) {
//                $isSelected = in_array($label, $filterValues);
                if (!$count && $this->_getIsFilterableAttribute($attribute) == self::OPTIONS_ONLY_WITH_RESULTS) {
                    continue;
                }

                $data[] = array(
                    'label' => $label,
                    'value' => $label,
                    'count' => $count,
                );
            }
        }

        return $data;
    }

    /**
     * Returns option label if attribute uses options.
     *
     * @param int $optionId
     * @return bool|int|string
     */
    protected function _getOptionText($optionId)
    {
        if ($this->getAttributeModel()->getFrontendInput() == 'text') {
            return $optionId; // not an option id
        }

        return parent::_getOptionText($optionId);
    }

    /**
     * Checks if given filter is valid before being applied to product collection.
     *
     * @param string $filter
     * @return bool
     */
    protected function _isValidFilter($filter)
    {
        return !empty($filter);
    }
}
