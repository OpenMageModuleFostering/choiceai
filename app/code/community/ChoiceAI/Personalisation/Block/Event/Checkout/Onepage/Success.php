<?php

/**
 * @category    ChoiceAI
 * @package     ChoiceAI_Personalisation
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Personalisation_Block_Event_Checkout_Onepage_Success extends Mage_Core_Block_Template
{

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('choiceai/personalisation/event/checkout/onepage/success.phtml');
    }

    public function getOrderInfo()
    {
        try {
            $lastOrderId = Mage::getSingleton('checkout/session')->getLastOrderId();

            $order = Mage::getSingleton('sales/order');
            $order->load($lastOrderId);


            if ($order && $order->getId()) {
                $orderInfo['items'] = array();

                $orderedItems = $order->getAllItems();

                foreach ($orderedItems as $item) {
                    if ($item->getProductType() == "bundle") {
                        $orderInfo['items'][$item->getItemId()] = array(
                            'id' => $item->getProductId(),
                            'parentId' => '',
                            'sku' => $item->getSku(),
                            'qty' => $item->getQtyOrdered(),
                            'price' => $item->getPrice(),
                            'bundle' => array()
                        );
                    } else if ($item->getProductType() != "configurable") {
                        if ($orderInfo['items'][$item->getParentItemId()] != null) {
                            $bundleItems = $orderInfo['items'][$item->getParentItemId()]['bundle'];
                            $bundleItem = array(
                                'pid' => $item->getProductId(),
                                'sku' => $item->getSku(),
                                'qty' => $item->getQtyOrdered(),
                                'price' => $item->getPrice()
                            );
                            $bundleItems[] = $bundleItem;
                            $orderInfo['items'][$item->getParentItemId()]['bundle'] = $bundleItems;
                        } else {
                            $parentId = '';
                            $parentIds = Mage::getModel('catalog/product_type_configurable')->
                            getParentIdsByChild($item->getProductId());
                            if ($parentIds != null && !empty($parentIds)) {
                                $parentId = $parentIds[0];
                            }

                            $orderInfo['items'][] = array(
                                'id' => $item->getProductId(),
                                'parentId' => $parentId,
                                'sku' => $item->getSku(),
                                'qty' => $item->getQtyOrdered(),
                                'price' => $item->getPrice(),
                                'bundle' => array()
                            );
                        }
                    }
                }

                $orderInfo['orderId'] = $order->getIncrementId();
                $orderInfo['email'] = $order->getCustomerEmail();
                $orderInfo['createdAt'] = $order->getCreatedAt();

                $currency = $order->getOrderCurrency();
                if (is_object($currency)) {
                    $orderInfo['currency'] = $currency->getCurrencyCode();
                }

                $paymentMethod = $order->getPayment()->getMethodInstance()->getTitle();
                $orderInfo['paymentMethod'] = $paymentMethod;

                return $orderInfo;
            }
        } catch (Exception $e) {
            return null;
        }

        return null;
    }

    protected function _toHtml()
    {
        if (!$this->helper('choiceai_personalisation')->isModuleOutputEnabled() || !$this->isOrderConfirmation()) {
            return '';
        }

        return parent::_toHtml();
    }

    protected function isOrderConfirmation()
    {
        return strpos($this->_getRouteName(), 'checkout') !== false
            && $this->_getActionName() == 'success';
    }

    protected function _getRouteName()
    {
        return $this->getRequest()->getRequestedRouteName();
    }

    protected function _getActionName()
    {
        return $this->getRequest()->getRequestedActionName();
    }

}
