<?php

/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ChoiceAI_Searchcore_Helper_Feedhelper extends ChoiceAI_Searchcore_Helper_Data {

    var $categoryMap;

    var $catlevel1List;

    var $attributeToTypeMap;

    var $_rootCategoryIds = array();

    var $_filters;

    protected function array_match($needle, $haystack) {
        if(!is_array($needle)) {
            return in_array($needle, $haystack);
        }

        foreach($needle as $eachNeedle) {
            if(in_array($eachNeedle, $haystack)) {
                return true;
            }
        }
        return false;
    }

    public function getAllFilterableAttributes(Mage_Core_Model_Website $website) {

        $filterableAttributes = array();
        $stores = $website->getStores();
        $this->tempCategoriesScanned = array();
        foreach($stores as $store) {
            Mage::app()->setCurrentStore($store);
            $categoryId = $store->getRootCategoryId();
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $this->tempCategoriesScanned[] = $categoryId;
            $filterableAttributes = array_merge($filterableAttributes, $this->getFilterableAttributesForCategory($category));
        }
        return array_unique($filterableAttributes);
    }

    public function getFilterableAttributesForCategory($category) {
        if(array_key_exists($category->getId(), $this->tempCategoriesScanned)) {
            return array();
        } else {
            $this->tempCategoriesScanned[] = $category->getId();
        }
        $filterableAttributes = array();
        $layer = Mage::getModel("catalog/layer");
        $layer->setCurrentCategory($category);
        $attributes = $layer->getFilterableAttributes();
        foreach ($attributes as $attribute) {
            $filterableAttributes[] = $attribute->getAttributeCode();
        }
        $childrenCategoryIds = $category->getAllChildren();
        $childrenCategories = Mage::getModel('catalog/category')->getCollection()->addIdFilter($childrenCategoryIds)->load();
        if(!is_null($childrenCategories)) {
            foreach ($childrenCategories as $childrenCategory) {
                $filterableAttributes = array_merge($filterableAttributes, $this->getFilterableAttributesForCategory($childrenCategory));
            }
        }
        return array_unique($filterableAttributes);
    }

    /**
     * function to get Category from the category id,
     * This checks it present in the global array 'categoryMap', if it is not there fetches from db
     * So that once it gets one category, it doesn't make db call again for the same category
     *
     * @param string $category_id
     * @return Mage_Catalog_Model_Category
     */
    public function getCategory($category_id = ""){
        if(!isset($this->categoryMap[$category_id])){
            $category = Mage::getModel('catalog/category')->load($category_id);
            $this->categoryMap[$category_id] = $category;
            $parentCategories = $category->getParentCategories();
            foreach($parentCategories as $parentCategory) {
                $parentCategory = Mage::getModel('catalog/category')->load($parentCategory->getId());
                $this->categoryMap[$parentCategory->getId()] = $parentCategory;
            }
            return $this->categoryMap[$category_id];
        }
        return $this->categoryMap[$category_id];
    }

    public function getRootCategoryIds(Mage_Core_Model_Website $website) {
        if(!array_key_exists($website->getWebsiteId(), $this->_rootCategoryIds)) {
            foreach($website->getStores() as $store) {
                $this->_rootCategoryIds[$website->getWebsiteId()][] = $store->getRootCategoryId();
            }
        }
        return $this->_rootCategoryIds[$website->getWebsiteId()];
    }

    public function getCategoryOnLevel($category_ids, $level) {
        if(!is_array($category_ids)) {
            return array();
        }
        $categoryValues = array();
        foreach($category_ids as $category_id) {
            $category = $this->getCategory($category_id);
            $parentIds = $category->getParentIds();
            if(!is_null($category) && $category->getLevel() == $level) {
                $categoryValues = array_merge($categoryValues, array($category->getName()));
            } else if ($category instanceof Mage_Catalog_Model_Category &&
                is_array($parentIds) &&
                (sizeof($parentIds) >0)) {
                $categoryValues = array_merge($categoryValues, $this->getCategoryOnLevel($parentIds, $level));
            }
        }
        return $categoryValues;
    }

    /**
     * method to get all the attributes
     **/
    public function getAttributeMapping(){
        if(isset($this->attributeToTypeMap)){
            return $this->attributeToTypeMap;
        } else {
            $attributes = Mage::getSingleton('eav/config')
                ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();
            foreach($attributes as $attribute){
                $this->attributeToTypeMap[$attribute->getAttributeCode()] = $attribute->getFrontendInput();
            }
            return $this->attributeToTypeMap;
        }
    }

    public function isAttributePresent($attributeName) {
        $fieldMap = $this->getAttributeMapping();
        return array_key_exists( $attributeName, $fieldMap);
    }

    /**
     * method to get field type of the field
     * @param $attributeName
     * @return string
     */
    public function getFieldType($attributeName){
        $fieldMap = $this->getAttributeMapping();
        if(array_key_exists( $attributeName, $fieldMap)){
            return $fieldMap[$attributeName];
        } else {
            return "text";
        }
    }

    function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }

    /*
     * function to check whether the field is a multiSelect/select or not,
     * This is optimized method, where it doesn't make a database call to get fieldType
     * where it fetches from the local variable, which holds the information of field to fieldType mapping
     * @param string $attributeName
     * @return bool
     */
    public function isMultiSelect($attributeName = ""){
        if(!$this->excludeMultiSelectList($attributeName)) {
            return false;
        }
        if( $this->isMultiSelectDatatype($attributeName)||
            $attributeName == ChoiceAI_Searchcore_Model_Resource_Field::CATEGORY_IDS ||
            $attributeName == ChoiceAI_Searchcore_Model_Resource_Field::CATEGORY_IDS_NAME ||
            $attributeName == ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_1_NAME ||
            $attributeName == ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_2_NAME ||
            $attributeName == ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_3_NAME ||
            $this->endsWith($attributeName, 'Associated')){
            return true;
        }
        return false;
    }

    public function excludeMultiSelectList($attributeName = "") {
        if($attributeName == "status" || $attributeName == "visibility" || $attributeName == "entity_id" ){
            return false;
        }
        return true;
    }


    public function isMultiSelectDatatype($attributeName = "") {
        if(!$this->excludeMultiSelectList($attributeName)) {
            return false;
        }

        if($this->getFieldType($attributeName) == "select" ||
            $this->getFieldType($attributeName) == "multiselect") {
            return true;
        }
        return false;
    }

    public function isImage($attributeName = "") {
        if($this->getFieldType($attributeName) == "media_image") {
            return true;
        }
        return false;
    }

    /**
     * Get all root categories used by all stores.
     * Note that root categories defined but not used, are not included.
     *
     * @return Mage_Catalog_Model_Category[]
     */
    public function getAllRootCategories()
    {
        $categories = array();

        /** @var $stores Mage_Searchcore_Model_Store[] */
        $stores = Mage::app()->getStores();

        foreach ($stores as $store) {
            $id = $store->getRootCategoryId();
            if (!isset($categories[$id])) {
                $categories[$id] = Mage::getModel('catalog/category')->load($id);
            }
        }

        return $categories;
    }

    public function getUniqueId($product, $item=null) {
        $type= null;
        if($product->hasData('type_id')) {
            $type = $product->getData('type_id');
        }

        switch($type){
            case 'configurable':
                $productId = $product->getData('entity_id');
                break;
            case 'grouped':
                $productId = $product->getData('entity_id');
                break;
            case 'bundle':
                $productId = $product->getData('entity_id');
                break;
            case 'simple':
                if ($item != null && $item instanceof Mage_Sales_Model_Order) {
			if($item->getParentItem() != null)	{
                    		$productId = $item->getParentItem()->getProductId();
				return $productId;
			}
                }
		$parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
		if(!isset($parentIds) || sizeof($parentIds) == 0) {
			$parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
			if(isset($parentIds) && sizeof($parentIds) > 0) {
				return $parentIds[0];
			}
                } else {
			return $parentIds[0];
		}
                $productId = $product->getData('entity_id');
                break;
            default:
                $productId = $product->getData('entity_id');
        }
        return $productId;
    }

    /**
     * Method to get the filters that need to be excluded in the website
     * @param Mage_Core_Model_Website $website
     * @return array
     */
    public function getFilters(Mage_Core_Model_Website $website) {
        if(!isset($this->_filters)) {
            $this->_filters = Mage::getResourceModel('choiceai_searchcore/config')->getFilters($website);
        }
        return $this->_filters;
    }

}
