<?php

/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class ChoiceAI_Searchcore_Model_Api_Task extends Varien_Object {

    static $PLATFORM_API_BASE_URL = "https://accounts.choiceaiapi.com/admin/";

    static $RECOMMENDATION_SETTINGS_URL = "https://starwreck.choiceai.com/";

    static $TRACKER_URL = "https://tracker.choiceaiapi.com/";

    const TIMEOUT = 30;

    static $url = "";

    const method = Zend_Http_Client::GET;

    protected $preparationSuccessful;

    protected $errors;

    protected $headers = array();

    protected $isRawData = false;

    const jsonResponse = true;

    public function __construct() {
        $this->preparationSuccessful = false;
        $this->errors = array();
	static::$PLATFORM_API_BASE_URL = Mage::helper('choiceai_searchcore/confighelper')->getConfigData('platform_url');
	static::$RECOMMENDATION_SETTINGS_URL = Mage::helper('choiceai_searchcore/confighelper')->getConfigData('service_url');
	static::$TRACKER_URL = Mage::helper('choiceai_searchcore/confighelper')->getConfigData('tracker_url');
    }

    /**
     * Method to check whether parameters to be sent as raw parameter
     *
     * @return bool
     */
    protected function isRawData() {
        return $this->isRawData;
    }

    abstract public function prepare(Mage_Core_Model_Website $website);

    abstract protected function postProcess(ChoiceAI_Searchcore_Model_Api_Response $response);

    /**
     * Method which will make the api call
     *
     * @return false|ChoiceAI_Searchcore_Model_Api_Response
     */
    public function process() {
        if(!$this->preparationSuccessful) {
            Mage::helper("choiceai_searchcore")
                ->log(Zend_Log::ERR,
                    sizeof($this->errors == 0)?"prepare method not called":json_encode($this->errors));
            return Mage::getModel("choiceai_searchcore/api_response")
                ->setErrors(sizeof($this->errors) == 0?array("message" => "prepare method not called"):$this->errors);
        }
        $request = Mage::getModel("choiceai_searchcore/api_request")
            ->setUrl(static::$url)
            ->setMethod(static::method)
            ->setHeaders($this->headers)
            ->setTimeout(static::TIMEOUT)
            ->setJsonResponse(static::jsonResponse);
        if($this->isRawData()) {
            $request->setRawData(true);
        }
        if($this->getData()) {
            $request->addData($this->getData());
        }

        return $this->postProcess($request->execute());
    }

}
?>
