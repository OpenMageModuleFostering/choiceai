<?php

/**
 * Search client.
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class ChoiceAI_Search_Model_Resource_Engine_Abstract
{
    const UNIQUE_KEY = 'unique';

    /**
     * @var array List of default query parameters.
     */
    protected $_defaultQueryParams = array(
        'offset' => 0,
        'limit' => 100,
        'sort_by' => array(array('relevance' => 'desc')),
        'store_id' => null,
        'locale_code' => null,
        'fields' => array(),
        'params' => array(),
        'ignore_handler' => false,
        'filters' => array(),
    );

    /**
     * @var array List of indexable attribute parameters.
     */
    protected $_indexableAttributeParams = array();

    /**
     * @var int Last number of results found.
     */
    protected $_lastNumFound;

    /**
     * @var array List of non fulltext fields.
     */
    protected $_notInFulltextField = array(
        self::UNIQUE_KEY,
        'id',
        'store_id',
        'in_stock',
        'categories',
        'show_in_categories',
        'visibility'
    );

    /**
     * @var array List of used fields.
     */
    protected $_usedFields = array(
        self::UNIQUE_KEY,
        'id',
        'sku',
        'price',
        'store_id',
        'categories',
        'show_in_categories',
        'visibility',
        'in_stock',
        'score'
    );

    /**
     * Get Indexer instance
     *
     * @return Mage_Index_Model_Indexer
     */
    protected function _getIndexer()
    {
        return Mage::getSingleton('index/indexer');
    }

    /**
     * Retrieves product ids for specified query.
     *
     * @param string $query
     * @param array $params
     * @param string $type
     * @return array
     */
    public function getIdsByQuery($query, $params = array(), $type = 'product')
    {
        $ids = array();
        $params['fields'] = array('id');
        $resultTmp = $this->search($query, $params, $type);
        if (!empty($resultTmp['ids'])) {
            foreach ($resultTmp['ids'] as $id) {
                $ids[] = $id['uniqueId'];
            }
        }

        $result = array(
            'ids' => $ids,
            'total_count' => (isset($resultTmp['total_count'])) ? $resultTmp['total_count'] : null,
            'faceted_data' => (isset($resultTmp['facets'])) ? $resultTmp['facets'] : array(),
            'results' => array_key_exists('result', $resultTmp) ? $resultTmp["result"] : array(),
            'stats' => array_key_exists('stats', $resultTmp) ? $resultTmp["stats"] : array()
        );

        return $result;
    }

    /**
     * Returns last number of results found.
     *
     * @return int
     */
    public function getLastNumFound()
    {
        return $this->_lastNumFound;
    }

    /**
     * Returns catalog product collection with current search engine set.
     *
     * @return ChoiceAI_Search_Model_Resource_Catalog_Product_Collection
     */
    public function getResultCollection()
    {
        return Mage::getResourceModel('choiceai_search/catalog_product_collection')->setEngine($this);
    }

    /**
     * Performs search query and facetting
     *
     * The main function that does the search!
     *
     * @param string $query
     * @param array $params
     * @param string $type
     * @return array
     */
    public function search($query, $params = array(), $type = 'product')
    {
        try {
            Varien_Profiler::start('CHOICEAI_SEARCH');
            $result = $this->_search($query, $params, $type);
            Varien_Profiler::stop('CHOICEAI_SEARCH');

            return $result;
        } catch (Exception $e) {
            Mage::logException($e);
            if ($this->_getHelper()->isDebugEnabled()) {
                $this->_getHelper()->showError($e->getMessage());
            }
        }

        return array();
    }

    /**
     * Returns search helper.
     *
     * @return ChoiceAI_Search_Helper_Data
     */
    protected function _getHelper()
    {
        return Mage::helper('choiceai_search');
    }

}
