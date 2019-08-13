<?php
/**
 * Created by PhpStorm.
 * User: harkirat
 * Date: 16/5/17
 * Time: 6:04 PM
 */

class ChoiceAI_Search_Block_Catalog_Product_List_Toolbar extends Mage_Catalog_Block_Product_List_Toolbar
{

    const IS_ACTIVE = 'choiceai_personalisation/settings/active';

    /**
     * Set default Order field
     *
     * @param string $field
     * @return Mage_Catalog_Block_Product_List_Toolbar
     */
    public function getCurrentOrder()
    {
        $order = Mage::app()->getRequest()->getQuery('order');
        $session = Mage::getSingleton('core/session');
        // To set currently selected sort by option to extended/overridden one, in case of search/productlist
        if (Mage::helper('choiceai_search')->isActiveEngine()) {
            if ((!isset($order) || $order === null) && isset($session['plist_sort_by'])) {
                return $session['plist_sort_by'];
            } else if (isset($session['plist_sort_by']) && isset($order) && $order == $session['plist_sort_by']) {
                return $session['plist_sort_by'];
            } else {
                return parent::getCurrentOrder();
            }
        } else {
            return parent::getCurrentOrder();
        }
    }
}