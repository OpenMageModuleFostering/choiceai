<?php
/**
 * Created by PhpStorm.
 * User: harkirat
 * Date: 30/6/17
 * Time: 8:22 PM
 */

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magento.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magento.com for more information.
 *
 * @category    Mage
 * @package     Mage_Catalog
 * @copyright  Copyright (c) 2006-2017 X.commerce, Inc. and affiliates (http://www.magento.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Product Url model
 *
 * @category   Mage
 * @package    Mage_Catalog
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class ChoiceAI_Search_Model_Catalog_Product_Url extends Mage_Catalog_Model_Product_Url
{

    /**
     * Retrieve Product URL
     *
     * @param  Mage_Catalog_Model_Product $product
     * @param  bool $useSid forced SID mode
     * @return string
     */
//    public function getProductUrl($product, $useSid = null)
//    {
//        return parent::getProductUrl($product, $useSid);
//        $toAppend = "cu=cai";
//
//        if (Mage::helper('choiceai_search')->isActiveEngine()) {
//            // append cu=cai to param
//            // contains params already
//            if(strpos($prodUrl, "?") !== false){
//                // only ? is there which is also the last character of the url
//                if(substr($prodUrl, -1) == "?")
//                    return $prodUrl.$toAppend;
//                else
//                    return $prodUrl."&".$toAppend;
//            } else{
//                return $prodUrl."?".$toAppend;
//            }
//        } else
//            return $prodUrl;
//    }

}
