<?php

/**
 * @category ChoiceAI
 * @package ChoiceAI_Searchcore
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Searchcore_Model_Observer {

    /**
     * Observer method to track the add to cart
     * @return $this
     */
    public function trackAddToCart(Varien_Event_Observer $observer) {
	if(!Mage::helper('choiceai_searchcore')->isExecutable()) {
		return;
	}
        $product = $observer->getEvent()->getProduct();
        if(!$product instanceof Mage_Catalog_Model_Product) {
            Mage::helper('choiceai_searchcore')->log(Zend_Log::ERR, 'CART_TRACKER:product is not a valid type');
            return $this;
        }
	$uniqueId = Mage::helper('choiceai_searchcore/feedhelper')->getUniqueId($product);
        $response = Mage::getModel('choiceai_searchcore/api_task_trackcart')
            ->setData('data', array('pid' => $uniqueId,
                'visit_type' => 'repeat'))
            ->setData('ip', isset($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'])
            ->setData('agent', $_SERVER['HTTP_USER_AGENT'])
            ->prepare(Mage::app()->getWebsite())
            ->process();
	Mage::helper('choiceai_searchcore')->log(Zend_Log::DEBUG, "CART_TRACKER: request with uniqueId ".$uniqueId);
        if(!$response->isSuccess()) {
            Mage::helper('choiceai_searchcore')
                ->log(Zend_Log::ERR, 'CART_TRACKER:request failed because ' .json_encode($response->getErrors()));
        }
		return $this;
	}

    /**
     * Observer method to track orders
     * @return $this
     */
    public function trackOrder(Varien_Event_Observer $observer) {
	if(!Mage::helper('choiceai_searchcore')->isExecutable()) {
        	return;
        }
        $payment = $observer->getEvent()->getPayment();
        /* @var Mage_Sales_Model_Order_Payment */

        if(!$payment instanceof Mage_Sales_Model_Order_Payment) {
            Mage::helper('choiceai_searchcore')->log(Zend_Log::ERR, 'ORDER_TRACKER:payment is not a valid type');
            return $this;
        }
        $items = $payment->getOrder()->getAllVisibleItems();

        if(!is_array($items)) {
            return $this;
        }

        foreach($items as $item) {
            if($item instanceof Mage_Sales_Model_Order) {
                Mage::helper('choiceai_searchcore')
                    ->log(Zend_Log::ERR, 'ORDER_TRACKER:request failed because item is of instancetype ' . get_class($item));
                continue;
            }
            $product =$item->getProduct();
            if(!$product instanceof Mage_Catalog_Model_Product) {
                return $this;
            }
	    $uniqueId = Mage::helper('choiceai_searchcore/feedhelper')->getUniqueId($product, $item);
            $response = Mage::getModel('choiceai_searchcore/api_task_trackorder')
                ->setData('data',
                    array('visit_type' => 'repeat',
                        'pid' => $uniqueId,
                        'qty' => $item->getQtyOrdered(),
                        'price' => $item->getPriceInclTax()))
                ->setData('ip', isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'])
                ->setData('agent', $_SERVER['HTTP_USER_AGENT'])
                ->prepare(Mage::app()->getWebsite())
                ->process();
	    Mage::helper('choiceai_searchcore')->log(Zend_Log::DEBUG, "ORDER_TRACKER: request with uniqueId ".$uniqueId);

	    if(!$response->isSuccess()) {
                Mage::helper('choiceai_searchcore')
                    ->log(Zend_Log::ERR, 'ORDER_TRACKER:request failed because ' . json_encode($response->getErrors()));
            }
            Mage::getSingleton('choiceai_searchcore/sync')->addProduct($product);
        }
        return $this;
	}

    /**
     * Method to sync the product catalog through cron
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function syncFull($observer)
    {
	if(!Mage::helper('choiceai_searchcore')->isExecutable()) {
                  return;
        }
        $websiteCollection = Mage::getModel('core/website')->getCollection()->load();
        if(!$websiteCollection instanceof Varien_Data_Collection) {
            return $this;
        }
        foreach ($websiteCollection as $website) {
            if($website instanceof Mage_Core_Model_Website) {
                return $this;
            }
            Mage::getResourceModel('choiceai_searchcore/config')
                ->setValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Constants::IS_CRON_ENABLED, 1);
            Mage::getSingleton('choiceai_searchcore/feed_feedmanager')->process(true, $website);
        }
        return $this;
    }

    /* 
     * Method to sync the product incremental catalog through cron
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function syncIncremental($observer)
    {
        if(!Mage::helper('choiceai_searchcore')->isExecutable()) {
                  return;
        }
        $websiteCollection = Mage::getModel('core/website')->getCollection()->load();
        if(!$websiteCollection instanceof Varien_Data_Collection) {
            return $this;
        }
        foreach ($websiteCollection as $website) {
            if($website instanceof Mage_Core_Model_Website) {
                return $this;
            }
            Mage::getResourceModel('choiceai_searchcore/config')
               ->setValue($website->getWebsiteId(), ChoiceAI_Searchcore_Helper_Constants::IS_CRON_ENABLED, 1);
            Mage::getSingleton('choiceai_searchcore/feed_feedmanager')->process(false, $website);
       }
       return $this;
   }

    /**
     * Method to track deleted product
     * @param Varien_Event_Observer $observer
     * @return void
     */
    public function trackDelete(Varien_Event_Observer $observer) {
	    if(!Mage::helper('choiceai_searchcore')->isExecutable()) {
            return $this;
        }
        $product = $observer->getEvent()->getDataObject();
        if(!$product instanceof Mage_Catalog_Model_Product) {
            return $this;
        }
        $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId());
        if(!$parentIds)
            $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());

        if(!is_array($parentIds)) {
            return $this;
        }

        foreach($parentIds as $parentId) {
            $parentProduct = Mage::getModel('catalog/product')->load($parentId);
            if($parentProduct instanceof Mage_Catalog_Model_Product) {
                return $this;
            }
            Mage::getSingleton('choiceai_searchcore/sync')->addProduct($parentProduct);
        }
        Mage::getSingleton('choiceai_searchcore/sync')->deleteProduct($product);
        return $this;
    }

    /**
      * Method to track deleted product
      * @param Varien_Event_Observer $observer
      * @return void
      */

    public function catalogInventorySave(Varien_Event_Observer $observer) {
	    if(!Mage::helper('choiceai_searchcore')->isExecutable()) {
            return;
        }
        $_item = $observer->getEvent()->getItem();
        if(!$_item instanceof Mage_Catalog_Order_Item) {
            return $this;
        }
        $product = $_item->getProduct();
        if(!$product instanceof Mage_Catalog_Model_Product) {
            return $this;
        }
        Mage::getSingleton('choiceai_searchcore/sync')->addProduct($product);
        return $this;
    }

    public function saleOrderCancel(Varien_Event_Observer $observer) {
        return $this;
    }
}
?>
