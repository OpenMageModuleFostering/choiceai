<?php

/**
 * @category    ChoiceAI
 * @package     ChoiceAI_Personalisation
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Personalisation_Block_Event_Catalog_Product_View extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('choiceai/personalisation/event/catalog/product/view.phtml');
    }

    public function getCurrentProduct()
    {
        return Mage::registry('current_product');
    }

    public function getAssociatedProducts($_product)
    {

        if ($_product->getTypeId() == "configurable") {
            $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($_product);
            return $conf->getUsedProductCollection()->addAttributeToSelect(array('id', 'sku', 'price'))
                ->addFilterByRequiredOptions();
        } else {
            return array();
        }

    }

    protected function _toHtml()
    {
        if (!$this->helper('choiceai_personalisation')->isModuleOutputEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }

}
