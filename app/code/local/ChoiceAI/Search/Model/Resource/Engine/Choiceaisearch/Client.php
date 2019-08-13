<?php

/**
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ChoiceAI_Search_Model_Resource_Engine_ChoiceAIsearch_Client extends ChoiceAI_Client
{

    const CONFIG_API_KEY = 'choiceai_personalisation/settings/api_key';

    public function __construct()
    {
        $config = $this->_getHelper()->getEngineConfigData();
        $config['context'] = Mage::getStoreConfig(self::CONFIG_API_KEY);
        parent::__construct($config);
    }

    protected function _getHelper()
    {
        return Mage::helper('choiceai_search/choiceaisearch');
    }

}

