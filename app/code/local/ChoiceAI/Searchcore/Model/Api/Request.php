<?php

/**
 * Class to make the request to the ChoiceAI api
 *
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Api_Request extends Varien_Object {

    /**
     * end url where the request is firing
     * @var
     */
    protected $url = "";

    /**
     * http method
     * @var
     */
    protected $method = Zend_Http_Client::GET;

    /*
     * Boolean to check whether the data should be sent as raw data or not
     */
    protected $rawData = false;

    /**
     * http response type
     * @var
     */
    protected $response = "";

    /**
     * headers needed to sent
     * @var
     */
    protected $headers = array();

    protected $jsonResponse = true;

    /**
     * Connection timeout to the api calls made to choiceai
     */
    protected $timeout = 30;

    public function isJsonResponse() {
        return $this->jsonResponse;
    }

    public function setJsonResponse($value) {
        if(is_bool($value) && $value == false) {
            $this->jsonResponse = false;
        }
        return $this;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function setHeaders(array $headers) {
        $this->headers = $headers;
        return $this;
    }

    public function getTimeout() {
        return $this->timeout;
    }

    public function setTimeout($timeout) {
        if(is_int($timeout) && $timeout >= 0) {
            $this->timeout = $timeout;
        }
        return $this;
    }

    public function setHeader(string $header,string $value) {
        $this->headers[$header] = $value;
        return $this;
    }

    /**
     * Method to get the url
     *
     * @return mixed
     */
    protected function getUrl() {
        return $this->url;
    }

    /**
     * setter method to set url variable
     *
     * @param $url
     * @return $this
     */
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    /**
     * Method to get the method
     *
     * @return mixed
     */
    protected function getMethod() {
        return $this->method;
    }

    /**
     * setter method to set method variable
     *
     * @param $method
     * @return void
     */
    public function setMethod($method) {
        $this->method = $method;
        return $this;
    }

    /**
     * Method to check whether parameters to be sent as raw parameter
     *
     * @return bool
     */
    protected function isRawData() {
        return $this->rawData;
    }

    /**
     * setter method to set rawdata variable
     *
     * @param bool $rawData
     * @return $this
     */
    public function setRawData($rawData= true) {
        $this->rawData =  $rawData;
        return $this;
    }


    /**
     * return Zend Rest Client
     *
     * @return Zend_Http_Client
     */
    protected function getRestClient(){

        $request = new Zend_Http_Client();
        $request->setUri($this->getUrl())
            ->setHeaders(array("Accept" => "application/json") + $this->getHeaders())
            ->setMethod($this->getMethod())
            ->setConfig(
                array(
                    'timeout' => $this->getTimeout()
                )
            );
        if($this->isRawData()) {
            $params = json_encode($this->getData());
            $request->setRawData($params, 'application/json');
        } else if($this->getMethod() == Zend_Http_Client::GET) {
            $request->setParameterGet($this->getData());
        } else {
            $request->setParameterPost($this->getData());
        }
        return $request;
    }


    /**
     * Method which will make the api call
     *
     * @return false|ChoiceAI_Searchcore_Model_Api_Response
     */
    public function execute(){
        try {
            $request = $this->getRestClient();
            $raw_response = $request->request();
        } catch (Zend_Http_Client_Exception $e) {
            Mage::helper('choiceai_searchcore')->log(Zend_Log::ERR,
                sprintf($this->getUrl() ." failed because HTTP error: %s", $e->getMessage()));
            return Mage::getModel('choiceai_searchcore/api_response')
                ->setErrorMessage(ChoiceAI_Searchcore_Model_Api_Response::SERVER_ERR);
        }
        return Mage::getModel("choiceai_searchcore/api_response")
            ->setJsonResponse($this->isJsonResponse())
            ->setResponse($raw_response, $this->getUrl());
            }

    /**
     * Method to return string
     *
     * @return string
     */
    public function __toString() {

        $parameters = $this->getData();
        if (count($parameters) > 0) {
            $parameters_as_string =  json_encode($parameters);
        }
        return "Request to url " . $this->getUrl() . " with method " . $this->getMethod()
            . (count($parameters) > 0?(" with parameters " . $parameters_as_string):"");
     }


}

?>
