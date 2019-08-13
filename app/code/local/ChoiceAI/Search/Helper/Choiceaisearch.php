<?php

/**
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Search_Helper_ChoiceAIsearch extends ChoiceAI_Search_Helper_Data
{

    public function getEngineConfigData($prefix = '', $website = null)
    {
        return Mage::helper('choiceai_searchcore')->getEngineConfigData($prefix, $website);
    }
}
