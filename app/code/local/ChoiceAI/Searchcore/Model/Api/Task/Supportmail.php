<?php
/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Api_Task_Supportmail extends ChoiceAI_Searchcore_Model_Api_Task {

    const method = Zend_Http_Client::POST;
    protected $isRawData = true;


    public function prepare(Mage_Core_Model_Website $website) {
        $this->preparationSuccessful = true;
        $this->prepareUrl($website);
        $this->prepareHeaders($website);
        return $this;
    }

    protected function prepareUrl(Mage_Core_Model_Website $website) {
        $siteKey = Mage::getResourceModel("choiceai_searchcore/config")
            ->getValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Confighelper::SITE_KEY);
        if(is_null($siteKey)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Site key not set";
            return;
        }

        static::$url = static::$RECOMMENDATION_SETTINGS_URL . "dashboard/mail/support";
    }

    protected function prepareHeaders(Mage_Core_Model_Website $website) {
        $username = Mage::getResourceModel("choiceai_searchcore/config")
            ->getValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Confighelper::USERNAME);
        if(is_null($username)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Secret key not set";
            return;
        }

        $this->headers["authorization"] = "Basic " . base64_encode($username . ':$uauth');
    }

    protected function postProcess(ChoiceAI_Searchcore_Model_Api_Response $response) {
        return $response;
    }
}
