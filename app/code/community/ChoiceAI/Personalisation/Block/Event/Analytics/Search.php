<?php
/**
 * Created by PhpStorm.
 * User: harkirat
 * Date: 1/7/17
 * Time: 3:37 PM
 */

/**
 * @category    ChoiceAI
 * @package     ChoiceAI_Personalisation
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Personalisation_Block_Event_Analytics_Search extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('choiceai/personalisation/analytics/search.phtml');
    }

    public function engineStatus()
    {
        return Mage::helper('choiceai_search')->isActiveEngine();
    }

}