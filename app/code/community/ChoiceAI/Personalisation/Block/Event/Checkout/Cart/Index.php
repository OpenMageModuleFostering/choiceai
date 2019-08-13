<?php

/**
 * @category    ChoiceAI
 * @package     ChoiceAI_Personalisation
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Personalisation_Block_Event_Checkout_Cart_Index extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('choiceai/personalisation/event/checkout/cart/index.phtml');
    }

    public function getProductToShoppingCart()
    {
        if (($product = Mage::getModel('core/session')->getProductToShoppingCart())) {
            Mage::getModel('core/session')->unsProductToShoppingCart();
            return $product;
        }

        return null;
    }

    protected function _toHtml()
    {
        if (!$this->helper('choiceai_personalisation')->isModuleOutputEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }

}
