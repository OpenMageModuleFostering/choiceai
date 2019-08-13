<?php

/**
 * ChoiceAI engine.
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Search_Model_Resource_Engine_ChoiceAIsearch extends ChoiceAI_Search_Model_Resource_Engine_Abstract
{
    /**
     * Initializes search engine.
     *
     * @see
     */
    public function __construct()
    {
        $this->client = Mage::getResourceSingleton('choiceai_search/engine_choiceaisearch_client');
    }

    function _prepareFacetsQueryResponse($response = array()){
        $result = array();
        foreach ($response as $facetName=>$facetlist) {
            if($facetlist["type"]=='facet_fields'){
                $result[$facetName] = array();
                $count = 0;
                $facetKey ='';
                foreach($facetlist['values'] as $value){
                    if($count++ % 2 == 0){
                        $facetKey = $value;
                    }else{
                        $result[$facetName][$facetKey]=$value;
                    }
                }
            }else if($facetlist["type"]=='facet_ranges'){
                $result[$facetName] = array();
                $count = 0;
                $facetKey = '';
                $gap = floatval($facetlist['values']['gap']);
                foreach($facetlist['values']['counts'] as $value){
                    if($count++ % 2 == 0){
                        $facetKey = floatval($value);
                    }else{
                        $result[$facetName]['['.$facetKey.' TO '.($facetKey + $gap).']']=$value;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * prepares the facet condition
     */
    public function _prepareFacetsConditions($facets = array()){
        $stringFacets = array();
        $rangeFacets = array();
        if(is_array($facets)){
            foreach($facets as $facetKey=>$facetValue){
                if(is_array($facetValue) && $facetValue != null && is_array($facetValue) && sizeof($facetValue) > 0 ){
                    if(isset($facetValue['from']) && isset($facetValue["to"])){
                        $facetValues= array();
                        $eachFacetValue= $facetValue;
                        if(!isset($eachFacetValue['from']) || $eachFacetValue['from'] == "" ){
                            $eachFacetValue['from'] = '0';
                        }
                        if(!isset($eachFacetValue['to']) || $eachFacetValue['to'] == "" ){
                            $eachFacetValue['to'] = '*';
                        }
                        $facetValues['from'] = $eachFacetValue['from'];
                        $facetValues['to'] = $eachFacetValue['to'];
                        $stringFacets[$facetKey][] = $facetValues;
                    } else {
                        $stringFacets[$facetKey] = $facetValue;
                    }
                }
            }
        }
        $facets=array();
        $facets["attribute"] = $stringFacets;
        $facets["range"] = $rangeFacets;
        return $facets;
    }

    protected function _prepareResponse($data){
        /* @var $response ChoiceAI_ResultSet */
        if (!$data instanceof ChoiceAI_ResultSet || $data->getTotalHits()<=0) {
            return array();
        }

        $result = array();

        foreach ($data->getResults() as $doc) {
            $result[] =$doc->getHit();
        }
        return $result;
    }

    /**
     * Prepares sort fields.
     *
     * @param array $sortBy
     * @return array
     */
    protected function _prepareSortFields($sortBy=array())
    {
        $sort =array();
        if(!isset($sortBy) || sizeof($sortBy) == 0) {
            $sortParameter = array_key_exists('order',$_GET)?$_GET['order']:null;
            $sortBy = (!is_null($sortParameter))?
                array(array($sortParameter => (isset($_GET['dir']) && $_GET['dir'] == 'desc')?'desc':'asc')): array();
            $sessionSort = Mage::getSingleton('catalog/session')->getSortOrder();
            $sessionDir = Mage::getSingleton('catalog/session')->getSortDirection();
            $sortBy = (is_null($sortBy)|| sizeof($sortBy) == 0) && !is_null($sessionSort) ?
                array(array($sessionSort => ($sessionDir === 'desc')?'desc':'asc')):$sortBy;
        }

        foreach($sortBy as $value){
            foreach($value as $sortKey=>$sortValue){
                if($sortKey != "position" && $sortKey != "relevance"){
                    if($sortValue == 'asc'){
                        $sort[$sortKey]  = 1;
                    }else{
                        $sort[$sortKey]  = -1;
                    }
                }
            }
        }
        return $sort;
    }


    public function getStats($data){
        $stats = $data->getStats();
        if(isset($stats) && is_array($stats)){
            return $stats;
        }
        return array();
    }

    /**
     * prepare limitN
     * @param $params
     * @return int|mixed
     */
    protected function _prepareLimit($params)
    {
        if(array_key_exists("limit", $params)) {
            $limit = (int)$params["limit"];
        }else{
            $limitInRequest = array_key_exists('limit', $_GET) ? $_GET['limit'] : null;
            $limitOnSession = Mage::getSingleton('catalog/session')->getLimitPage();
            if ($limitOnSession === "all" || $limitInRequest === "all") {
                $limitOnSession = 100;
            }
            $limit = ($limitInRequest > 0) ? (int)$limitInRequest : ($limitOnSession > 0 ? $limitOnSession : ((isset($_GET['mode']) && $_GET['mode'] == 'list') ? Mage::getStoreConfig('catalog/frontend/list_per_page') : Mage::getStoreConfig('catalog/frontend/grid_per_page')));
        }

        return $limit;
    }

    /**
     * Performs search and facetting
     *
     * Note: Called from app/code/local/ChoiceAI/Search/Model/Resource/Engine/Abstract.php's search function
     *
     * @param string $query
     * @param array $params
     * @param string $type
     * @return array
     */
    protected function _search($qt, $params = array(), $type = 'product')
    {
        $multiselectValue = Mage::getConfig()->getNode('default/choiceai/general/multiselect_facet');
        $multiselectValue = (!is_null($multiselectValue) &&
            sizeof($multiselectValue) && $multiselectValue[0] == 'true')?true:false;
        $limit = $this->_prepareLimit($params);
        if(isset($_GET['p'])){
            $page = ((int)$_GET['p'] > 0)?((int)$_GET['p'] - 1)* $limit:0;
        }else{
            $page = 0;
        }

        $facets = $this->_prepareFacetsConditions($params['filters']);
        $searchParams = array();
        $query =  Mage::helper('catalogsearch')->getQueryText();

        if(isset($query) && $query != ''){
            $this->client = $this->client->setRuleset('search')->setQuery($query)->setFilters($facets['attribute']);
        }else{
            $this->client = $this->client->setRuleset('browse')->setCategoryId($params['category'])->setFilters($facets['attribute']);
        }

        // @TODO: Confirm this, till "?" only right?
        $urlPath = explode("?", $_SERVER['REQUEST_URI'])[0];

        $paramsString = isset($_SERVER['REDIRECT_QUERY_STRING']) ? $_SERVER['REDIRECT_QUERY_STRING'] : '';

        $getParamsToSend = array(
            'v'=> 3,
            'url' => $urlPath
        );

        // Checking if the following params are available in GET, if yes, we will forward them just as they are
        $pairs = [
            'site'=> 'mwc',
            'expId'=> 'expId',
            'variantId'=> 'variantId',
            'mwpreview'=> 'mwpreview',
            'mwforce'=> 'mwforce',
            'mwdevice'=> 'mwdevice',
            'layout'=> 'layout',
            'uitype'=> 'uitype',
            'expforce'=> 'expforce',
            'lrp'=>'lrp',
            'llp'=>'llp',
            'catPaths'=> 'catPaths',
            'tagsList'=> 'tagsList'
        ];

        foreach ($pairs as $getParam => $paramNameForSending) {
            if (isset($_GET[$getParam]))
                $getParamsToSend[$paramNameForSending] = $_GET[$getParam];
        }


        if(trim($paramsString) != '')
            $getParamsToSend['p'] = $paramsString;

        if(isset($_SERVER['HTTP_REFERER']))
            $getParamsToSend['r'] = $_SERVER['HTTP_REFERER'];

        if(isset($_COOKIE['caicookie']))
            $getParamsToSend['u'] = $_COOKIE['caicookie'];


        // Calls Client.php in lib
        $data = $this->client
            ->setOffset($page)
            ->setLimit($limit)
            ->setOtherOptions($getParamsToSend)
            ->setDebug(false)
            ->setSort($this->_prepareSortFields(array_key_exists('sort_by', $params)?$params['sort_by']:array()))
            ->search();
        if (!$data instanceof ChoiceAI_ResultSet) {
            return array();
        }

//        if($data->getSpellCheckQuery()) {
//            // Not being read anywhere, so why setting it?
//            Mage::unregister('spellcheckQuery');
//            Mage::register('spellcheckQuery', $data->getSpellCheckQuery());
//        }


        /* @var $data ChoiceAI_ResultSet */
        $result = array(
                'total_count' => $data->getTotalHits(),
                'result' => $this->_prepareResponse($data),
                'stats' => $this->getStats($data)
            );
        $result['facets'] = $this->_prepareFacetsQueryResponse($data->getFacets());
        // Seems unused
        Mage::unregister('start');
        Mage::register('start', $page* Mage::getStoreConfig('catalog/frontend/grid_per_page'));
        return $result;
    }

}

?>