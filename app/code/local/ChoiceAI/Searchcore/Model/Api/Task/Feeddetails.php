<?php

/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Api_Task_Feeddetails extends ChoiceAI_Searchcore_Model_Api_Task {

    const method = Zend_Http_Client::GET;

    const STATUS = 'status';

    const MESSAGE = 'message';

    const FEEDINFO = 'feedInfo';

    const NUMDOCS = 'numDocs';

    public function prepare(Mage_Core_Model_Website $website) {
        $this->preparationSuccessful = true;
        $this->prepareUrl($website);
        $this->prepareHeaders($website);
        return $this;
    }

    protected  function prepareUrl(Mage_Core_Model_Website $website) {
        $siteKey = Mage::getResourceModel("choiceai_searchcore/config")
            ->getValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Confighelper::SITE_KEY);
        if(is_null($siteKey)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Site key not set";
            return;
        }

        static::$url = static::$PLATFORM_API_BASE_URL . $siteKey . "/feed-info";
    }

    protected function prepareHeaders(Mage_Core_Model_Website $website) {
        $apiKey = Mage::getResourceModel("choiceai_searchcore/config")
            ->getValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Confighelper::API_KEY);
        $secretKey = Mage::getResourceModel("choiceai_searchcore/config")
            ->getValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Confighelper::SECRET_KEY);
        if(is_null($secretKey) || is_null($apiKey)) {
            $this->preparationSuccessful = false;
            $this->errors["message"] = "Site key not set";
            return;
        }
        $this->headers["Authorization"] = base64_encode($apiKey.":" .$secretKey);
    }

    protected function postProcess(ChoiceAI_Searchcore_Model_Api_Response $response) {
        if(!$response->isSuccess()) {
            return $response;
        }
        $responseObj = $response->getResponse();
        if(!array_key_exists(self::STATUS, $responseObj) || !array_key_exists(self::FEEDINFO, $responseObj)) {
            $response->setSuccess(false);
            $response->setMessage('Invalid response from choiceai');
            return $response;
        }
        if($responseObj[self::STATUS] != 200 || !array_key_exists(self::NUMDOCS, $responseObj[self::FEEDINFO])) {
            $response->setSuccess(false);
            $response->setMessage("status code :" .$responseObj[self::STATUS].",".
                (array_key_exists(self::MESSAGE, $responseObj)?$responseObj[self::MESSAGE]:""));
            return $response;
        }
        return $response;
    }
}
