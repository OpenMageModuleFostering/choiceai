<?php

/**
 * This class maintains all the configuration w.r.t ChoiceAI with site basis
 *
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Config extends Mage_Core_Model_Abstract {

    const KEY = "choiceai_key";

    const VALUE = 'value';

    const WEBSITE_ID = 'website_id';

    const FEED_LOCK_TIME = 'feed_lock_time';

    const FEED_LOCK = 'feed_lock';

    const FEED_LOCK_TRUE = '1';

    const FEED_LOCK_FALSE = '0';

    const FEED_STATUS = 'feed_status';

    const MAX_FEED_LOCK_TIME = 6;

    const LAST_UPLOAD_TIME = 'lastUpload';

    const FILTER_DELIMITER = "|`";

    const FILTER = 'filter';

    /**
     *
     * @return void
     */
    protected function _construct()
	{
		$this->_init('choiceai_searchcore/config');
	}
}

?>
