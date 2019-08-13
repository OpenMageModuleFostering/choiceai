<?php

/**
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 
include("Result.php");
class ChoiceAI_ResultSet implements Iterator, Countable {
	/**
	 * Results
	 * 
	 * @var array Results
	 */
	protected $_results = array();

	/**
	 * Current position
	 * 
	 * @var int Current position
	 */
	protected $_position = 0;

	/**
	 * Response
	 * 
	 * @var array
	 */
	protected $_response = null;
	protected $_took = 0;
	
	protected $spellCheckQuery=null;
	/**
	 * Constructs ResultSet object
	 *
	 * @param array $response
	 */
	public function __construct($response) {
		$this->rewind();
		$this->_init($response);
	}
	
	

	/**
	 * Loads all data into the results object (initialisation)
	 *
	 * @param array $response
	 */
	protected function _init($response) {
		$this->_response = $response;
		
		$result = $response;
		$this->_totalHits = $result["total"];
		$this->_took = isset($result["searchMetaData"]["queryTime"]) ? $result["searchMetaData"]['queryTime'] : 0;
		if (isset($result["products"])) {
		    if(isset($result["ids"])) {
                foreach ($result["ids"] as $prodId) {
                    if(isset($result['products'][$prodId]))
                        $this->_results[] = new ChoiceAI_Result($result['products'][$prodId]);
                }
            }else {
                foreach ($result["products"] as $hit) {
                    $this->_results[] = new ChoiceAI_Result($hit);
                }
            }
		}
	}

	/**
	 * Returns all results
	 *
	 * @return array Results
	 */
	public function getResults() {
		return $this->_results;
	}

	/**
	 * Returns whether facets exist
	 *
	 * @return boolean Facet existence
	 */
    //    @TODO: Not being used
	public function hasFacets() {
		return isset($this->_response['facets']);
	}

	/**
	 * Returns all facets results
	 *
	 * @return array Facet results
	 */
	public function getFacets() {
        //return isset($this->_response['facets']) ? $this->_response['facets'] : array();
        return isset($this->_response) && isset($this->_response['facets']) ? $this->_response['facets'] : array();
	}

	/**
	 * Returns the total number of found hits
	 *
	 * @return int Total hits
	 */
	public function getTotalHits() {
		return (int) $this->_totalHits;
	}

	/**
	* Returns the total number of ms for this search to complete
	*
	* @return int Total time
	*/
	public function getTotalTime() {
		return (int) $this->_took;
	}

	/**
	 * Returns response object
	 *
	 * @return array object
	 */
	public function getResponse() {
		return $this->_response;
	}

	/**
	 * Returns size of current set
	 *
	 * @return int Size of set
	 */
	public function count() {
		return sizeof($this->_results);
	}


	/**
	 * Returns the current object of the set
	 *
	 * @return ChoiceAI_Result|bool Set object or false if not valid (no more entries)
	 */
	public function current() {
		if ($this->valid()) {
			return $this->_results[$this->key()];
		} else {
			return false;
		}
	}

	/**
	 * Sets pointer (current) to the next item of the set
	 */
	public function next() {
		$this->_position++;
		return $this->current();
	}

	/**
	 * Returns the position of the current entry
	 *
	 * @return int Current position
	 */
	public function key() {
		return $this->_position;
	}

	/**
	 * Check if an object exists at the current position
	 *
	 * @return bool True if object exists
	 */
	public function valid() {
		return isset($this->_results[$this->key()]);
	}

	/**
	 * Resets position to 0, restarts iterator
	 */
	public function rewind() {
		$this->_position = 0;
	}
	
	
		/**
	 * get Spell corrected query
	 */
	 
	 public function getSpellCheckQuery(){
	 	return $this->spellCheckQuery;
	 }
	 
	 /**
	 * set Spell corrected query
	 */
	 
	 public function setSpellCheckQuery($query){
	 	$this->spellCheckQuery=$query;
	 }
	
	
	/**
	 * get didYouMean results
	 */
	 public function getSpellSuggestion(){
         $suggests=isset($this->_response['didYouMean'])?$this->_response['didYouMean']:null;

         if(is_null($suggests)||!is_array($suggests)||!sizeof($suggests)>0){
             return null;
         }

         foreach($suggests as $suggestion){
             foreach($suggestion as $suggest_key=>$suggest_value){
                 if($suggest_key=='suggestion'){
                     return $suggest_value;
                 }
             }
         }
	 }
	 
	 /**
	  * get stats
	  */
	  public function getStats(){
//	  	echo json_encode($this->_response);
	  	return isset($this->_response['stats'])?$this->_response['stats']:null;
	  }

	  public function getSpellcheckFrequency(){
		if(isset($this->_response["didYouMean"]) && isset($this->_response["didYouMean"][0]['frequency'])){
			return (int)$this->_response["didYouMean"][0]['frequency'];
		}
	}
}
?>