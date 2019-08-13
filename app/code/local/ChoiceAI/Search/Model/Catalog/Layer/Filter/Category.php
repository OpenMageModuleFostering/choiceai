<?php
/**
 * Handles category filtering in layered navigation.
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Search_Model_Catalog_Layer_Filter_Category extends Mage_Catalog_Model_Layer_Filter_Category
{
    /**
     * Adds category filter to product collection.
     *
     * @param Mage_Catalog_Model_Category $category
     * @return ChoiceAI_Search_Model_Catalog_Layer_Filter_Category
     */
    public function addCategoryFilter($category)
    {
        $value = array(
            'categories' => $category->getId()
        );
        $this->getLayer()->getProductCollection()
            ->addFqFilter($value);

        return $this;
    }

    /**
     * Adds facet condition to product collection.
     *
     * @see ChoiceAI_Search_Model_Resource_Catalog_Product_Collection::addFacetCondition()
     * @return ChoiceAI_Search_Model_Catalog_Layer_Filter_Category
     */
    public function addFacetCondition()
    {
        /** @var $category Mage_Catalog_Model_Category */
        $category = $this->getCategory();
        $childrenCategories = $category->getChildrenCategories();

        $useFlat = (bool) Mage::getStoreConfig('catalog/frontend/flat_catalog_category');
        $categories = ($useFlat)
            ? array_keys($childrenCategories)
            : array_keys($childrenCategories->toArray());

        $this->getLayer()->getProductCollection()->addFacetCondition('categories', $categories);

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

        $param = array("category" => array($value));
        $this->getLayer()
            ->getProductCollection()
            ->addSearchQfFilter($param);

        return $this;
    }

    /**
     * Retrieves request parameter and applies it to product collection.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Mage_Core_Block_Abstract $filterBlock
     * @return ChoiceAI_Search_Model_Catalog_Layer_Filter_Category
     */
    public function apply(Zend_Controller_Request_Abstract $request, $filterBlock)
    {
        $filter = $request->getParam($this->getRequestVar());
        if (is_null($filter) || $filter == "") {
            return $this;
        }
        $this->applyFilterToCollection($this, $filter);
        $this->getLayer()->getState()->addFilter($this->_createItem($filter, $filter));
        $this->_items = null;
        return $this;
    }

    /**
     * Retrieves current items data.
     *
     * @return array
     */
    protected function _getItemsData()
    {
        $layer = $this->getLayer();
            /** @var $productCollection ChoiceAI_Search_Model_Resource_Catalog_Product_Collection */
            $productCollection = $layer->getProductCollection();
            $facets = $productCollection->getFacetedData('category');
            $data = array();
	     if (array_sum($facets) > 0) {
                    $options = array();
                    foreach ($facets as $label => $count) {
                        $options[] = array(
                            'label' => $label,
                            'value' => $label,
                            'count' => $count,
                        );
                    }
        //        }
	      foreach ($options as $option) {
                    if (is_array($option['value']) || !Mage::helper('core/string')->strlen($option['value'])) {
                        continue;
                    }
                    $count = 0;
                    $label = $option['label'];
                    if (isset($facets[$option['value']])) {
                        $count = (int) $facets[$option['value']];
                    }
                    if (!$count && $this->_getIsFilterableAttribute($attribute) == self::OPTIONS_ONLY_WITH_RESULTS) {
                        continue;
                    } 
                    $data[] = array(
                        'label' => $label,
                        'value' => $option['value'],
                        'count' => (int) $count,
                    );
                }

	        }

        return $data;
    }
}
