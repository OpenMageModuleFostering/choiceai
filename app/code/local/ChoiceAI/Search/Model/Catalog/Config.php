<?php
/**
 * Overrides default layer model to handle custom product collection filtering.
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Search_Model_Catalog_Config extends Mage_Catalog_Model_Config
{
    const IS_ACTIVE = 'choiceai_personalisation/settings/active';
    const CONFIG_KEY = 'choiceai_personalisation/settings/config';

    public $options = NULL;

    /**
     * Returns what sort by options are used by Magento, whether in listing or search
     *
     * Note by Harkirat: This function is called in both cases: product list and search
     *
     * @return array
     */
    public function getAttributeUsedForSortByArray()
    {
        try {
            if(is_null($this->options)) {
                // Check if URL in takeover list
                $STORE_CONFIG = json_decode(Mage::getStoreConfig(self::CONFIG_KEY));
                $sortbyObjs = $STORE_CONFIG->sortby;

                // Getting URL Path
                $currentReqPath = explode("?", $_SERVER['REQUEST_URI'])[0];

                // Getting param keys in array var $paramPairs
                parse_str($_SERVER['QUERY_STRING'], $paramPairs);
                // Getting query keys
                $currentReqParams = array();
                if (count($paramPairs)) {
                    foreach ($paramPairs as $key => $paramPair)
                        $currentReqParams[] = $key;
                }

                // Refining URL path
                // My own local installation case: localhost/magento/
                if (strpos($currentReqPath, "/magento/") !== false)
                    $currentReqPath = str_replace("/magento/", "/", $currentReqPath);

                // Making www.store.com/index.php/abcd => www.store.com/abcd
                if (strpos($currentReqPath, "/index.php/") !== false)
                    $currentReqPath = str_replace("/index.php/", "/", $currentReqPath);


                if (Mage::getStoreConfig(self::IS_ACTIVE) == '1') {
                    foreach ($sortbyObjs as $sortbyObj) {
                        if (isset($sortbyObj->rule)) {
                            if (isset($sortbyObj->rule->paths)) {
                                if (in_array($currentReqPath, $sortbyObj->rule->paths)) {
                                    $this->options = $this->getOptions($sortbyObj);

                                    if (empty($this->options)) {
                                        return parent::getAttributeUsedForSortByArray();
                                    }
                                    return $this->options;
                                }
                            }

                            if (isset($sortbyObj->rule->params)) {
                                if (!empty(array_intersect($currentReqParams, $sortbyObj->rule->params))) {
                                    $this->options = $this->getOptions($sortbyObj);

                                    if (empty($this->options)) {
                                        return parent::getAttributeUsedForSortByArray();
                                    }
                                    return $this->options;
                                }
                            }
                        }
                    }
                }
            } else{
                return $this->options;
            }

            $this->options = parent::getAttributeUsedForSortByArray();
            return $this->options;
        } catch (Exception $e){
            return parent::getAttributeUsedForSortByArray();
        }
    }

    private function getOptions($sortbyObj){
        $options = array();

        if(isset($sortbyObj->override)){
            // Search case, override all system options with choice
            $caiOptions = $sortbyObj->override;

            foreach ($caiOptions as $key => $caiOption)
                $options[$key] = Mage::helper('catalog')->__($caiOption);
        } else if(isset($sortbyObj->extend)) {
            // Product list page, add system options first, then add/replace ours

            $options = array(
                'position'  => Mage::helper('catalog')->__('Position')
            );
            foreach ($this->getAttributesUsedForSortBy() as $attribute) {
                /* @var $attribute Mage_Eav_Model_Entity_Attribute_Abstract */
                $options[$attribute->getAttributeCode()] = $attribute->getStoreLabel();
            }

            // Default options added above, now adding our option/s, which can either override or be a new option
            // If we add more overrides in future, this will still work
            foreach ($sortbyObj->extend as $key=>$option)
                $options[$key] = Mage::helper('catalog')->__($option);

            $_SESSION['plist_sort_by'] = $key;
        }

        return $options;
    }
}
