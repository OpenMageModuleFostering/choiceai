<?php

/**
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

include("ResultSet.php");
class ChoiceAI_Service
{

    public function getOptions($options){
        if(!isset($options)){
            return "";
        }
        $content = '';
        foreach($options as $key=>$value){
            $content = $content.'&'. rawurlencode($key).'='.rawurlencode($value);
        }

        return $content;
    }


    /**
     * method to set sort fields
     *
     */
    public function getSorting($sorts){

        $sortString='&sort=';
        $startFlag=false;

        if(is_null($sorts)||!isset($sorts)||!is_array($sorts)||!sizeof($sorts)>0){
            return '';
        }

        foreach($sorts as $sort_key=>$sort_value){
            if($startFlag) {
                $sortString=$sortString.",";
            }
            $startFlag = true;
            if($sort_value == 1) {
                $sortString=$sortString.rawurlencode($sort_key." asc");
            } else {
                $sortString=$sortString.rawurlencode($sort_key." desc");
            }
        }

        return $sortString;
    }



    public function getFacetFields($facets,$filters){

        $facetString="";

        if($facets==null){
            $facets=array();
        }
        foreach($facets as $facet){
            $facetString=$facetString."&facet.field=";
            if(array_key_exists($facet,$filters)){
                if(!is_array($stringFacets[$facet]) ||  !sizeof($stringFacets[$facet]) > 0){
                    $facetString=$facetString.rawurlencode("{!ex=\"".$facet."\"}");
                }
            }
            $facetString=$facetString.rawurlencode($facet);
        }

        return $facetString;
    }


    /**
     * function to get facets
     * @param mixed $param array
     * @return $facetstring string
     */
    public function getFilters($filter =array()) {
        $facetString = '&filter='.rawurlencode(json_encode($filter));
        return $facetString;
    }

    /**
     * function to set String facets
     * @param $facetkey string
     * @param mixed $facetvalue array
     * @param $multiSelectFacet boolean
     * @return $facetstring string
     */
    public function getAttributeFacets($facetkey = "", $facetValue = array(),$multiSelectFacet = false ){

        $facetString = "(";

        if(!is_array($facetValue) || !sizeof($facetValue) > 0){
            return "";
        }

        $flag = false;
        if($multiSelectFacet)
            $facetString = $facetString.("{!tag=\"".$facetkey."\"}")."(";
        foreach($facetValue as 	$value){
            $value = str_replace('"','\"',$value);
            if($flag){
                $facetString=$facetString." OR ";
            }
            $flag=true;
            if(is_array($value)){
                $from = isset($value["from"])?$value["from"]:"*";
                $to = isset($value["to"])?$value["to"]:"*";
                $facetString=$facetString.$facetkey.":"."[".$from.' TO '.$to."]";
            } else{
                $facetString=$facetString.$facetkey.":"."\"".$value."\"";
            }
        }

        return $facetString.")";

    }


    /**
     * function to get queryParam
     * @param mixed $ruleset string
     * @return String query
     */
    function getQueryParam($params = array()) {
        if($params['ruleset'] == 'browse') {
            return 'catid='. rawurlencode($params['category-id']);
        } else {
            return 'q='.rawurlencode($params['query']);
        }
    }

    /**
     * function to prepare Url
     * @param mixed $params array
     * @param mixed $address string
     * @return String url
     */
    function prepare_url($params = array(), $address = "") {
        $url = $address.$params['ruleset']."?c=".$params['context']."&start=".(isset($params['start'])?$params['start']:0)."&rows=".(isset($params['limit'])?$params['limit']:20);
        $url = $url.'&'.$this->getQueryParam($params);
        $filter = $this->getFilters($params['filter']);
        if($filter != "")
            $url = $url.$filter;
        if(isset($params['sort']))
            $url = $url.$this->getSorting($params['sort']);
        if(isset($params['others']))
            $url = $url.$this->getOptions($params['others']);
        return $url;
    }

    /**
     *
     * function to fire search query
     *
     */
    public function search($params, $address, $spellcheck = false) {
        $url = $this->prepare_url($params, $address);
        $opts = array(
            'http'=>array(
                'timeout'=>30,
            )
        );

        $context = stream_context_create($opts);
        $response =file_get_contents($url, false, $context);

        $choiceaiResponse=null;
        if(isset($response)) {
            $choiceaiResponse=new ChoiceAI_ResultSet(json_decode($response,true));

            if($spellcheck) {
                $choiceaiResponse->setSpellCheckQuery($params['query']);
            }

            if((!is_null($choiceaiResponse) && !$spellcheck && $choiceaiResponse->getTotalHits()==0 && !is_null($spellSuggest=$choiceaiResponse->getSpellSuggestion())) ||
                (!$spellcheck && $choiceaiResponse->getSpellcheckFrequency()>20 && !is_null($spellSuggest=$choiceaiResponse->getSpellSuggestion()))){
                $params['query'] = $spellSuggest;
                return $this->search($params, $address, true);
            }
        }

        return $choiceaiResponse;
    }
}
?>
