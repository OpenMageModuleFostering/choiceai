<?php

/**
 * @category    ChoiceAI
 * @package     ChoiceAI_Personalisation
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Personalisation_Block_Base_Script extends Mage_Core_Block_Template {

    protected function _construct() {
        parent::_construct();
        $this->setTemplate('choiceai/personalisation/base/script.phtml');
    }

    protected function _toHtml() {
        if (!$this->helper('choiceai_personalisation')->isModuleOutputEnabled()) {
            return '';
        }
        return parent::_toHtml();
    }

    public function getUser() {
        if (Mage::getSingleton('customer/session')->isLoggedIn()) {
            $user = Mage::getSingleton('customer/session')->getCustomer();
            return $user;
        }
        return null;
    }

}
