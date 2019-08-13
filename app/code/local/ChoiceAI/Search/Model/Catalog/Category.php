<?php
/**
 * Created by PhpStorm.
 * User: harkirat
 * Date: 12/5/17
 * Time: 11:25 PM
 */

class ChoiceAI_Search_Model_Catalog_Category extends Mage_Catalog_Model_Category
{
    const IS_ACTIVE = 'choiceai_personalisation/settings/active';
    const CONFIG_KEY = 'choiceai_personalisation/settings/config';

    /**
     * Retrieve Available Product Listing  Sort By
     * code as key, value - name
     *
     * @return array
     */
    public function getAvailableSortByOptions()
    {
        $overtakeFlag = false;

        // Check if URL in takeover list
        $STORE_CONFIG = json_decode(Mage::getStoreConfig(self::CONFIG_KEY));
        $sortbyObjs = $STORE_CONFIG->sortby;

//      Getting URL Path
        $currentReqPath = explode("?", $_SERVER['REQUEST_URI'])[0];

//      Getting param keys in array var $paramPairs
        parse_str($_SERVER['QUERY_STRING'], $paramPairs);
//      Getting query keys
        $currentReqParams = array();
        if (count($paramPairs)) {
            foreach ($paramPairs as $key => $paramPair)
                $currentReqParams[] = $key;
        }

//      Refining URL path
//      My own local installation case: localhost/magento/
        if (strpos($currentReqPath, "/magento/") !== false)
            $currentReqPath = str_replace("/magento/", "/", $currentReqPath);

//      Making www.store.com/index.php/abcd => www.store.com/abcd
        if (strpos($currentReqPath, "/index.php/") !== false)
            $currentReqPath = str_replace("/index.php/", "/", $currentReqPath);

        $isPluginActive = Mage::helper('choiceai_search')->isActiveEngine();
//        $isPluginActive = true;

        if($isPluginActive) {
            foreach ($sortbyObjs as $sortbyObj) {
                if (isset($sortbyObj->rule->paths)) {
                    if (in_array($currentReqPath, $sortbyObj->rule->paths)) {
                        $overtakeFlag = true;
                    }
                }

                if (isset($sortbyObj->rule->params)) {
                    if (!empty(array_intersect($currentReqParams, $sortbyObj->rule->params))) {
                        $overtakeFlag = true;
                    }
                }
            }
        }

        if($overtakeFlag)
            return Mage::getSingleton('catalog/config')->getAttributeUsedForSortByArray();
        else
            return parent::getAvailableSortByOptions();
    }
}