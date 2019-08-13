<?php

/**
 * @category    ChoiceAI
 * @package     ChoiceAI_Personalisation
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Personalisation_Block_Searchpage_Addparam extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('choiceai/personalisation/base/addparam.phtml');
    }

    public function engineStatus()
    {
        return Mage::helper('choiceai_search')->isActiveEngine();
    }

}
