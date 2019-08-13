<?php
/**
 * Created by PhpStorm.
 * User: harkirat
 * Date: 16/5/17
 * Time: 6:04 PM
 */

class ChoiceAI_Search_Block_Catalog_Product_List_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar {

    const IS_ACTIVE = 'choiceai_personalisation/settings/active';

    /**
     * Set default Order field
     *
     * @param string $field
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    public function getCurrentOrder()
    {
        // To set currently selected sort by option to extended/overridden one, in case of search/productlist
        if(Mage::helper('choiceai_search')->isActiveEngine()) {
            if ((!isset($_REQUEST['order']) ||is_null($_REQUEST['order'])) && isset($_SESSION['plist_sort_by'])) {
                return $_SESSION['plist_sort_by'];
            } else if (isset($_SESSION['plist_sort_by']) && isset($_REQUEST['order']) && $_REQUEST['order'] == $_SESSION['plist_sort_by']) {
                return $_SESSION['plist_sort_by'];
            } else{
                return parent::getCurrentOrder();
            }
        } else {
            return parent::getCurrentOrder();
        }
    }
}