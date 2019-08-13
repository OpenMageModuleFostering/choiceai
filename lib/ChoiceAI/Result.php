<?php

/**
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ChoiceAI_Result {
	/**
	 * Hit array
	 * 
	 * @var array Hit array
	 */
	protected $_hit = array();

	/**
	 * Constructs a single results object
	 *
	 * @param array $hit Hit data
	 */
	public function __construct(array $hit) {

        $this->_hit = $hit;

        $this->_hit["uniqueId"] = $hit["pid"];
        $this->_hit["entity_id"] = $hit["pid"];

        if (strpos($this->_hit["image"], 'catalog/product') !== false) {
            $this->_hit["image"] = "/" . implode("/", array_slice(explode ( "/", $this->_hit["image"]), -3, 3));
        }

        $this->_hit["small_image"] = $this->_hit["image"];
        $this->_hit["thumbnail"] = $this->_hit["image"];

        $this->_hit["final_price"] = $hit["price"];
        if(isset($hit["oldPrice"]) && !is_null($hit["oldPrice"]) && $hit["oldPrice"] > 0) {
            $this->_hit["price"] = $hit["oldPrice"];
        } else {
            $this->_hit["price"] = $hit["price"];
        }

	}

	/**
	 * Magic function to directly access keys inside the result
	 *
	 * Returns null if key does not exist
	 *
	 * @param string $key Key name
	 * @return mixed Key value
	 */
	public function __get($key) {
		return array_key_exists($key, $this->_hit) ? $this->_hit[$key] : null;
	}
	
	
	/*
	 * Returns the raw hit array
	 *
	 * @return array Hit array
	 */
	public function getHit() {
		return $this->_hit;
	}
}
