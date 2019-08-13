<?php

/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Resource_Config_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     */
    public function _construct()
    {
        $this->_init('choiceai_searchcore/config');
    }


}

?>
