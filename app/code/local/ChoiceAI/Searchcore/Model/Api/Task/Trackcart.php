<?php

/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Api_Task_Trackcart extends ChoiceAI_Searchcore_Model_Api_Task {

    const method = Zend_Http_Client::POST;

    const jsonResponse = false;

    const TIMEOUT = 5;

    public function prepare(Mage_Core_Model_Website $website) {
        $this->prepareUrl($website);
        $this->isRawData = true;
        return $this;
    }

    protected function prepareUrl(Mage_Core_Model_Website $website)
    {
        $siteKey = Mage::getResourceModel("choiceai_searchcore/config")
            ->getValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Confighelper::SITE_KEY);
        if (is_null($siteKey)) {
            $this->errors["message"] = "Site key not set";
            return;
        }

        $uid = array_key_exists('choiceai_userId', $_COOKIE) ? $_COOKIE['choiceai_userId'] : null;
        if (!isset($uid) || is_null($uid)) {
            $this->errors["message"] = "UID Missing";
            return;
        }

        static::$url = static::$TRACKER_URL . "v1.0/$siteKey/track/cart/$uid";
        $this->preparationSuccessful = true;
    }

    protected function postProcess(ChoiceAI_Searchcore_Model_Api_Response $response) {
        return $response;
    }
}
?>
