<?php
/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Resource_Attribute {

	protected $attributeMap = array();
	public function getAttributeValue($attributeCode, $value, $product){
		if(!isset($this->attributeMap[$value])){
			if(!($product instanceof Mage_Catalog_Model_Product) || Mage::getResourceModel('catalog/product')->getAttribute($attributeCode) == null){
                        	return null;
                	}
			$options = Mage::getResourceModel('catalog/product')->getAttribute($attributeCode)
                		->getSource()->getAllOptions();
			foreach($options as $option){
				$this->attributeMap[$option["value"]] = $option["label"];
			}
		}
		return array_key_exists($value, $this->attributeMap)?$this->attributeMap[$value]:null;
	}

}

?>
