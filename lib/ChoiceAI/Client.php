<?php
/**
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

include("Service.php");
class ChoiceAI_Client {
	/**
	 * Default ChoiceAIsearch ruleset
	 */
	const DEFAULT_RULESET = 'search';

	/**
	 * Default transport
	 *
	 * @var string
	 */
	const DEFAULT_TRANSPORT = 'https';

	protected $params = array(
		'ruleset' => self::DEFAULT_RULESET,
		'multiSelectFacet' => false,
		'filter' => array(),
		'rangeFilter' =>array(),
		'cond' => array(),
		'query' => '',
		'category-id' => ''
		);

	protected $address = '';

	protected $choiceaiResponse = null;

	protected $setFacets = null;

	protected $inStoreChoice = '';

	/**
	 * Number of seconds after a timeout occurs for every request
	 * If using indexing of file large value necessary.
	 */
	const TIMEOUT = 300;

	/**
	 * Config with defaults
	 *
	 * @var array
	 */
	protected $_config = array(
		'ruleset' => self::DEFAULT_RULESET,
		'transport' => self::DEFAULT_TRANSPORT,
		'timeout' => self::TIMEOUT,
		'headers' => array()
	);



	/**
	 * Creates a new ChoiceAI client
	 *
	 * @param array $config OPTIONAL Additional config options
	 */
	public function __construct(array $config = array()) {
		$this->setConfig($config);

		//inStoreChoice
        $this->inStoreChoice = $this->_config['transport'].'://datav3.choice.ai/widget/v4/getInStoreChoice';

        //tagProducts
        $this->address = $this->_config['transport'].'://datav3.choice.ai/widget/v4/getTagProducts';
	}

	/**
	 * Sets specific config values (updates and keeps default values)
	 *
	 * @param array $config Params
	 */
	public function setConfig(array $config) {
		foreach ($config as $key => $value) {
			$this->_config[$key] = $value;
		}

		return $this;
	}

	/**
	 * Returns a specific config key or the whole
	 * config array if not set
	 *
	 * @param string $key Config key
	 * @return array|string Config value
	 */
	public function getConfig($key = '') {
		if (empty($key)) {
			return $this->_config;
		}

		if (!array_key_exists($key, $this->_config)) {
			throw new Exception('Config key is not set: ' . $key);
		}

		return $this->_config[$key];
	}

	/**
	 * Sets / overwrites a specific config value
	 *
	 * @param string $key Key to set
	 * @param mixed $value Value
	 * @return ChoiceAI_Client Client object
	 */
	public function setConfigValue($key, $value) {
		return $this->setConfig(array($key => $value));
	}

	/**
	 * Returns connection port of this client
	 *
	 * @return int Connection port
	 */
	public function getContext() {
		return $this->getConfig('context');
	}

	/**
	 * Returns transport type to user
	 *
	 * @return string Transport type
	 */
	public function getTransport() {
		return $this->getConfig('transport');
	}


	/**
	 * sets the attribute filter
	 * @param mixed $filter array
	 * @return ChoiceAI_Client Client object
	 */
	public function setFilters($filter =array()){
		if(isset($filter) && is_array($filter)){
			$this->params['filter'] = $filter;
		}
		return $this;
	}

	/**
	 * sets the range filter
	 * @param mixed $rangeFilter array
	 * @return ChoiceAI_Client Client object
	 */
	public function setRangeFilters($rangeFilter =array()){
		if(isset($rangeFilter) && is_array($rangeFilter)){
			$this->params['rangeFilter'] = $rangeFilter;
		}
		return $this;
	}

	/**
	 * sets the offset
	 * @param mixed $pg integer
	 * @return ChoiceAI_Client Client object
	 */
	public function setOffset($pg = 0){
        $this->params['start'] = $pg;
		return $this;
	}

	/**
	 * sets the limit
	 * @param mixed $limit integer
	 * @return ChoiceAI_Client Client object
	 */
	public function setLimit($limit = 20){
		$this->params['limit'] = $limit;
		return $this;
	}

	/**
	 * sets the ruleset
	 * @param mixed $ruleset string
	 * @return ChoiceAI_Client Client object
	 */
	public function setRuleset($ruleset = 'search'){
		if(isset($ruleset)){
			$this->params['ruleset'] = $ruleset;
		}else{
			$this->params['ruleset'] = 'search';
		}
		return $this;
	}

	/**
	 * sets the sort
	 * @param mixed $sorts array
	 * @return ChoiceAI_Client Client object
	 */
	public function setSort($sorts = array()){
		if(is_array($sorts) && count($sorts)){
			$this->params['sort'] = $sorts;
		}else{
            $this->params['sort'] = array("");
        }
		return $this;
	}

	/**
	 * sets the facet fields, This is mainly used for
	 * @param mixed $sorts array
	 * @return ChoiceAI_Client Client object
	 */
	public function setFacetFields($facetField = array()){
		if(isset($facetField) && is_array($facetField)){
			$this->params['facets'] = $facetField;
		}
		return $this;
	}

	/**
	 * sets the other options which can be used
	 * @param mixed $options array
	 * @return ChoiceAI_Client Client object
	 */
	public function setOtherOptions($options =array()){
		if(isset($options) && is_array($options)){
			$this->params['others'] = $options;
		}
		return $this;
	}

	/**
	 * sets the search query
	 * @param mixed $query search
	 * @return ChoiceAI_Client Client object
	 */
	public function setQuery($query = ''){
		if(isset($query)){
			$this->params['query'] = $query;
		}
		return $this;
	}

	/**
	 * sets the Category Id
	 * @param mixed $query search
	 * @return ChoiceAI_Client Client object
	 */
	public function setCategoryId($query = ''){
		if(isset($query)){
			$this->params['category-id'] = $query;
		}
		return $this;
	}

	/**
	 * sets the Cond
	 * @param mixed $query search
	 * @return ChoiceAI_Client Client object
	 */
	public function setCond($query = ''){
		if(isset($query)){
			$this->params['cond'] = $query;
		}
		return $this;
	}

	/**
	 * sets the Cond
	 * @param mixed $query search
	 * @return ChoiceAI_Client Client object
	 */
	public function setDebug($debug = false){
		if(isset($debug)){
			$this->params['debug'] = $debug;
		}
		return $this;
	}

	/**
	 * sets the multiSelectFacet
	 * @param mixed $multiSelectFacet boolean
	 * @return ChoiceAI_Client Client object
	 */
	public function setMultiSelectFacet($multiSelectFacet = false){
		if(isset($multiSelectFacet)){
			$this->params['multiSelectFacet'] = $multiSelectFacet;
		}
		return $this;
	}


    /**
     * function to get facets
     * @param mixed $param array
     * @return $facetstring string
     */
    public function getFilters()
    {
        $allSysFacets = Mage::app()->getCache()->load("sysFacets");

        // Saving facets to cache if not present already
        if (!$allSysFacets || count($allSysFacets) <= 0) {
            $allSysFacets = array();

            $collection = Mage::getResourceModel('catalog/product_attribute_collection');
            $collection->addFieldToSelect("attribute_code");
            $collection->addFieldToFilter(
                array("is_filterable", "is_filterable_in_search"),
                array(
                    array("eq"=>1),
                    array("eq"=>1)
                )
            );
            $collection->load();

            foreach ($collection as $attr)
                $allSysFacets[] = $attr->getAttributeCode();

            $cache = Mage::app()->getCache();
            $cache->save(Mage::helper('core')->jsonEncode($allSysFacets), "sysFacets", array("facets"));
        } else {
            $allSysFacets = Mage::helper('core')->jsonDecode($allSysFacets);
        }

        if (count($_GET)) {
            $appliedFacets = array();
            foreach ($allSysFacets as $facet) {
                if (isset($_GET[$facet]))
                    $appliedFacets[$facet] = $_GET[$facet];
            }

            // return '&facets=' . Mage::helper('core')->jsonEncode($appliedFacets);
            if (count($appliedFacets))
                $this->setFacets = Mage::helper('core')->jsonEncode($appliedFacets);
        }
    }

	/**
	 *
	 * Search through ChoiceAI api
	 *
	 * @return ChoiceAI_ResultSet object
	 */
	public function search() {
		$service = new ChoiceAI_Service();
        $this->params["context"] = $this->getContext();
        $this->params["org"] = explode("_", $this->params["context"])[0];

        // Checks and sets facets in $this->setFacets
        $this->getFilters();

        if(is_null($this->choiceaiResponse)) {
            if ((int)$this->params['start'] > 0 || $this->setFacets != null) {
                $this->params["facets"] = $this->setFacets;
                $this->choiceaiResponse = $service->search($this->params, $this->address, "tag");
            } else {
                $this->choiceaiResponse = $service->search($this->params, $this->inStoreChoice, "choice");
            }
            return $this->choiceaiResponse;
        } else {
            return $this->choiceaiResponse;
        }
	}

}