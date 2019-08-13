<?php

/**
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

include("ResultSet.php");

class ChoiceAI_Service
{

    public function getOptions($options)
    {
        if (!isset($options)) {
            return "";
        }
        $content = '';
        foreach ($options as $key => $value) {
            $content = $content . '&' . rawurlencode($key) . '=' . rawurlencode($value);
        }

        return $content;
    }


    /**
     * method to set sort fields
     *
     */
    public function getSorting($sorts)
    {
        $sortString = '';

        if (is_null($sorts) || !isset($sorts) || !is_array($sorts) || !sizeof($sorts) > 0) {
            return '';
        }
        foreach ($sorts as $sortKey => $sortValue) {
            if ($sortKey)
                $sortString = "&caiSortBy=" . $sortKey;
        }
        return $sortString;
    }


    /**
     * function to get queryParam
     * @param mixed $ruleset string
     * @return String query
     */
    function getQueryParam($params = array())
    {
        // ty is setting the type of search
        if ($params['ruleset'] == 'browse') {
            return 'ty=clistpage';
        } else {
            return 'ty=csearch&q=' . rawurlencode($params['query']);
        }
    }

    /**
     * function to prepare Url
     * @param mixed $params array
     * @param mixed $address string
     * @return String url
     */
    function prepare_url($params = array(), $address = "", $apiType = false)
    {
        $url = $address
            . "?c=" . $params['context']
            . "&org=" . $params['org']
            . "&from=" . (isset($params['start']) ? $params['start'] : 0)
            . "&size=" . (isset($params['limit']) ? $params['limit'] : 20);
        $url = $url . '&' . $this->getQueryParam($params);

        // da
        // to be sent only if some count is there, regarding the current state, about results of screen before when the request was made
        if (isset($_GET['ct']) && $_GET['ct'] != "0")
            $url .= "&da=" . Mage::helper('core')->jsonEncode(array("lpimpressions" => $_GET['ct']));
        else
            $url .= "&da={}";

        if (Mage::registry("expId")) {
            $url .= "&existingPage=" . Mage::registry("expId");
        }
        if ($apiType == "tag") {
            if (Mage::registry("passback")) {
                // passback is php array
                $url .= "&passback=" . Mage::helper('core')->jsonEncode(Mage::registry("passback"));
            }
            if (Mage::registry("contextId")) {
                $url .= "&contextId=" . Mage::registry("contextId");
            }
        }

        // Facets, passing the facets from cache
        if (isset($params['facets'])) {
            $url .= "&facets=" . $params['facets'];
        }

        if (isset($params['sort']))
            $url .= $this->getSorting($params['sort']);
        if (isset($params['others']))
            $url .= $this->getOptions($params['others']);

        return $url;
    }

    /**
     *
     * function to fire search query
     * Harkirat: Earlier in place of apiType, there was "$spellcheck = false"
     *
     */
    public function search($params, $address, $apiType = false)
    {
        $url = $this->prepare_url($params, $address, $apiType);
        $base_url = $params['others']['url'];
        if ($base_url && Mage::registry("expId")) {
            Mage::register("user_context", "cai_" . $base_url . Mage::registry("expId"));
        }
        $opts = array(
            'http' => array(
                'timeout' => 30
            )
        );
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $opts['http']['header'] = "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
        }

        $context = stream_context_create($opts);
        $response = file_get_contents($url, false, $context);

        $choiceaiResponse = null;
        if (isset($response)) {
            $response = Mage::helper('core')->jsonDecode($response);
            $uidData = $response['uid'];
            if (isset($uidData)) {
                $uid = explode(',', $uidData);
                Mage::register("caiuid", $uid[0], true);
            }
            if ($apiType == "choice") {
                $data = $response['data']['automations'];
                if (isset($data[0]))
                    $data = $data[0];
                else
                    $data = array();
            } else {
                $data = $response['data'];
            }

            if (isset($data['contextId'])) {
                if (empty($data['contextId'])) {
                    Mage::unregister("contextId");
                    Mage::register("contextId", "x", true);
                } else {
                    Mage::unregister("contextId");
                    Mage::register("contextId", $data['contextId'], true);
                }
            }
            if (isset($data['passback']) && !empty($data['passback'])) {
                Mage::unregister("passback");
                Mage::register("passback", $data['passback'], true);
            }

            $choiceaiResponse = new ChoiceAI_ResultSet($data);

        }

        return $choiceaiResponse;
    }
}

?>