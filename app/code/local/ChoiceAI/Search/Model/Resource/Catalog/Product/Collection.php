<?php
/**
 * Custom catalog product collection model.
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Search_Model_Resource_Catalog_Product_Collection extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection
{
    /**
     * @var ChoiceAI_Search_Model_Resource_Engine_Abstract Search engine.
     */
    protected $_engine;

    /**
     * @var array Faceted data.
     */
    protected $_facetedData = array();

    /**
     * @var array Facets conditions.
     */
    protected $_facetsConditions = array();

    /**
     * @var array General default query.
     */
    protected $_generalDefaultQuery = array('*' => '*');

    /**
     * @var string Search query text.
     */
    protected $_searchQueryText = '';

    /**
     * @var array Search query filters.
     */
    protected $_searchQueryFilters = array();

    protected $_searchCategoryFilters =array();

    /**
     * @var array Search query range filters.
     */
    protected $_searchQueryRangeFilters = array();

    /**
     * @var array Search entity ids.
     */
    protected $_searchedEntityIds = array();

    /**
     * @var array Sort by definition.
     */
    protected $_sortBy = array();
    
    protected $qt = 'search';

    protected $stats =array();

    /**
     * Adds facet condition to current collection.
     *
     * @param string $field
     * @param mixed $condition
     * @return ChoiceAI_Search_Model_Resource_Catalog_Product_Collection
     */
    public function addFacetCondition($field, $condition = null)
    {
        if (array_key_exists($field, $this->_facetsConditions)) {
            if (!empty($this->_facetsConditions[$field])){
                $this->_facetsConditions[$field] = array($this->_facetsConditions[$field]);
            }
            $this->_facetsConditions[$field][] = $condition;
        } else {
            $this->_facetsConditions[$field] = $condition;
        }

        return $this;
    }

    /**
     * Stores filter query.
     *
     * @param array $params
     * @return ChoiceAI_Search_Model_Resource_Catalog_Product_Collection
     */
    public function addFqFilter($params)
    {
        if (is_array($params)) {
            foreach ($params as $field => $value) {
                $this->_searchQueryFilters[$field] = $value;
            }
        }

        return $this;
    }
    
    /**
     * Stores filter query.
     *
     * @param array $params
     * @return ChoiceAI_Search_Model_Resource_Catalog_Product_Collection
     */
    public function addCategoryId($id)
    {   
		$this->_searchCategoryFilters = $id;

        return $this;
    }
    
    /**
     * setting the query type
     */
     public function setQueryType($qt = 'search'){
     	$this->qt = $qt;
     	return $this;
     }

    /**
     * Stores range filter query.
     *
     * @param array $params
     * @return ChoiceAI_Search_Model_Resource_Catalog_Product_Collection
     */
    public function addFqRangeFilter($params)
    {
        if (is_array($params)) {
            foreach ($params as $field => $value) {
                $this->_searchQueryRangeFilters[$field] = $value;
            }
        }

        return $this;
    }

    /**
     * Stores query text filter.
     *
     * @param $query
     * @return ChoiceAI_Search_Model_Resource_Catalog_Product_Collection
     */
    public function addSearchFilter($query)
    {
        $this->_searchQueryText = $query;

        return $this;
    }

    /**
     * Stores search query filter.
     *
     * @param mixed $param
     * @param null $value
     * @return ChoiceAI_Search_Model_Resource_Catalog_Product_Collection
     */
    public function addSearchQfFilter($param, $value = null)
    {
        if (is_array($param)) {
            foreach ($param as $field => $value) {
                $this->addSearchQfFilter($field, $value);
            }
        } elseif (isset($value)) {
            if (isset($this->_searchQueryFilters[$param]) && !is_array($this->_searchQueryFilters[$param])) {
                $this->_searchQueryFilters[$param] = array($this->_searchQueryFilters[$param]);
                $this->_searchQueryFilters[$param][] = $value;
            } else {
                $this->_searchQueryFilters[$param] = $value;
            }
        }

        return $this;
    }

    /**
     * Aggregates search query filters.
     *
     * @return array
     */
    public function getExtendedSearchParams()
    {
        $result = $this->_searchQueryFilters;
        $result['query_text'] = $this->_searchQueryText;

        return $result;
    }

    /**
     * Returns faceted data.
     *
     * @param string $field
     * @return array
     */
    public function getFacetedData($field)
    {

        if (array_key_exists($field, $this->_facetedData)) {
            return $this->_facetedData[$field];
        }

        return array();
    }

    /**
     * Returns collection size.
     *
     * @return int
     */
    public function getSize()
    {
        if (is_null($this->_totalRecords)) {
            $query = $this->_getQuery();
            $params = $this->_getParams();
            $params['limit'] = 1;
            //$this->_engine->getIdsByQuery($query, $params);
	    $this->_totalRecords = 1;
            //$this->_totalRecords = $this->_engine->getLastNumFound();
        }

        return $this->_totalRecords;
    }

    /**
     * Retrieves current collection stats.
     * Used for max price.
     *
     * @param $fields
     * @return mixed
     */
    public function getStats($field)
    {
	return isset($this->stats[$field])?$this->stats[$field]:array();
    }

    /**
     * Defines current search engine.
     *
     * @param ChoiceAI_Search_Model_Resource_Engine_Abstract $engine
     * @return ChoiceAI_Search_Model_Resource_Catalog_Product_Collection
     */
    public function setEngine(ChoiceAI_Search_Model_Resource_Engine_Abstract $engine)
    {
        $this->_engine = $engine;

        return $this;
    }

    /**
     * Stores sort order.
     *
     * @param string $attribute
     * @param string $dir
     * @return ChoiceAI_Search_Model_Resource_Catalog_Product_Collection
     */
    public function setOrder($attribute, $dir = self::SORT_ORDER_DESC)
    {
        $this->_sortBy[] = array($attribute => $dir);

        return $this;
    }

    /**
     * Handles collection filtering by ids retrieves from search engine.
     * Will also stores faceted data and total records.
     *
     * @return Mage_Catalog_Model_Resource_Product_Collection
     */
    protected function _beforeLoad()
    {
	if ($this->_engine) {
            $result = $this->_engine->getIdsByQuery($this->qt, $this->_getParams());
            $this->_facetedData = isset($result['faceted_data']) ? $result['faceted_data'] : array();
            $this->_totalRecords = isset($result['total_count']) ? $result['total_count'] : null;
            $this->stats = isset($result["stats"])?$result["stats"] :array();
	        $this->results = isset($result["results"]) ? $result["results"] : array();
        }
	foreach($this->results as $result){
        	$product = new Mage_Catalog_Model_Product();
		$result['id'] = $result['uniqueId'];
		$product->addData($result);
		$this->_items[$result['id']]=$product;
	}

        return parent::_beforeLoad();
    }



    /**
     * Load collection data into object items
     *
     * @return Mage_Eav_Model_Entity_Collection_Abstract
     */
    public function load($printQuery = false, $logQuery = false)
    {
        if ($this->isLoaded()) {
            return $this;
        }
        Varien_Profiler::start('__EAV_COLLECTION_BEFORE_LOAD__');
        Mage::dispatchEvent('eav_collection_abstract_load_before', array('collection' => $this));
        $this->_beforeLoad();
        Varien_Profiler::stop('__EAV_COLLECTION_BEFORE_LOAD__');


        Varien_Profiler::start('__EAV_COLLECTION_LOAD_ENT__');
//        $this->_loadEntities($printQuery, $logQuery);
        Varien_Profiler::stop('__EAV_COLLECTION_LOAD_ENT__');
        Varien_Profiler::start('__EAV_COLLECTION_LOAD_ATTR__');
//        $this->_loadAttributes($printQuery, $logQuery);
        Varien_Profiler::stop('__EAV_COLLECTION_LOAD_ATTR__');

        Varien_Profiler::start('__EAV_COLLECTION_ORIG_DATA__');
	/*
        foreach ($this->_items as $item) {
            $item->setOrigData();
        }*/
        Varien_Profiler::stop('__EAV_COLLECTION_ORIG_DATA__');

        $this->_setIsLoaded();
        Varien_Profiler::start('__EAV_COLLECTION_AFTER_LOAD__');
        $this->_afterLoad();
        Varien_Profiler::stop('__EAV_COLLECTION_AFTER_LOAD__');
        return $this;
    }

    /**
     * Retrieves parameters.
     *
     * @return array
     */
    protected function _getParams()
    {
        $store = Mage::app()->getStore($this->getStoreId());
        $params = array();
        $params['locale_code'] = $store->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_LOCALE);
        $params['filters'] = $this->_searchQueryFilters;
        $params['category'] = $this->_searchCategoryFilters;
        $params['range_filters'] = $this->_searchQueryRangeFilters;

        if (!empty($this->_sortBy)) {
            $params['sort_by'] = $this->_sortBy;
        }

        if ($this->_pageSize !== false) {
            $page = ($this->_curPage  > 0) ? (int) $this->_curPage  : 1;
            $rowCount = ($this->_pageSize > 0) ? (int) $this->_pageSize : 1;
            $params['offset'] = $rowCount * ($page - 1);
            $params['limit'] = $rowCount;
        }

        if (!empty($this->_facetsConditions)) {
            $params['facets'] = $this->_facetsConditions;
        }

        return $params;
    }

    /**
     * Returns stored text query.
     *
     * @return string
     */
    protected function _getQuery()
    {
        return $this->_searchQueryText;
    }
}
