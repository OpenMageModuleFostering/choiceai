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
//            if($startFlag) {
//                $sortString=$sortString.",";
//            }
//            $startFlag = true;
//            if($sort_value == 1) {
//                $sortString=$sortString.rawurlencode($sort_key." asc");
//            } else {
//                $sortString=$sortString.rawurlencode($sort_key." desc");
//            }
            switch ($sortKey) {
                case $sortKey == "price_high_to_low":
                    $sortString .= "&sortBy=price&sortOrder=desc";
                    break;

                case $sortKey == "price_low_to_high":
                    $sortString .= "&sortBy=price&sortOrder=asc";
                    break;

                case $sortKey == "newest":
                    $sortString .= "&rmodel=newest";
                    break;

                case $sortKey == "bestsellers":
                    $sortString .= "&rmodel=bestsellers";
                    break;
            }
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
    function prepare_url($params = array(), $address = "")
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

        // Facets, passing the facets from cache
        if(isset($params['facets'])){
            $url .= "&facets=".$params['facets'];
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
        $url = $this->prepare_url($params, $address);

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

        Mage::getSingleton('core/cookie')->set("api_url", $url);

        $choiceaiResponse = null;
        if (isset($response)) {
            $response = Mage::helper('core')->jsonDecode($response);

            $data = $response['data'];

            if ($apiType == "choice") {
                $data = $response['data']['automations'];
                if (isset($data[0]))
                    $data = $data[0];
                else
                    $data = array();
            } else {
                $data = $response['data'];
            }

            // tagsList, catPaths
            if (isset($data['tagsList'])) {
                if (isset($_SERVER['REDIRECT_QUERY_STRING']) && strpos($_SERVER['REDIRECT_QUERY_STRING'], "tagsList") === false)
                    $_SERVER['REDIRECT_QUERY_STRING'] .= "&tagsList=" . Mage::helper('core')->jsonEncode($data['tagsList']);

//                Mage::register("tagsList", Mage::helper('core')->jsonEncode($data['tagsList']), true);
            }
            if (isset($data['catPaths'])) {
                if(empty($data['catPaths'])){
                    if (isset($_SERVER['REDIRECT_QUERY_STRING']) && strpos($_SERVER['REDIRECT_QUERY_STRING'], "catPaths") === false)
                        $_SERVER['REDIRECT_QUERY_STRING'] .= "&catPaths={}";

//                    Mage::register("catPaths", "{}", true);
                }
                else{
                    if (isset($_SERVER['REDIRECT_QUERY_STRING']) && strpos($_SERVER['REDIRECT_QUERY_STRING'], "catPaths") === false)
                        $_SERVER['REDIRECT_QUERY_STRING'] .= "&catPaths=" . Mage::helper('core')->jsonEncode($data['catPaths']);

//                    Mage::register("catPaths", Mage::helper('core')->jsonEncode($data['catPaths']), true);
                }
            }

            // for lpimpressions
            if (isset($data['ids'])) {
                Mage::register("prodCountNow", count($data['ids']), true);
            }
            if (isset($data['passback']) && !empty($data['passback'])) {
                Mage::register("passback", $data['passback'], true);
            }

            $choiceaiResponse = new ChoiceAI_ResultSet($data);

            // Not required, spellcheck will be done server-side automagically
//            if($spellcheck) {
//                $choiceaiResponse->setSpellCheckQuery($params['query']);
//            }

//            if((!is_null($choiceaiResponse) && !$spellcheck && $choiceaiResponse->getTotalHits()==0 && !is_null($spellSuggest=$choiceaiResponse->getSpellSuggestion())) ||
//                (!$spellcheck && $choiceaiResponse->getSpellcheckFrequency()>20 && !is_null($spellSuggest=$choiceaiResponse->getSpellSuggestion()))){
//                $params['query'] = $spellSuggest;
//                return $this->search($params, $address, true);
//            }
        }

        return $choiceaiResponse;
    }
}

?>
