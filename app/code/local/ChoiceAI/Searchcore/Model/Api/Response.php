<?php

/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Api_Response extends Varien_Object {

    const SERVER_ERR = 'Unable to reach choiceai server, Please contact support';

    const SERVER_RESPONSE_ERR = 'Invalid response from ChoiceAI, Please contact support';

    /**
     * Variable to maintain the response successful or not
     * @var
     */
    protected $success;

    /**
     * Variable to maintain the response successful or not
     * @var
     */
    protected $errors = array();

    protected $jsonResponse = true;

    /**
     * Variable to store the response from the api
     * @var
     */
    protected $response;

    public function isJsonResponse() {

        return $this->jsonResponse;
    }

    public function setJsonResponse($value) {
        if(is_bool($value) && $value == false) {
            $this->jsonResponse = false;
        }
        return $this;
    }

    public function _construct() {
        $this->success = false;
        $this->message = "Empty response";
        $this->response = array();
    }

    public function setSuccess($success) {
        if(is_bool($success)) {
            $this->success = $success;
        }
        return $this;
    }

    /**
     * Method to check exact error message
     *
     * @return bool
     */
    public function isSuccess() {
        return $this->success;
    }

    public function setErrors($errors = array()) {
        $this->errors = $errors;
        return $this;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function setErrorMessage($message) {
        $this->errors['message'] = $message;
        return $this;
    }

    public function setError(string $key, string $value) {
        $this->errors[$key] = $value;
        return $this;
    }

    /**
     * Method to get Message
     *
     * @return string
     */
    public function getMessage() {
        if(array_key_exists('message', $this->errors)) {
            return $this->errors['message'];
        }
        return null;
    }

    /**
     * Method to set the message
     *
     * @param $message
     * @return $this
     */
    public function setMessage($message) {
        $this->errors['message'] = $message;
        return $this;
    }

    /**
     * Method to return the response
     *
     * @return array
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * Method to set the response
     *
     * @param Zend_Http_Response $response
     * @param $url
     * @return $this
     */
    public function setResponse(Zend_Http_Response $response, $url) {
        if($response->isSuccessful()) {
            $this->success = true;
            $this->setMessage("success");
            $body = $response->getBody();
            if(!$this->isJsonResponse()) {
                #Mage::helper('choiceai_searchcore')->log(Zend_Log::DEBUG, "response from the " . $url . " is successful");
                $this->setData("body", $body);
                return $this;
            }
            $this->response =  json_decode($body, true);
            #Mage::helper('choiceai_searchcore')->log(Zend_Log::DEBUG, "response from the " . $url . " is " . $body);
            if($this->response == false || !is_array($this->response) || sizeof($this->response) == 0) {
                $this->success = false;
                $this->setMessage("Invalid Authorization credentials");
                Mage::helper('choiceai_searchcore')->log(Zend_Log::ERR, $url . " api failed cos ". $this->getMessage());
                return $this;
            }
        } else {

            $this->success = false;
            switch ($response->getStatus()) {
                case 500:
                    $message = "ChoiceAI API server error, Please contact support \n" . $response->getBody();
                    $this->setMessage("ChoiceAI API server error, Please contact support \n");
                    break;
                default:
                    $message = "ChoiceAI Unexpected error, Please contact support";
                    $this->setMessage($message);
            }
            Mage::helper('choiceai_searchcore')
                ->log(Zend_Log::ERR, $url . " api failed cos ". $response->getStatus() . ":" . $message);
        }
        return $this;
    }


}
?>
