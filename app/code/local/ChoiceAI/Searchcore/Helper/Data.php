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
	$value =array();
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


    /**
     * Method to validate the config save allowed
     * @param Mage_Core_Model_Website $website
     * @param $configName
     * @param $origData
     * @return string | null
     */
    public function isConfigSaveAllowed(Mage_Core_Model_Website $website, $configName, $value, $origData = true) {
	if(is_null($value) || is_array($value) || is_object($value)){
		return "Invalid Value given";
	}
	if($configName == ChoiceAI_Searchcore_Helper_Constants::SEARCH_HOSTED_STATUS && $value == ChoiceAI_Searchcore_Helper_Constants::TRUE && $this->isConfigTrue($website, ChoiceAI_Searchcore_Helper_Constants::SEARCH_MOD_STATUS, $origData)) {
		return "Module search is on";
	}
	if($configName == ChoiceAI_Searchcore_Helper_Constants::SEARCH_MOD_STATUS && $value == ChoiceAI_Searchcore_Helper_Constants::TRUE && $this->isConfigTrue($website, ChoiceAI_Searchcore_Helper_Constants::SEARCH_HOSTED_STATUS, $origData)) {
                  return "Hosted search is on";
          }

	return null;
    }


    /**
     * @param Mage_Core_Model_Website $website
     * @param $data
     * @return void
     */
    public function saveConfig(Mage_Core_Model_Website $website, $data) {
        foreach($data as $key => $value) {
            if(!is_null($this->isConfigSaveAllowed($website, $key, $value, true)))
                continue;
            Mage::getConfig()
                ->saveConfig(ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_CONFIG_PREFIX .
                    ChoiceAI_Searchcore_Helper_Constants::CONFIG_SEPARATOR .
                    $key,
                    (string)$value,
                    'websites',
                    (int)$website->getWebsiteId());
        }
    }

    public function isConfigTrue(Mage_Core_Model_Website $website, $configName, $origData = false) {
        $configData = $this->getEngineConfigData($configName, $website, $origData);
        return array_key_exists($configName, $configData) &&
            $configData[$configName] == ChoiceAI_Searchcore_Helper_Constants::TRUE;
    }

   /*
    * returns the choiceai site name
    * @return String
    */
    public function getSiteName(){
        $siteKeyLabel = ChoiceAI_Searchcore_Helper_Constants::SITE_KEY;
        $config = $this->getEngineConfigData($siteKeyLabel);
        if(array_key_exists($siteKeyLabel, $config) && $config[$siteKeyLabel] != "")
            return $config[$siteKeyLabel];
        else {
            $siteKey = Mage::getResourceModel('choiceai_searchcore/config')
                ->getValue(Mage::app()->getWebsite()->getWebsiteId(),
                    $siteKeyLabel);
            if(isset($siteKey) && $siteKey != ''){
                $this->saveConfig(Mage::app()->getWebsite(),
                    array($siteKeyLabel => $siteKey));
                return $siteKey;
            } else {
                if(Mage::getIsDeveloperMode()) {
                    Mage::throwException("ChoiceAI site key is empty");
                } else {
                    Mage::helper('choiceai_searchcore')->log(Zend_Log::ERR, 'ChoiceAI site key is not set');
                    return null;
                }
            }
        }
    }

   /*
    * returns the choiceai api Key
    * @return String
    */
    public function getApiKey(){
        $apiKeyLabel = ChoiceAI_Searchcore_Helper_Constants::API_KEY;
        $config = $this->getEngineConfigData($apiKeyLabel);
        if(array_key_exists($apiKeyLabel, $config) && $config[$apiKeyLabel] != "")
            return $config[$apiKeyLabel];
        else {
            $apiKey = Mage::getResourceModel('choiceai_searchcore/config')
                ->getValue(Mage::app()->getWebsite()->getWebsiteId(), $apiKeyLabel);
            if(isset($apiKey) && $apiKey != ''){
               $this->saveConfig(Mage::app()->getWebsite(),
                    array($apiKeyLabel => $apiKey));
                return $apiKey;
            } else {
                if(Mage::getIsDeveloperMode()) {
                    Mage::throwException("ChoiceAI api key is empty");
                } else {
                    Mage::helper('choiceai_searchcore')->log(Zend_Log::ERR, 'ChoiceAI Api key is not set');
                    return null;
                }
            }
        }
    }

    /*
      * decides which module should be given more priority based on the version
      * @return boolean
      */
     public function isExecutable() {
         if(Mage::helper('core')->isModuleEnabled('ChoiceAI_Recscore')){
                 $otherModuleVersion = floatval(Mage::getConfig()->getModuleConfig("ChoiceAI_Recscore")->version);
                 $currentModuleVersion = floatval(Mage::getConfig()->getModuleConfig("ChoiceAI_Searchcore")->version);
		 if($this->version_compare($otherModuleVersion, $currentModuleVersion) > 0) {
                         return false;
                 } else if($this->version_compare($otherModuleVersion, $currentModuleVersion) == 0) {
                         return false;
                 }

         }
         return true;
     }

     //Compare two sets of versions, where major/minor/etc. releases are separated by dots. 
     //Returns 0 if both are equal, 1 if A > B, and -1 if B < A. 
     public function version_compare($a, $b)
     {   
             $a = explode(".", rtrim($a, ".0")); //Split version into pieces and remove trailing .0 
             $b = explode(".", rtrim($b, ".0")); //Split version into pieces and remove trailing .0 
             foreach ($a as $depth => $aVal) 
             { //Iterate over each piece of A 
                 if (isset($b[$depth])) 
                 { //If B matches A to this depth, compare the values 
                     if ($aVal > $b[$depth]) return 1; //Return A > B 
                     else if ($aVal < $b[$depth]) return -1; //Return B > A 
                     //An equal result is inconclusive at this point 
                 } 
                 else 
                 { //If B does not match A to this depth, then A comes after B in sort order 
                     return 1; //so return A > B 
                 } 
             } 
             //At this point, we know that to the depth that A and B extend to, they are equivalent. 
             //Either the loop ended because A is shorter than B, or both are equal. 
             return (count($a) < count($b)) ? -1 : 0; 
     } 

}
?>
