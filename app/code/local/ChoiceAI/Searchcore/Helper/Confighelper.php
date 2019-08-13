<?php

/**
 * @category ChoiceAI
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Helper_Confighelper extends ChoiceAI_Searchcore_Helper_Data
{

    const SITE_KEY = "site_key";

    const API_KEY = "api_key";

    const SECRET_KEY = "secret_key";

    const USERNAME = "username";

    const NEED_FEATURE_FIELD_UPDATION = "need_feature_field_updation";

    const IS_CRON_ENABLED = "cron_enabled";

    const SUBJECT = 'subject';

    const CONTENT = 'content';

    const CC = 'cc';


    /**
     * All possible data type values supported choiceai
     * @var array
     */
    public static $data_types = array("text", "longText", "link", "decimal", "number", "datetime");

    public function validateAndSaveKeys($website, $requestBody)
    {
        $errors = $this->validateKeyParams($requestBody);
        if (sizeof($errors) > 0) {
            return $errors;
        }
        $requestParams = json_decode($requestBody, true);
        if (!$requestParams) {
            $errors['message'] = 'Invalid Request';
            return $errors;
        }
        $response = Mage::getModel("choiceai_searchcore/api_task_validatekeys")
            ->setData(ChoiceAI_Searchcore_Model_Api_Task_Validatekeys::SECRET_KEY, $requestParams[self::SECRET_KEY])
            ->setData(ChoiceAI_Searchcore_Model_Api_Task_Validatekeys::SITE_KEY, $requestParams[self::SITE_KEY])
            ->prepare($website)
            ->process();
        if (!$response->isSuccess()) {
            return $response->getErrors();
        }

        $existingSecretKey = Mage::getResourceModel('choiceai_searchcore/config')
            ->getValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Constants::SECRET_KEY);
        $keyAlreadyExists = !is_null($existingSecretKey);
        if ($keyAlreadyExists) {
            $this->flushConfigs($website);
        }

        Mage::getResourceModel('choiceai_searchcore/config')
            ->setValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Constants::SECRET_KEY,
                $requestParams[ChoiceAI_Searchcore_Helper_Constants::SECRET_KEY]);
        Mage::getResourceModel('choiceai_searchcore/config')
            ->setValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Constants::SITE_KEY,
                $requestParams[ChoiceAI_Searchcore_Helper_Constants::SITE_KEY]);
        $response = $response->getResponse();
        Mage::getResourceModel('choiceai_searchcore/config')
            ->setValue($website->getWebsiteId(),
                ChoiceAI_Searchcore_Helper_Constants::API_KEY,
                $response[ChoiceAI_Searchcore_Model_Api_Task_Validatekeys::API_KEY]);
        Mage::getResourceModel('choiceai_searchcore/config')
            ->setValue($website->getWebsiteId(),
                ChoiceAI_Searchcore_Helper_Constants::USERNAME,
                $response[ChoiceAI_Searchcore_Model_Api_Task_Validatekeys::USERNAME]);
        $this->saveConfig(Mage::app()->getWebsite(),
            array(ChoiceAI_Searchcore_Helper_Constants::API_KEY => $response[ChoiceAI_Searchcore_Model_Api_Task_Validatekeys::API_KEY],
                ChoiceAI_Searchcore_Helper_Constants::SITE_KEY => $requestParams[ChoiceAI_Searchcore_Helper_Constants::SITE_KEY]));
        return $errors;
    }

    public function flushConfigs($website)
    {
        Mage::helper('choiceai_searchcore')->log(Zend_Log::DEBUG, 'Flushing all the configs');
        $configs = $this->getEngineConfigData('', $website, true);
        foreach ($configs as $config => $value) {
            Mage::getConfig()->deleteConfig(ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_CONFIG_PREFIX .
                ChoiceAI_Searchcore_Helper_Constants::CONFIG_SEPARATOR .
                $config,
                'websites',
                (int)$website->getWebsiteId());
        }
        Mage::getResourceModel('choiceai_searchcore/config')->deleteAll($website->getWebsiteId());

    }

    public function validateKeyParams($requestBody)
    {
        $errors = array();
        $requestParams = json_decode($requestBody, true);
        if (!$requestParams) {
            Mage::helper('choiceai_searchcore')->log(Zend_Log::ERR, 'Invalid request with requestBody' . $requestBody);
            $errors['message'] = 'Invalid Request';
            return $errors;
        }
        if (!array_key_exists(ChoiceAI_Searchcore_Helper_Constants::SECRET_KEY, $requestParams)) {
            $errors[ChoiceAI_Searchcore_Helper_Constants::SECRET_KEY] = "Has Empty Data";
        }
        if (!array_key_exists(ChoiceAI_Searchcore_Helper_Constants::SITE_KEY, $requestParams)) {
            $errors[ChoiceAI_Searchcore_Helper_Constants::SITE_KEY] = "Has Empty Data";
        }
        return $errors;
    }

    public function getFeatureFields()
    {
        return ChoiceAI_Searchcore_Model_Field::$feature_fields;
    }

    public function getAllAttributes($fieldNameAsKey = false)
    {
        $attributes = Mage::getSingleton('eav/config')
            ->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();
        $fields = array();
        foreach ($attributes as $attribute) {
            $attributeType = $attribute->getFrontendInput();
            $fieldType = $attributeType == 'media_image' ? ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_IMAGE :
                ($attributeType == 'price' ? ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_NUMBER :
                    ($attributeType == 'date' ? ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_DATE :
                        ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_STRING));
            $fieldType = ($attribute->getName() == "created_at") ? ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_DATE : $fieldType;
            $fieldType = ($attribute->getName() == "updated_at") ? ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_DATE : $fieldType;
            if ($fieldNameAsKey) {
                $fields[$attribute->getName()] = array(ChoiceAI_Searchcore_Helper_Constants::FIELD_NAME => $attribute->getName(),
                    ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE => $fieldType);
            } else {
                $fields[] = array(ChoiceAI_Searchcore_Helper_Constants::FIELD_NAME => $attribute->getName(),
                    ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE => $fieldType);
            }
        }
        if ($fieldNameAsKey) {
            $fields['final_price'] = array(ChoiceAI_Searchcore_Helper_Constants::FIELD_NAME => "final_price",
                ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE => ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_NUMBER);
            $fields['type_id'] = array(ChoiceAI_Searchcore_Helper_Constants::FIELD_NAME => "type_id",
                ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE => ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_STRING);
        } else {
            $fields[] = array(ChoiceAI_Searchcore_Helper_Constants::FIELD_NAME => "final_price",
                ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE => ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_NUMBER);
            $fields[] = array(ChoiceAI_Searchcore_Helper_Constants::FIELD_NAME => "type_id",
                ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE => ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE_STRING);
        }
        return $fields;
    }

    private function getFieldMapping($fields)
    {
        $fieldMapping = array();
        foreach ($fields as $field) {
            $fieldMapping[$field->getFieldName()] = $field;
        }
        return $fieldMapping;
    }

    /**
     * @param $fields
     * @return array
     */
    private function validate($fields)
    {
        $errors = array();
        if (!is_array($fields)) {
            $errors["message"] = "Expecting theInput data should be an array, Given " . gettype($fields);
            return $errors;
        }
        $existingAttributes = $this->getAllAttributes(true);
        $featureFields = Mage::getModel('choiceai_searchcore/field')->getFeaturedFields();
        foreach ($fields as $field) {
            if (!array_key_exists(ChoiceAI_Searchcore_Model_Field::field_name, $field)) {
                $errors["extra"] = "Not Present for all the fields";
                continue;
            } else if (is_null($field[ChoiceAI_Searchcore_Model_Field::field_name]) ||
                $field[ChoiceAI_Searchcore_Model_Field::field_name] == ""
            ) {
                $errors["extra"] = "field Name is empty for some fields";
                continue;
            }
            if (!array_key_exists(ChoiceAI_Searchcore_Model_Field::datatype, $field)) {
                $errors[$field[ChoiceAI_Searchcore_Model_Field::field_name]] = "Not Present for all the fields";
            } else if (!in_array($field[ChoiceAI_Searchcore_Model_Field::datatype], ChoiceAI_Searchcore_Model_Field::$data_types)) {
                Mage::helper('choiceai_searchcore')->log(Zend_Log::ERR, 'Invalid feature field ' .
                    $field[ChoiceAI_Searchcore_Model_Field::datatype]);
                $errors[$field[ChoiceAI_Searchcore_Model_Field::field_name]] = "Invalid datatype specified";
            }

            if (array_key_exists($field[ChoiceAI_Searchcore_Model_Field::field_name], $existingAttributes)) {
                if (!Mage::getSingleton('choiceai_searchcore/field')->validateDatatype($field[ChoiceAI_Searchcore_Model_Field::datatype], $existingAttributes[$field[ChoiceAI_Searchcore_Model_Field::field_name]][ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE])) {
                    $errors[$field[ChoiceAI_Searchcore_Model_Field::field_name]] = "Field cannot be mapped to " . $field[ChoiceAI_Searchcore_Model_Field::datatype];
                }
            }

            if (array_key_exists(ChoiceAI_Searchcore_Model_Field::featured_field, $field)) {
                if (!array_key_exists($field[ChoiceAI_Searchcore_Model_Field::featured_field], $featureFields)) {
                    Mage::helper('choiceai_searchcore')->log(Zend_Log::ERR, 'Invalid feature field ' .
                        $field[ChoiceAI_Searchcore_Model_Field::featured_field]);
                    $errors[$field[ChoiceAI_Searchcore_Model_Field::field_name]] = "Invalid feature field specified";
                } else if (!Mage::getSingleton('choiceai_searchcore/field')->validateDatatype($featureFields[$field[ChoiceAI_Searchcore_Model_Field::featured_field]]["datatype"], $existingAttributes[$field[ChoiceAI_Searchcore_Model_Field::field_name]][ChoiceAI_Searchcore_Helper_Constants::FIELD_TYPE])) {
                    $errors[$field[ChoiceAI_Searchcore_Model_Field::field_name]] = "Field cannot be mapped to " . $field[ChoiceAI_Searchcore_Model_Field::datatype];
                }
            }
        }
        return $errors;
    }

    public function deleteFields($fields, $website)
    {
        $errors = $this->validate($fields);
        if (sizeof($errors) != 0) {
            return $errors;
        }
        $collection = $this->buildFieldCollection($fields, $website);
        return Mage::getModel("choiceai_searchcore/field")->saveFields($collection);
    }

    /**
     * @param $fields
     * @param $website
     * @return array
     */
    public function saveFields($fields, $website)
    {
        $errors = $this->validate($fields);
        if (sizeof($errors) != 0) {
            return $errors;
        }
        $collection = $this->buildFieldCollectionToAdd($fields, $website);
        $response = Mage::getModel("choiceai_searchcore/field")->saveFields($collection);
        if (!is_array($response) && $response === true) {
            Mage::getSingleton('choiceai_searchcore/field')->rebuildConfigCache($website);
            $this->triggerUpdateFeatureField($website);
        }
    }


    public function triggerUpdateFeatureField(Mage_Core_Model_Website $website)
    {
        Mage::getResourceModel('choiceai_searchcore/config')
            ->setValue($website->getWebsiteId(),
                ChoiceAI_Searchcore_Helper_Constants::NEED_FEATURE_FIELD_UPDATION,
                ChoiceAI_Searchcore_Helper_Constants::NEED_FEATURE_FIELD_UPDATION_TRUE);
        $this->triggerFeedUpload($website);
    }

    /**
     * Method to trigger feed upload
     * @param Mage_Core_Model_Website $website
     * @return void
     */
    public function triggerFeedUpload(Mage_Core_Model_Website $website)
    {
        Mage::getModel('choiceai_searchcore/api_task_triggerfeedupload')
            ->prepare($website)
            ->process();
    }


    private function getFeatureFieldToFieldMapping($fields)
    {
        $featureFieldToFieldMapping = array();
        foreach ($fields as $field) {
            if ($field instanceof ChoiceAI_Searchcore_Model_Field &&
                $field->hasData(ChoiceAI_Searchcore_Model_Field::featured_field) &&
                !is_null($field->getData(ChoiceAI_Searchcore_Model_Field::featured_field))
            ) {
                $featureFieldToFieldMapping[$field[ChoiceAI_Searchcore_Model_Field::featured_field]] = $field;
            }
        }
        return $featureFieldToFieldMapping;
    }

    private function buildFieldCollection($fields, $website)
    {
        $collection = array();
        $fieldMapping = $this->getFieldMapping($this->getFields($fields, $website));
        foreach ($fields as $field) {
            if (!array_key_exists(ChoiceAI_Searchcore_Model_Field::field_name, $field)) {
                continue;
            }
            if (array_key_exists($field[ChoiceAI_Searchcore_Model_Field::field_name], $fieldMapping)) {
                $collection[]["delete"] = $fieldMapping[$field[ChoiceAI_Searchcore_Model_Field::field_name]];
            }
        }
        return $collection;
    }

    private function buildFieldCollectionToAdd($fields, $website)
    {
        $collection = array();
        $fieldMapping = $this->getFieldMapping($this->getFields($fields, $website));
        $featureFieldToFieldMapping = $this->getFeatureFieldToFieldMapping($fieldMapping);

        foreach ($fields as $field) {
            if (!array_key_exists(ChoiceAI_Searchcore_Model_Field::field_name, $field)) {
                continue;
            }
            /*
            All possible test cases
             1) if field name is present and it was a feature field
             1.a) if request feature field is equal to selected feature field, dont do anything
             1.b) if request feature field is not equal to selected feature field, dont do anything
             1.c) if request field is not a feature field,
            remove the field name entry from the feature field row, save as different row.

             2) if field name is present and it was not a feature field
             2.a) if request field is a feature field,
                remove the field name entry as a normal field and save as feature field
             2.b) if request field is not a feature field,
            update the existing field

             3) if field name not present,
	     3.a) if it has feature field, delete it from db and insert the new field
             3.b) save as a new field
            */

            // case 1
            if (array_key_exists($field[ChoiceAI_Searchcore_Model_Field::field_name], $fieldMapping) &&
                $fieldMapping[$field[ChoiceAI_Searchcore_Model_Field::field_name]]->hasData(ChoiceAI_Searchcore_Model_Field::featured_field) &&
                !is_null($fieldMapping[$field[ChoiceAI_Searchcore_Model_Field::field_name]]->getData(ChoiceAI_Searchcore_Model_Field::featured_field))
            ) {
                //case 1 a)
                if (array_key_exists(ChoiceAI_Searchcore_Model_Field::featured_field, $field) &&
                    $field[ChoiceAI_Searchcore_Model_Field::featured_field] ==
                    $fieldMapping[$field[ChoiceAI_Searchcore_Model_Field::field_name]][ChoiceAI_Searchcore_Model_Field::featured_field]
                ) {
                    continue;
                } // case 1 b)
                else if (array_key_exists(ChoiceAI_Searchcore_Model_Field::featured_field, $field)) {
                    $collection[]["delete"] = $featureFieldToFieldMapping[$field[ChoiceAI_Searchcore_Model_Field::featured_field]];
                    $collection[]["delete"] = $fieldMapping[$field[ChoiceAI_Searchcore_Model_Field::field_name]];
                    $fieldModel = Mage::getModel("choiceai_searchcore/field");
                    $fieldModel->setFeaturedField($field[ChoiceAI_Searchcore_Model_Field::featured_field]);
                } //case 1 c)
                else {
                    $collection[]["delete"] = $fieldMapping[$field[ChoiceAI_Searchcore_Model_Field::field_name]];
                    $fieldModel = Mage::getModel("choiceai_searchcore/field");

                }
            } else if (array_key_exists($field[ChoiceAI_Searchcore_Model_Field::field_name], $fieldMapping)) {
                //case 2 a)
                if (array_key_exists(ChoiceAI_Searchcore_Model_Field::featured_field, $field)) {
                    $collection[]["delete"] = $fieldMapping[$field[ChoiceAI_Searchcore_Model_Field::field_name]];
                    $fieldModel = Mage::getModel("choiceai_searchcore/field");
                    $fieldModel->setFeaturedField($field[ChoiceAI_Searchcore_Model_Field::featured_field]);
                } // case 2 b)
                else {
                    $fieldModel = $fieldMapping[$field[ChoiceAI_Searchcore_Model_Field::field_name]];
                }
            } else {
                $fieldModel = Mage::getModel("choiceai_searchcore/field");
                if (array_key_exists(ChoiceAI_Searchcore_Model_Field::featured_field, $field)) {
                    $fieldModel->setFeaturedField($field[ChoiceAI_Searchcore_Model_Field::featured_field]);
                    // case 3 a)
                    if (array_key_exists($field[ChoiceAI_Searchcore_Model_Field::featured_field], $featureFieldToFieldMapping)) {
                        $collection[]["delete"] = $featureFieldToFieldMapping[$field[ChoiceAI_Searchcore_Model_Field::featured_field]];
                    }
                }

            }
            $fieldModel->setFieldName($field[ChoiceAI_Searchcore_Model_Field::field_name]);
            $fieldModel->setDatatype($field[ChoiceAI_Searchcore_Model_Field::datatype]);
            $fieldModel->setAutosuggest(0);
            $fieldModel->setWebsiteId($website->getWebsiteId());
            $fieldModel->setDisplayed(1);
            $collection[]["add"] = $fieldModel;
        }
        return $collection;
    }

    /**
     * Method to getFields, if
     *
     * @param $fields
     * @return mixed
     */
    private function getFields($fields, $website)
    {
        $inField = array();
        foreach ($fields as $field) {
            if ($field[ChoiceAI_Searchcore_Model_Field::field_name] == "") {
                continue;
            }
            $inField[] = "'" . $field[ChoiceAI_Searchcore_Model_Field::field_name] . "'";
        }
        $collection = Mage::getResourceModel("choiceai_searchcore/field_collection");

        $collection->getSelect()
            ->where('(' . ChoiceAI_Searchcore_Model_Field::field_name . ' in (' . implode(",", $inField) . ')' . " OR " .
                ChoiceAI_Searchcore_Model_Field::featured_field . " IS NOT NULL) AND " .
                ChoiceAI_Searchcore_Model_Field::website_id . " = " . $website->getWebsiteId());
        return $collection->load();
    }


    /**
     * Method to update feature fields to choiceai
     *
     * @return bool| array
     */
    public function updateFeatureFields(Mage_Core_Model_Website $website)
    {
        $response = Mage::getModel("choiceai_searchcore/api_task_updatefeaturefields")
            ->prepare($website)
            ->process();
        if (!$response->isSuccess()) {
            Mage::log(Zend_Log::ERR,
                "Update feature fields failed because of theses errors " . json_encode($response->getErrors()));
            return $response->getErrors();
        }
        return true;
    }

    public function getNumberOfDocsInChoiceAI(Mage_Core_Model_Website $website)
    {
        $response = Mage::getModel('choiceai_searchcore/api_task_feeddetails')
            ->prepare($website)
            ->process();
        if ($response->isSuccess()) {
            $response = $response->getResponse();
            $feedInfo = $response[ChoiceAI_Searchcore_Model_Api_Task_Feeddetails::FEEDINFO];
            return $feedInfo[ChoiceAI_Searchcore_Model_Api_Task_Feeddetails::NUMDOCS];
        }
        return 0;
    }

    /**
     * @param Mage_Core_Model_Website $website
     * @return void
     */
    public function triggerAutoggestIndexing(Mage_Core_Model_Website $website)
    {
        if (Mage::helper('core')->isModuleEnabled('ChoiceAI_Searchcore') &&
            $this->isConfigTrue($website, ChoiceAI_Searchcore_Helper_Constants::AUTOSUGGEST_STATUS)
        ) {
            //trigger Autosuggest
            $response = Mage::getModel('choiceai_searchcore/api_task_autosuggestindex')
                ->prepare($website)
                ->process();
        }
    }

    public function getCategoryExclusion(Mage_Core_Model_Website $website)
    {
        $conf = Mage::helper('choiceai_searchcore')->getEngineConfigData(ChoiceAI_Searchcore_Helper_Constants::EXCLUDE_CATEGORY, $website, true);
        $categoryExclusionConf = json_decode($conf[ChoiceAI_Searchcore_Helper_Constants::EXCLUDE_CATEGORY], true);
        if (!is_array($categoryExclusionConf)) {
            return array();
        }
        $categoryToBeExcluded = array();
        foreach ($categoryExclusionConf as $eachExclusion) {
            $categoryToBeExcluded[] = (string)$eachExclusion;
        }
        return $categoryToBeExcluded;
    }

    public function getConfigData($name)
    {
        return (string)Mage::getConfig()->getNode("default/" . ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_CONFIG_PREFIX . "/" . $name);
    }
}

?>
