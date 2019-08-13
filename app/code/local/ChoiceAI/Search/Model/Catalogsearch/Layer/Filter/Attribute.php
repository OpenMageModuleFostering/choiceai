<?php

/**
 *
 * @package ChoiceAI_Search
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ChoiceAI_Search_Model_Catalogsearch_Layer_Filter_Attribute extends
    ChoiceAI_Search_Model_Catalog_Layer_Filter_Attribute
{
    protected function _getIsFilterableAttribute($attribute)
    {
        return $attribute->getIsFilterableInSearch();
    }
}
