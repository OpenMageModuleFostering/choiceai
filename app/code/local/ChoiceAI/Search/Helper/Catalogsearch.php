<?php

/**
 * @category ChoiceAI
 * @package ChoiceAI_Recommendation
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Search_Helper_Catalogsearch extends Mage_CatalogSearch_Helper_Data {

    public function getResultUrl($query = null) {
		if(Mage::helper('choiceai_search')->isHostedSearchActive()) {
			$redirectUrl = Mage::helper('choiceai_search')->getHostedRedirectUrl();
			return $redirectUrl . ((!is_null($query) && $query != "")?($this->getQueryParamName()."=".$query):"");
		}
		return parent::getResultUrl($query);
    }

}
