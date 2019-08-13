<?php

/**
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ChoiceAIResponse
{
	var $results=array();
	
	public function __construct($results){
		$this->results=$results;		
	}
	
	/**
	 * function to get products
	 */
	
	public function getResults(){
		return $this->results["response"]["products"];		
	}
	
	
	/**
	 * get Number of search Results
	 */
	public function getNumberOfProducts(){
		return $this->results["response"]["numberOfProducts"];
	}
	
	
	/**
	 * get facets
	 * 
	 */
	public function getFacets(){
		return $this->results["facets"];
	}
	
	
	
}

?>