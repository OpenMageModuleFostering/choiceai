<?php

/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Helper_Data extends Mage_Core_Helper_Abstract {

    const LOG_FILE = "choiceai_searchcore.log";

    /**
     * array field which stores the config
     *
     * @var array
     */
    public $engineConfigData = NULL;

    /**
     * Method to log
     *
     * @param int    $level
     * @param string $message
     */
    public function log($level, $message) {
        Mage::log($message, $level, static::LOG_FILE, true);
    }

    /**
     * Returns search engine config data.
     *
     * @param string $prefix
     * @param mixed $store
     * @return array
     */
    public function getEngineConfigData($prefix = '', Mage_Core_Model_Website $website = null, $original = false)
    {
        if(is_null($website)) {
            $website = Mage::app()->getWebsite();
        }
        $originalConfig = array();
        if($original) {
            $originalConfig = $this->getOriginalValue($prefix, $website);
        }
        if(is_null($this->engineConfigData)) {
            $this->engineConfigData = Mage::getConfig()
                ->getNode(ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_CONFIG_PREFIX, 'websites',
                    (int)$website->getWebsiteId());
        }

        if (!$this->engineConfigData) {
            return false;
        }
        if ($this->engineConfigData->hasChildren()) {
            $value = array();
            foreach ($this->engineConfigData->asArray() as $k=>$v) {
                if ($prefix != '' && preg_match("#^{$prefix}(.*)#", $k, $matches)) {
                    $value[$k] = $v;
                }
            }
        } else {
            $value = (string)$this->engineConfigData;
        }
        return array_merge($value,$originalConfig);
    }

    public function getOriginalValue($prefix = '', Mage_Core_Model_Website $website) {
        $configDataCollection = Mage::getModel('core/config_data')
                     ->getCollection()
                     ->addScopeFilter('websites', (int)$website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_CONFIG_PREFIX)
                     ->load();
	    $value = array();
        foreach($configDataCollection as $data) {
           $path = $data->getPath();
            if (substr($path, 0, strlen(ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_CONFIG_PREFIX)) == ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_CONFIG_PREFIX) {
                $path = substr($path, strlen(ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_CONFIG_PREFIX) +1);
            }
            if ($prefix != '' && preg_match("#^{$prefix}(.*)#", $path, $matches)) {
                $value[$path] = $data->getValue();
            }
        }
    	return $value;
    }

}
?>
