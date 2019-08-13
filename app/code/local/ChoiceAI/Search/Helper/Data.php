<?php

/**
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Search_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Allowed languages.
     * Example: array('en_US' => 'en', 'fr_FR' => 'fr')
     *
     * @var array
     */
    protected $_languageCodes = array();

    /**
     * Searchable attributes.
     *
     * @var array
     */
    protected $_searchableAttributes;

    /**
     * Sortable attributes.
     *
     * @var array
     */
    protected $_sortableAttributes;

    /**
     * Text field types.
     *
     * @var array
     */
    protected $_textFieldTypes = array(
        'text',
        'varchar',
    );

    /**
     * Unlocalized field types.
     *
     * @var array
     */
    protected $_unlocalizedFieldTypes = array(
        'datetime',
        'decimal',
    );

    /**
     * Boolean field which stores choiceai active or not
     *
     * @var boolean
     */
    public $isActive = NULL;


    const IS_ACTIVE = 'choiceai_personalisation/settings/active';
    const CONFIG_KEY = 'choiceai_personalisation/settings/config';

    /**
     * Returns attribute field name (localized if needed).
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @param string $localeCode
     * @return string
     */
    public function getAttributeFieldName($attribute, $localeCode = null)
    {
        if (is_string($attribute)) {
            $this->getSearchableAttributes(); // populate searchable attributes if not already set
            if (!isset($this->_searchableAttributes[$attribute])) {
                return $attribute;
            }

            $attribute = $this->_searchableAttributes[$attribute];
        }

        $attributeCode = $attribute->getAttributeCode();
        $backendType = $attribute->getBackendType();

        if ($attributeCode != 'score' && in_array($backendType, $this->_textFieldTypes)) {
            if (null === $localeCode) {
                $localeCode = $this->getLocaleCode();
            }

//            $languageCode = $this->getLanguageCodeByLocaleCode($localeCode);
//            $languageSuffix = "_fq";
            //$attributeCode .= $languageSuffix;
        }

        return $attributeCode;
    }

    /**
     * Returns search engine config data.
     *
     * @param string $prefix
     * @param mixed $store
     * @return array
     */
    public function getEngineConfigData($prefix = '', $website = null)
    {
        return Mage::helper('choiceai_searchcore')->getEngineConfigData($prefix, $website);
    }

    /**
     * Returns EAV config singleton.
     *
     * @return Mage_Eav_Model_Config
     */
    public function getEavConfig()
    {
        return Mage::getSingleton('eav/config');
    }

    /**
     * Returns language code of specified locale code.
     *
     * @param string $localeCode
     * @return bool
     */
    public function getLanguageCodeByLocaleCode($localeCode)
    {
        $localeCode = (string)$localeCode;
        if (!$localeCode) {
            return false;
        }

        if (!isset($this->_languageCodes[$localeCode])) {
            $languages = $this->getSupportedLanguages();
            $this->_languageCodes[$localeCode] = false;
            foreach ($languages as $code => $locales) {
                if (is_array($locales)) {
                    if (in_array($localeCode, $locales)) {
                        $this->_languageCodes[$localeCode] = $code;
                    }
                } elseif ($localeCode == $locales) {
                    $this->_languageCodes[$localeCode] = $code;
                }
            }
        }

        return $this->_languageCodes[$localeCode];
    }

    /**
     * Returns store locale code.
     *
     * @param null $store
     * @return string
     */
    public function getLocaleCode($store = null)
    {
        return Mage::getStoreConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE, $store);
    }

    /**
     * Returns searched parameter as array.
     *
     * @param Mage_Catalog_Model_Resource_Eav_Attribute $attribute
     * @param mixed $value
     * @return array
     */
    public function getSearchParam($attribute, $value)
    {
        if (empty($value) ||
            (isset($value['from']) && empty($value['from']) &&
                isset($value['to']) && empty($value['to']))
        ) {
            return false;
        }

        $field = $this->getAttributeFieldName($attribute);
        $backendType = $attribute->getBackendType();
        if ($backendType == 'datetime') {
            $format = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
            if (is_array($value)) {
                foreach ($value as &$val) {
                    if (!is_empty_date($val)) {
                        $date = new Zend_Date($val, $format);
                        $val = $date->toString(Zend_Date::ISO_8601) . 'Z';
                    }
                }

                unset($val);
            } else {
                if (!is_empty_date($value)) {
                    $date = new Zend_Date($value, $format);
                    $value = $date->toString(Zend_Date::ISO_8601) . 'Z';
                }
            }
        }

        if ($attribute->usesSource()) {
            $attribute->setStoreId(Mage::app()->getStore()->getId());
        }

        return array($field => $value);
    }

    /**
     * Defines supported languages for snowball filter.
     *
     * @return array
     */
    public function getSupportedLanguages()
    {
        $default = array(
            /**
             * SnowBall filter based
             */
            // Danish
            'da' => 'da_DK',
            // Dutch
            'nl' => 'nl_NL',
            // English
            'en' => array('en_AU', 'en_CA', 'en_NZ', 'en_GB', 'en_US'),
            // Finnish
            'fi' => 'fi_FI',
            // French
            'fr' => array('fr_CA', 'fr_FR'),
            // German
            'de' => array('de_DE', 'de_DE', 'de_AT'),
            // Italian
            'it' => array('it_IT', 'it_CH'),
            // Norwegian
            'nb' => array('nb_NO', 'nn_NO'),
            // Portuguese
            'pt' => array('pt_BR', 'pt_PT'),
            // Romanian
            'ro' => 'ro_RO',
            // Russian
            'ru' => 'ru_RU',
            // Spanish
            'es' => array('es_AR', 'es_CL', 'es_CO', 'es_CR', 'es_ES', 'es_MX', 'es_PA', 'es_PE', 'es_VE'),
            // Swedish
            'sv' => 'sv_SE',
            // Turkish
            'tr' => 'tr_TR',

            /**
             * Lucene class based
             */
            // Czech
            'cs' => 'cs_CZ',
            // Greek
            'el' => 'el_GR',
            // Thai
            'th' => 'th_TH',
            // Chinese
            'zh' => array('zh_CN', 'zh_HK', 'zh_TW'),
            // Japanese
            'ja' => 'ja_JP',
            // Korean
            'ko' => 'ko_KR'
        );

        return $default;
    }

    /**
     * Checks if configured engine is active.
     *
     * @return bool
     */
    public function isActiveEngine()
    {
        if ($this->isActive === null) {
            $server = Mage::app()->getRequest()->getServer();
            $storeConfig = json_decode(Mage::getStoreConfig(self::CONFIG_KEY));
            $sortbyObjs = $storeConfig->sortby;
//          Getting URL Path
            $currentReqPath = explode("?", $server['REQUEST_URI'])[0];
            $currentReqPath = rtrim($currentReqPath, "/");
//          Refining URL path
//          My own local installation case: localhost/magento/
            if (strpos($currentReqPath, "/magento/") !== false)
                $currentReqPath = str_replace("/magento/", "/", $currentReqPath);

//          Making www.store.com/index.php/abcd => www.store.com/abcd
            if (strpos($currentReqPath, "/index.php/") !== false)
                $currentReqPath = str_replace("/index.php/", "/", $currentReqPath);
            // Getting param keys in array var $paramPairs
            parse_str($server['QUERY_STRING'], $paramPairs);
            // Getting query keys
            $currentReqParams = array();
            if (!empty($paramPairs)) {
                foreach ($paramPairs as $key => $paramPair)
                    $currentReqParams[] = $key;
            }

            $isPluginActive = Mage::getStoreConfig(self::IS_ACTIVE) == '1';
            $choiceOptions = array();

            if ($isPluginActive) {
                foreach ($sortbyObjs as $sortbyObj) {
                    if (isset($sortbyObj->rule->paths)) {
                        if (in_array($currentReqPath, $sortbyObj->rule->paths)) {
                            $choiceOptions = $this->_getOptionsAddedByChoice($sortbyObj);

                            $order = Mage::app()->getRequest()->getQuery('order');
                            if (empty($choiceOptions))
                                $this->isActive = true;
                            else if (!isset($order) || $order === null)
                                $this->_setDefaultSortOption($sortbyObj, $storeConfig);
                            break;
                        }
                    }

                    if (isset($sortbyObj->rule->params)) {
                        if (!empty(array_intersect($currentReqParams, $sortbyObj->rule->params))) {
                            $choiceOptions = $this->_getOptionsAddedByChoice($sortbyObj);
                            $order = Mage::app()->getRequest()->getQuery('order');
                            if (empty($choiceOptions))
                                $this->isActive = true;
                            else if (!isset($order) || $order === null)
                                $this->_setDefaultSortOption($sortbyObj, $storeConfig);
                            break;
                        }
                    }
                }

                $expId = $sortbyObj->expId;
                if ($expId) {
                    Mage::register('expId', $expId, true);
                    $passback = Mage::getModel('core/cookie')->get("caiexperiment_" . $expId);
                    if ($passback) {
                        Mage::register('passback', $passback, true);
                    }

                    // magento sanitizes . with _ while reading from cookie
                    $contextId = Mage::getModel('core/cookie')->get(
                        "cai_" . str_replace(
                            ".",
                            "_", $currentReqPath
                        ) . $expId
                    );
                    if ($contextId) {
                        Mage::register('contextId', $contextId, true);
                    }
                }

                $server = Mage::app()->getRequest()->getServer();
                $device = $this->getDevice($server['HTTP_USER_AGENT']);
                $order = Mage::app()->getRequest()->getQuery('order');
                if (!empty($choiceOptions) && isset($order) && in_array($order, $choiceOptions)) {
                    if (isset($sortbyObj) && isset($sortbyObj->device)) {
                        if (in_array("all", $sortbyObj->device) || in_array($device, $sortbyObj->device))
                            $this->isActive = true;
                        else
                            $this->isActive = false;
                    } else
                        $this->isActive = false;
                }
            }
        }

        return $this->isActive;
    }

    protected function getDevice($userAgent)
    {
        if (stripos($userAgent, "ipad") !== false) {
            return "desktoptablet";
//            return "tablet";
        } else if (stripos($userAgent, "android") !== false || stripos($userAgent, "mobile") !== false ||
            stripos($userAgent, "iphone") !== false) {
            return "desktoptablet";
//            return "mobile";
        } else
            return 'desktop';
    }

    // If sort order is default, $_REQUEST["order"] will not exist
    // This adds a default value in it
    // author: sumit
    protected function _setDefaultSortOption($sortbyObj, $storeConfig)
    {
        if (isset($sortbyObj->extend)) {
            if (!empty($sortbyObj->extend)) {
                foreach ($sortbyObj->extend as $optionKey => $optionValue) {
//                    $_REQUEST['order'] = $optionKey;
                    Mage::app()->getRequest()->setQuery('order', $optionKey);
                    break;
                }
            } else {
                $this->isActive = true;
            }
        } else if (isset($sortbyObj->override) && $storeConfig->default_search_sort) {
            Mage::app()->getRequest()->setQuery('order', $storeConfig->default_search_sort);
        } else if (!isset($sortbyObj->override) && !isset($sortbyObj->extend)) {
            Mage::app()->getRequest()->setQuery('order', "takeover_mode");
        }

//        } else if(isset($_SESSION['catalog']) && isset($_SESSION['catalog']['sort_order'])){
//            // If prod list page, use the session value
//            // This case although seems useless
//            $_REQUEST["order"] = $_SESSION['catalog']['sort_order'];
//        }
    }

    /**
     * @param $sortbyObj
     * @return array
     */
    protected function _getOptionsAddedByChoice($sortbyObj)
    {
        $options = array();

        if (isset($sortbyObj->override)) {
            // Search case, override all system options with choice
            foreach ($sortbyObj->override as $key => $caiOption)
                $options[] = $key;
        } elseif (isset($sortbyObj->extend)) {
            foreach ($sortbyObj->extend as $key => $option)
                $options[] = $key;
        }

        return $options;
    }

    /**
     * Checks if debug mode is enabled.
     *
     * @return bool
     */
    public function isDebugEnabled()
    {
        $config = $this->getEngineConfigData();

        return array_key_exists('enable_debug_mode', $config) && $config['enable_debug_mode'];
    }

    /**
     * Forces error display.
     * Used in Abstract.php model's search function
     *
     * @param string $error
     */
    public function showError($error)
    {
//        echo Mage::app()->getLayout()->createBlock('core/messages')
//            ->addError($error)->getGroupedHtml();
    }

}
