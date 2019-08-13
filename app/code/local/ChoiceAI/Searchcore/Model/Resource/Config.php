<?php

/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Resource_Config extends Mage_Core_Model_Mysql4_Abstract
{

    /**
     * ChoiceAI Config table Name
     *
     * @var string
     */
    protected $_choiceaiConfigTable;

    // date format
    const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('choiceai_searchcore/config', 'id');
        $this->_choiceaiConfigTable = $this->getTable('choiceai_searchcore/config');
    }

    public function getValues($websiteId, $key) {
        if(!isset($key) || is_array($key) ){
            return array();
        }
        $adapter = $this->_getReadAdapter();
        $select = $adapter->select()
            ->from($this->_choiceaiConfigTable, ChoiceAI_Searchcore_Model_Config::VALUE)
            ->where(ChoiceAI_Searchcore_Model_Config::WEBSITE_ID.' = ?', (int)$websiteId)
            ->where(ChoiceAI_Searchcore_Model_Config::KEY.' = ?', $key);
        $rows = $adapter->fetchAll($select);
        $values = array();
        foreach($rows as $row) {
            if(array_key_exists(ChoiceAI_Searchcore_Model_Config::VALUE, $row)) {
                $values[] = $row[ChoiceAI_Searchcore_Model_Config::VALUE];
            }
        }
        return $values;
    }

    /**
     * @param $website_id
     * @param $key
     * @return null|string
     */
    public function getValue($website_id, $key)
    {
        $adapter = $this->_getReadAdapter();
        if(is_array($key)) {
            $keyValuePair = array();
            foreach ($key as $eachKey) {
                $keyValuePair[$eachKey] = $this->getValue($website_id, $eachKey);
            }
            return $keyValuePair;
        }
        $select = $adapter->select()
            ->from($this->_choiceaiConfigTable, 'value')
            ->where('`'.ChoiceAI_Searchcore_Model_Config::WEBSITE_ID.'` = ?', (int)$website_id)
            ->where('`'.ChoiceAI_Searchcore_Model_Config::KEY.'` = ?', $key);
        $result = $adapter->fetchOne($select);
        if($result === false) {
            return null;
        }
        return $result;
    }

    /**
     * @param int $website_id
     * @param string $key
     * @param string $value
     * @return void
     */
    public function setValue($website_id, $key, $value = null)
    {
        if(is_array($key)) {
            foreach($key as $eachKey => $eachValue) {
                $this->setValue($website_id, $eachKey, $eachValue);
            }
            return;
        }
	if (!isset($value) || $value == "" || !isset($key) || $key == "") {
            return;
        }

        $config = Mage::getModel('choiceai_searchcore/config')->getCollection()
            ->addFieldToFilter(ChoiceAI_Searchcore_Model_Config::KEY, $key)
            ->addFieldToFilter(ChoiceAI_Searchcore_Model_Config::WEBSITE_ID, (int)$website_id)
            ->getFirstItem();

        $config->setWebsiteId($website_id)
            ->setChoiceAIKey($key)
            ->setValue($value)
            ->save();
    }

    public function updateValues($websiteId, $key, $values = array()) {
        $this->deleteKey($websiteId, $key);
        $this->beginTransaction();
        foreach($values as $eachValue) {
            Mage::getModel('choiceai_searchcore/config')
                ->setWebsiteId($websiteId)
                ->setChoiceAIKey($key)
                ->setValue($eachValue)
                ->save();
        }
        $this->commit();
    }

    public function deleteKey($websiteId, $key) {
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");
        $query = "DELETE FROM choiceai_recommendation_conf WHERE " . ChoiceAI_Searchcore_Model_Config::KEY . " = :key"
            . " and " . ChoiceAI_Searchcore_Model_Config::WEBSITE_ID . " = :website_id";
        $binds = array(
            ChoiceAI_Searchcore_Model_Config::KEY => $key,
            ChoiceAI_Searchcore_Model_Config::WEBSITE_ID => $websiteId
        );
        $write->query($query, $binds);

    }

    public function lockSite($website_id) {
        $this->setValue($website_id, array(
            ChoiceAI_Searchcore_Model_Config::FEED_LOCK => ChoiceAI_Searchcore_Model_Config::FEED_LOCK_TRUE,
            ChoiceAI_Searchcore_Model_Config::FEED_LOCK_TIME => date(self::DATE_FORMAT)));

    }


    public function unLockSite($website_id) {
        $this->setValue($website_id, ChoiceAI_Searchcore_Model_Config::FEED_LOCK,
            ChoiceAI_Searchcore_Model_Config::FEED_LOCK_FALSE);
    }

    /**
     * Method will check whether there is a feed lock or not
     * @param $website_id
     * @return bool
     */
    public function isLock($website_id) {
        //fetch the values for feedlock, feed lock time from db
        $feedLockDetails = $this->getValue($website_id,
            array(ChoiceAI_Searchcore_Model_Config::FEED_LOCK, ChoiceAI_Searchcore_Model_Config::FEED_LOCK_TIME));
        //fetch feed lock from @var feedLockDetails
        $feedLock = array_key_exists(ChoiceAI_Searchcore_Model_Config::FEED_LOCK, $feedLockDetails)?
            $feedLockDetails[ChoiceAI_Searchcore_Model_Config::FEED_LOCK]:null;
            if(is_null($feedLock) || $feedLock == ChoiceAI_Searchcore_Model_Config::FEED_LOCK_FALSE){
            return false;
        }
        // Ignoring the feed Lock, if the feed has been locked more than $maxFeedLockTime
        if($feedLock == ChoiceAI_Searchcore_Model_Config::FEED_LOCK_TRUE &&
            array_key_exists(ChoiceAI_Searchcore_Model_Config::FEED_LOCK_TIME, $feedLockDetails)) {
            $feedLockTime  = $feedLockDetails[ChoiceAI_Searchcore_Model_Config::FEED_LOCK_TIME];
            $date = strtotime($feedLockTime);
            $currentTime = strtotime(date(self::DATE_FORMAT));
            $diff = abs($date - $currentTime);
            $maxFeedLockTime = Mage::getConfig()->getNode('default/choiceai/general/max_feed_lock_feed');
            if(is_null($maxFeedLockTime)) {
                $maxFeedLockTime = ChoiceAI_Searchcore_Model_Config::MAX_FEED_LOCK_TIME;
            }
            if(round($diff / ( 60 * 60 )) > $maxFeedLockTime) {
                return false;
            }
        }
        return true;
    }

    /**
     * Method to get all the filters
     * @param Mage_Core_Model_Website $website
     * @return array
     */
    public function getFilters(Mage_Core_Model_Website $website) {
        $values = Mage::getResourceModel('choiceai_searchcore/config')->getValues($website->getWebsiteId(),
            ChoiceAI_Searchcore_Model_Config::FILTER);
        $filters = array();
        foreach($values as $value) {
            $explodedValues = explode(ChoiceAI_Searchcore_Model_Config::FILTER_DELIMITER, $value);
            if(sizeof($explodedValues) < 2) {
                continue;
            }
            $filters[$explodedValues[0]] = $explodedValues[1];
        }
        return $filters;
    }

    public function saveGlobalConfig(Mage_Core_Model_Website $website, $values = array()) {
	foreach($values as $key => $value ) {
	}
    }

    public function deleteAll($websiteId) {
        $write = Mage::getSingleton("core/resource")->getConnection("core_write");
        $query = "DELETE FROM choiceai_recommendation_conf WHERE " . ChoiceAI_Searchcore_Model_Config::WEBSITE_ID . " = :website_id";
        $binds = array(
            'website_id' => $websiteId
        );
        $write->query($query, $binds);
    }

    public function getGlobalConfig(Mage_Core_Model_Website $website, $keys =array()) {
    }
}

?>
