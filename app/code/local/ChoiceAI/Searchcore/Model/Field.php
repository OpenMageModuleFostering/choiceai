<?php

/**
 * This class maintains the config of the fields that are needed by choiceai
 *
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Field extends Mage_Core_Model_Abstract
{

    /**
     * field name column in db
     */
    const field_name = "field_name";

    /**
     * datatype column name in db
     */
    const datatype = "datatype";

    /**
     * autosuggest column name in db
     */
    const autosuggest = "autosuggest";

    /**
     * featured_field column name in db
     */
    const featured_field = "featured_field";

    /**
     * displayable column name in db
     */
    const dislayable = "displayed";

    const multivalued = 'multivalued';

    /**
     * website id column name in db
     */
    const website_id = "website_id";

    const status = 'status';

    /**
     * All possible data type values supported choiceai
     * @var array
     */
    public static $data_types = array(ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_TEXT, ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_LONGTEXT,
        ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_LINK, ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_NUMBER,
        ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_DECIMAL, ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_DATE);


    public static $displayableFeatureFields = array('title', 'price',
        'brand', 'color', 'size', 'imageUrl', 'productUrl');

    public static $featurefields = array();

    /**
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('choiceai_searchcore/field');
        ChoiceAI_Searchcore_Model_Field::$featurefields = $this->getFeaturedFields();

    }


    /**
     * Save fields
     *
     * @return void
     */
    public function saveFields($collection)
    {
        $this->_getResource()->beginTransaction();
        try {
            foreach ($collection as $data) {
                if (sizeof($data) > 0) {
                    if (array_key_exists("add", $data)) {
                        $data["add"]->save();
                    } else if (array_key_exists("delete", $data)) {
                        $data["delete"]->delete();
                    }
                }
            }

            $this->_getResource()->commit();
        } catch (Exception $e) {
            $this->_getResource()->rollBack();
            Mage::helper("choiceai_searchcore")->log(Zend_Log::ERR, "Saving fields failed because " . $e->getMessage());
            return array('OTHERS' => $e->getMessage());
        }
        return true;

    }

    /*
	* method to get the featured fields
	*/
    public function getFeaturedFields()
    {
        $featuredFields = array();
        $featuredFields["uniqueId"] = $this->getField("text", "false", "false");
        $featuredFields["sellingPrice"] = $this->getField("decimal", "false", "false");
        $featuredFields["discount"] = $this->getField("decimal", "false", "false");
        $featuredFields["rating"] = $this->getField("decimal", "false", "false");
        $featuredFields["brandId"] = $this->getField("text", "false", "false");
        $featuredFields[ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_1_NAME] =
            $this->getField("text", "false", "false");
        $featuredFields[ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_2_NAME] =
            $this->getField("text", "false", "false");
        $featuredFields[ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_3_NAME] =
            $this->getField("text", "false", "false");
        $featuredFields[ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_4_NAME] =
            $this->getField("text", "false", "false");
        $featuredFields[ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_1] =
            $this->getField("text", "true", "false");
        $featuredFields[ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_2] =
            $this->getField("text", "true", "false");
        $featuredFields[ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_3] =
            $this->getField("text", "true", "false");
        $featuredFields[ChoiceAI_Searchcore_Model_Resource_Field::CAT_LEVEL_4] =
            $this->getField("text", "true", "false");
        $featuredFields["category"] = $this->getField("text", "true", "true");
        $featuredFields["subCategory"] = $this->getField("text", "true", "true");
        $featuredFields["color"] = $this->getField("text", "true", "false");
        $featuredFields["size"] = $this->getField("text", "true", "false");
        $featuredFields["availability"] = $this->getField("bool", "false", "false");
        $featuredFields["description"] = $this->getField("longText", "false", "false");
        $featuredFields["imageUrl"] = $this->getField("link", "true", "false");
        $featuredFields["productUrl"] = $this->getField("link", "false", "false");
        $featuredFields["brand"] = $this->getField("text", "false", "true");
        $featuredFields["price"] = $this->getField("decimal", "false", "false");
        $featuredFields["title"] = $this->getField("text", "false", "true");
        $featuredFields["gender"] = $this->getField("text", "false", "false");
        $featuredFields["choiceaiVisibility"] = $this->getField("text", "false", "false");
        return $featuredFields;
    }

    public function getField($dataType, $multiValued, $autosuggest)
    {
        return array(self::status => 1, self::datatype => $dataType,
            self::multivalued => ($multiValued == "true") ? 1 : 0,
            self::autosuggest => ($autosuggest == "true") ? 1 : 0);

    }

    public function rebuildConfigCache(Mage_Core_Model_Website $website) {
        $this->setBrandFieldName($website);
        $this->setCategoryFieldName($website);
        $this->setImageUrlFieldName($website);
        $this->setPriceFieldName($website);
        $this->setProductUrlFieldName($website);
        $this->setTitleFieldName($website);
    }

    public function getPriceFieldName()
    {
        $priceFieldConfig =
            Mage::helper('choiceai_searchcore')->getEngineConfigData(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRICE);
        if (array_key_exists(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRICE, $priceFieldConfig)) {
            return $priceFieldConfig[ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRICE];
        }
        $this->rebuildConfigCache(Mage::app()->getWebsite());
        $priceField = $this->_getResource()->getFieldByFeatureField(Mage::app()->getWebsite()->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRICE);
        return $priceField;
    }

    public function setPriceFieldName(Mage_Core_Model_Website $website) {
        $priceName = $this->getResource()->getFieldByFeatureField($website->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRICE);
        Mage::helper('choiceai_searchcore')->saveConfig($website,
            array(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRICE => $priceName));
    }

    public function getImageUrlFieldName()
    {
        $imageUrlFieldName = $this->_getResource()->getFieldByFeatureField(Mage::app()->getWebsite()->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_IMAGE_URL);
        return $imageUrlFieldName;
    }

    public function setImageUrlFieldName(Mage_Core_Model_Website $website)
    {
        $imageField = $this->getResource()->getFieldByFeatureField($website->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_IMAGE_URL);
        Mage::helper('choiceai_searchcore')->saveConfig($website,
            array(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_IMAGE_URL => $imageField));
    }

    public function getProductUrlFieldName()
    {
        $fieldConfig =
            Mage::helper('choiceai_searchcore')
                ->getEngineConfigData(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRODUCT_URL);
        if(array_key_exists(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRODUCT_URL, $fieldConfig)) {
            return $fieldConfig[ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRODUCT_URL];
        }
        $this->rebuildConfigCache(Mage::app()->getWebsite());
        $productUrl = $this->_getResource()->getFieldByFeatureField(Mage::app()->getWebsite()->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRODUCT_URL);
        return $productUrl;

    }

    public function setProductUrlFieldName(Mage_Core_Model_Website $website)
    {
        $productUrlField = $this->getResource()->getFieldByFeatureField($website->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRODUCT_URL);
        Mage::helper('choiceai_searchcore')->saveConfig($website,
            array(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_PRODUCT_URL => $productUrlField));
    }

    public function getCategoryFieldName()
    {
        $fieldConfig =
            Mage::helper('choiceai_searchcore')
                ->getEngineConfigData(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_CATEGORY);
        if(array_key_exists(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_CATEGORY, $fieldConfig)) {
            return $fieldConfig[ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_CATEGORY];
        }
        $this->rebuildConfigCache(Mage::app()->getWebsite());
        $categoryField = $this->_getResource()->getFieldByFeatureField(Mage::app()->getWebsite()->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_CATEGORY);
        return $categoryField;
    }

    public function setCategoryFieldName(Mage_Core_Model_Website $website)
    {
        $categortField = $this->getResource()->getFieldByFeatureField($website->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_CATEGORY);
        Mage::helper('choiceai_searchcore')->saveConfig($website,
            array(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_CATEGORY => $categortField));
    }

    public function getBrandFieldName()
    {
        $fieldConfig =
            Mage::helper('choiceai_searchcore')
                ->getEngineConfigData(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_BRAND);
        if(array_key_exists(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_BRAND, $fieldConfig)) {
            return $fieldConfig[ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_BRAND];
        }
        $this->rebuildConfigCache(Mage::app()->getWebsite());
        $brandField = $this->_getResource()->getFieldByFeatureField(Mage::app()->getWebsite()->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_BRAND);
        return $brandField;
    }

    public function setBrandFieldName(Mage_Core_Model_Website $website)
    {
        $brandField = $this->getResource()->getFieldByFeatureField($website->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_BRAND);
        Mage::helper('choiceai_searchcore')->saveConfig($website,
            array(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_BRAND => $brandField));
    }

    public function getTitleFieldName()
    {
        $fieldConfig =
            Mage::helper('choiceai_searchcore')
                ->getEngineConfigData(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_TITLE);
        if(array_key_exists(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_TITLE, $fieldConfig)) {
            return $fieldConfig[ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_TITLE];
        }
        $this->rebuildConfigCache(Mage::app()->getWebsite());
        $titleField = $this->_getResource()->getFieldByFeatureField(Mage::app()->getWebsite()->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_TITLE);
        return $titleField;
    }

    public function setTitleFieldName(Mage_Core_Model_Website $website)
    {
        $titleField = $this->getResource()->getFieldByFeatureField($website->getWebsiteId(),
            ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_TITLE);
        Mage::helper('choiceai_searchcore')->saveConfig($website,
            array(ChoiceAI_Searchcore_Helper_Constants::FEATURE_FIELD_TITLE => $titleField));
    }

    public function getImageFields($website)
    {
        $conf = Mage::helper('choiceai_searchcore')->getEngineConfigData(ChoiceAI_Searchcore_Helper_Constants::FIELD_CONF, $website, true);
        $fieldConf = json_decode($conf[ChoiceAI_Searchcore_Helper_Constants::FIELD_CONF], true);
        if (!is_array($fieldConf)) {
            return array();
        }
        $imageFields = array();
        foreach ($fieldConf as $field => $conf) {
            if (!is_array($conf) || !array_key_exists('image_full', $conf)) {
                continue;
            }
            $imageFields[$field] = Mage::helper('choiceai_searchcore')->isConfigTrue($website, 'image_full') ? true : false;
        }
        return $imageFields;
    }

    public function getCopyFields($website)
    {
        $conf = Mage::helper('choiceai_searchcore')->getEngineConfigData(ChoiceAI_Searchcore_Helper_Constants::FIELD_CONF, $website, true);

        $fieldConf = json_decode($conf[ChoiceAI_Searchcore_Helper_Constants::FIELD_CONF], true);
        if (!is_array($fieldConf)) {
            return array();
        }
        $imageFields = array();
        foreach ($fieldConf as $field => $conf) {
            if (!is_array($conf) || !array_key_exists('copy_field', $conf)) {
                continue;
            }
            $imageFields[$field] = $conf['copy_field'];
        }
        return $imageFields;
    }

    public function validateDatatype($choiceaiDatatype, $magentoDatatype)
    {
        if ($choiceaiDatatype == ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_TEXT || $choiceaiDatatype == ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_LONGTEXT ||
            $choiceaiDatatype == ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_LINK
        ) {
            return true;
        }
        if ($choiceaiDatatype == ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_NUMBER && $magentoDatatype == ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_NUMBER) {
            return true;
        }
        if ($choiceaiDatatype == ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_DECIMAL && $magentoDatatype == ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_NUMBER) {
            return true;
        }
        if ($choiceaiDatatype == ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_DATE && $magentoDatatype == ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_DATE) {
            return true;
        }
        return false;
    }
}

?>
