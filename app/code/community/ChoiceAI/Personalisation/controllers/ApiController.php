<?php

/**
 * @category    ChoiceAI
 * @package     ChoiceAI_Personalisation
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ChoiceAI_Personalisation_ApiController extends Mage_Core_Controller_Front_Action {

    const CONFIG_API_KEY = 'choiceai_personalisation/settings/api_key';
    const CONFIG_KEY = 'choiceai_personalisation/settings/config';
    const API_VERSION = 3;

    public function _authorise() {

        $API_KEY = Mage::getStoreConfig(self::CONFIG_API_KEY);

        // Check for api access
        if(!$API_KEY && strlen($API_KEY) === 0) {
            // Api access disabled
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'API access disabled', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(403)
                ->setHeader('Content-type', 'application/json', true);
            return false;
        }

        $authHeader = $this->getRequest()->getHeader('mwauth');

        // fallback
        if (!$authHeader || strlen($authHeader) == 0) {
            $authHeader = $this->getRequest()->getParam('mwauth');
        }

        if (!$authHeader) {
            Mage::log('Unable to extract authorization header from request', null, 'choiceai.log');
            // Internal server error
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error, Authorization header not found', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
            return false;
        }

        if(trim($authHeader) !== trim($API_KEY)) {
            // Api access denied
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Api access denied', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(401)
                ->setHeader('Content-type', 'application/json', true);
            return false;
        }

        return true;

    }

    public function configAction() {
        try {
            if(!$this->_authorise()) {
                return $this;
            }

            $responseObj = array();

            if ($_SERVER["REQUEST_METHOD"] == "GET") {
                $STORE_CONFIG = Mage::getStoreConfig(self::CONFIG_KEY);
                $responseObj["status"] = "ok";
                $responseObj["config"] = json_decode($STORE_CONFIG);
            } else if ($_SERVER["REQUEST_METHOD"] == "PUT") {
                $store_config = $this->getRequest()->getParam('config');
                if($store_config ==""){
                    $input = file_get_contents('php://input');
                    $input = utf8_encode($input);
                    $store_config = json_encode(json_decode($input)->config);
                }
                $store_config_json = json_decode($store_config);
                if ($store_config_json == NULL) {
                    throw new Exception();
                }
                Mage::getModel('core/config')->saveConfig(self::CONFIG_KEY, $store_config);
                Mage::app()->getStore()->resetConfig();
                $responseObj["status"] = "ok";
            } else {
                $responseObj['status'] = 'error';
                $responseObj['message'] = 'Invalid request';
            }

            $responseObj['version'] = self::API_VERSION;
            $this->getResponse()
                ->setBody(json_encode($responseObj))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);

        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return $this;
    }

    public function ordersAction() {
        try {
            if(!$this->_authorise()) {
                return $this;
            }

            $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

            if(isset($sections[3])) {
                // Looking for a specific order
                $orderId = $sections[3];

                $order = Mage::getModel('sales/order')->load($orderId, 'increment_id');

                $extras = $this->getRequest()->getParam('extras');
                $debug = $this->getRequest()->getParam('debug', 'false') === 'true';
                if($extras && strlen($extras)) {
                    $extras = explode(',', $extras);
                    for($i = 0;$i < sizeof($extras);$i++) {
                        $extras[$i] = trim($extras[$i]);
                    }
                }

                $items = array();

                $orderItems = $order->getItemsCollection()->load();

                foreach($orderItems as $key => $orderItem) {
                    $items[] = array(
                        'name'  =>  $orderItem->getName(),
                        'pid'   =>  $orderItem->getProductId(),
                        'sku'   =>  $orderItem->getSku(),
                        'qty'   =>  $orderItem->getQtyOrdered(),
                        'price' =>  $orderItem->getPrice()
                    );
                }

                $responseObj = array(
                    'order_id' => $orderId,
                    'items' => $items,
                    'ip' => $order->getRemoteIp()
                );

                $attributes = $order->debug();
                if($debug) {
                    $responseObj['extras'] = $attributes;
                } else {
                    foreach($extras as $key) {
                        $responseObj['extras'][$key] = $attributes[$key];
                    }
                }

                $responseObj['version'] = self::API_VERSION;
                $this->getResponse()
                    ->setBody(json_encode($responseObj))
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-type', 'application/json', true);
            } else {
                // Looking for a list of orders
                $currentTime = time();
                $fromDate = $this->getRequest()->getParam('fromDate', date('Y-m-d', ($currentTime - 86400)));
                $toDate = $this->getRequest()->getParam('toDate', date('Y-m-d', $currentTime));

                $orders = array();

                $ordersCollection = Mage::getModel('sales/order')->getCollection()
                    //->addFieldToFilter('status', 'complete')
                    ->addAttributeToSelect('customer_email')
                    ->addAttributeToSelect('created_at')
                    ->addAttributeToSelect('increment_id')
                    ->addAttributeToSelect('status')
                    ->addAttributeToFilter('created_at', array('from' => $fromDate, 'to' => $toDate))
                ;

                foreach ($ordersCollection as $order) {
                    $orders[] = array(
                        'order_id'      =>  $order->getIncrementId(),
                        'status'        =>  $order->getStatus(),
                        'email'         =>  $order->getCustomerEmail(),
                        'created_at'    =>  $order->getCreatedAt()
                    );
                }

                $this->getResponse()
                    ->setBody(json_encode(array('orders' => $orders, 'fromDate' => $fromDate, 'toDate' => $toDate, 'version' => self::API_VERSION)))
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-type', 'application/json', true);
            }
        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return $this;
    }

    public function productattributesAction() {
        try {
            if(!$this->_authorise()) {
                return $this;
            }

            $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));

            if(!isset($sections[3])) {
                // product id
                throw new Exception();
            }

            $productId = $sections[3];

            $product = Mage::getModel('catalog/product')->load($productId);

            $product_info = array();

            $product_info["is_available"] = $product->isAvailable();
            $product_info["name"] = $product->getName();
            $product_info["id"] = $product->getId();
            $product_info["sku"] = $product->getSku();
            $product_info["price"] = $product->getPrice();
            $product_info["final_price"] = $product->getFinalPrice();
            $product_info["special_price"] = $product->getSpecialPrice();
            $product_info["type"] = $product->getTypeId();

            $variants = array();
            $options = array();

            if ($product->getTypeId() == "configurable") {

                $productAttributeOptions = $product->getTypeInstance(true)->getConfigurableAttributesAsArray($product);
                $attributeTypes = array();

                foreach ($productAttributeOptions as $productAttribute) {

                    $attributes = array();
                    foreach ($productAttribute['values'] as $attribute) {
                        $attributes[] = array(
                            "id" => $attribute["value_index"],
                            "name" => $attribute["store_label"]
                        );
                    }

                    $attributeType = $productAttribute["attribute_code"];
                    $options[] = array(
                        "id" => $productAttribute["id"],
                        "key" => $attributeType,
                        "name" => $productAttribute["store_label"],
                        "values" => $attributes,
                        "position" => $productAttribute["position"]
                    );

                    $attributeTypes[] = $attributeType;

                }

                $associatedProducts = $product->getTypeInstance()->getUsedProducts();
                foreach ($associatedProducts as $associatedProduct) {

                    $variant = array();
                    $variant["is_available"] = $associatedProduct->isAvailable();
                    $variant["id"] = $associatedProduct->getId();
                    $variant["sku"] = $associatedProduct->getSku();
                    $variant["price"] = $associatedProduct->getPrice();
                    $variant["final_price"] = $associatedProduct->getFinalPrice();
                    $variant["special_price"] = $associatedProduct->getSpecialPrice();

                    $associatedProductData = $associatedProduct->getData();
                    foreach ($attributeTypes as $attributeType) {
                        $variant[$attributeType] = $associatedProductData[$attributeType];
                    }

                    $variants[] = $variant;
                }
            }

            $this->getResponse()
                ->setBody(json_encode(array('product' => $product_info, 'variants' => $variants, 'options' => $options, 'version' => self::API_VERSION)))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);
        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }
        return $this;
    }

    public function productsAction() {
        try {
            if(!$this->_authorise()) {
                return $this;
            }

            $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
            $products = array();

            $attributes = array(
                'name',
                'sku',
                'image',
                'manufacturer',
                'price',
                'final_price',
                'special_price',
                'short_description'
            );

            $extras = $this->getRequest()->getParam('extras');
            $debug = $this->getRequest()->getParam('debug', 'false') === 'true';

            if($extras && strlen($extras)) {
                $extras = explode(',', $extras);
                for($i = 0;$i < sizeof($extras);$i++) {
                    $extras[$i] = trim($extras[$i]);
                    $attributes[] = $extras[$i];
                }
            }

            if(isset($sections[3])) {
                // Looking for a specific product
                $productId = $sections[3];

                $product = Mage::getModel('catalog/product')->load($productId);

                $product = $this->getFormattedProduct($product, $extras, $debug);
                if($product !== null) {
                    $products[] = $product;
                }else{
                    throw new Exception("Failed to fetch the product");
                }

            } else {
                // Looking for a list of products
                $limit = $this->getRequest()->getParam('limit', 100);
                $offset = $this->getRequest()->getParam('offset', 1);

                $productsCollection = Mage::getModel('catalog/product')->getCollection();

                // Get only enabled products
                $productsCollection
                    ->addAttributeToSelect($attributes)
                    ->addAttributeToFilter('status', array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED))
                    ->getSelect()->limit($limit, $offset)   //we can specify how many products we want to show on this page
                ;

                foreach($productsCollection as $product) {
                    $product = $this->getFormattedProduct($product, $extras, $debug);
                    if($product !== null) {
                        $products[] = $product;
                    }else{
                        throw new Exception("Failed to fetch the product");
                    }
                }

            }

            $currency = Mage::app()->getStore()->getCurrentCurrencyCode();

            $this->getResponse()
                ->setBody(json_encode(array('products' => $products, 'currency' => $currency, 'version' => self::API_VERSION)))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);


        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return $this;
    }


    public function categoriesAction() {
        try {
            if(!$this->_authorise()) {
                return $this;
            }

            $attributes = array(
                'id',
                'name',
                'image',
                'url',
                'level',
                'path',
                'is_anchor',
                'is_active',
                'created_at',
                'updated_at'
            );

            $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
            $categories = array();

            $level = $this->getRequest()->getParam('level');
            $active = $this->getRequest()->getParam('active', 'false') === 'true';
            $isAnchor = $this->getRequest()->getParam('is_anchor', 'false') === 'true';

            if($level && strlen($level)) {
                $level = intval($level);
            } else {
                $level = null;
            }

            if(isset($sections[3])) {
                // Looking for a specific category
                $categoryId = $sections[3];

                $category = Mage::getModel('catalog/category')->load($categoryId);

                $category = $this->getFormattedCategory($category);
                if($category !== null && $category->id != null && is_array($category)) {
                    $categories[] = $category;
                } else{
                    throw new Exception("Category ".$categoryId." not found");
                }

            } else {
                // Looking for a list of categories
                $limit = $this->getRequest()->getParam('limit', 100);
                $offset = $this->getRequest()->getParam('offset', 1);

                $categoriesCollection = Mage::getModel('catalog/category')->getCollection();

                if($level != null) {
                    $categoriesCollection
                        ->addAttributeToFilter('level', $level) //we can specify the level of categories to be fetched
                    ;
                }

                if($active != null) {
                    $categoriesCollection
                        ->addAttributeToFilter('is_active', 1) //if you want only active categories
                    ;
                }

                if($isAnchor != null) {
                    $categoriesCollection
                        ->addAttributeToFilter('is_anchor', 1) // If you want categories which are shown in search facets
                    ;
                }

                $categoriesCollection
                    ->addAttributeToSelect($attributes)
                    ->getSelect()->limit($limit, $offset)   //we can specify how many categories we want to show on this page
                ;

                foreach($categoriesCollection as $category) {
                    $category = $this->getFormattedCategory($category);
                    if($category !== null && is_array($category)) {
                        $categories[] = $category;
                    }
                }

            }

            $this->getResponse()
                ->setBody(json_encode(array('categories' => $categories, 'version' => self::API_VERSION)))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);

        } catch(\Exception $e) {
            if($e->getMessage() != null)
                $message = $e->getMessage();
            else
                $message = 'Internal server error';

            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => $message, 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return $this;
    }


    public function facetattributesAction() {
        try {
            if(!$this->_authorise()) {
                return $this;
            }

            $limit = $this->getRequest()->getParam('limit', 100);
            $page = $this->getRequest()->getParam('page', 1);
            $fields = $this->getRequest()->getParam('fields', false);
            $isFilterable = $this->getRequest()->getParam('is_filterable', false);

            $collection = Mage::getResourceModel('catalog/product_attribute_collection');

            if($isFilterable && is_numeric($isFilterable)){
                $collection->addFieldToFilter('is_filterable', (int) $isFilterable);
            } else{
//              Give only is filterable with results
                $collection->addFieldToFilter('is_filterable', 1);
            }

//            $fieldsToSelect = array(
//                "attribute_id",
//                "is_filterable",
//                "attribute_code",
//                "frontend_label",
//                "is_visible",
//                "is_visible_on_front",
//                "is_user_defined",
//                "is_required",
//                "is_searchable",
//                "is_filterable_in_search",
//                "position",
//                "is_used_for_promo_rules",
//                "is_used_for_price_rules",
//                "used_in_product_listing"
//            );
//            //@TODO: Mysql error coming up
//            $collection->addFieldToSelect($fieldsToSelect);

            $collection->setOrder('position', 'ASC');
            $collection->setPageSize((int) $limit);
            $collection->setCurPage((int) $page);
            $collection->load();

            if($limit * ($page-1) < $collection->getSize()) {
                $allAttrs = array();

                foreach ($collection as $attr) {
                    $newAttr = array();
                    if($fields=="all") {
                        $newAttr['attribute_id'] = $attr->getAttributeId();
                        $newAttr['is_filterable'] = $attr->getIsFilterable();
                        $newAttr['attribute_code'] = $attr->getAttributeCode();
                        $newAttr['frontend_label'] = $attr->getFrontendLabel();
                        $newAttr['is_visible'] = $attr->getIsVisible();
                        $newAttr['is_visible_on_front'] = $attr->getIsVisibleOnFront();
                        $newAttr['is_user_defined'] = $attr->getIsUserDefined();
                        $newAttr['is_required'] = $attr->getIsRequired();
                        $newAttr['is_searchable'] = $attr->getIsSearchable();
                        $newAttr['is_filterable_in_search'] = $attr->getIsFilterableInSearch();
                        $newAttr['position'] = $attr->getPosition();
                        $newAttr['is_used_for_promo_rules'] = $attr->getIsUsedForPromoRules();
                        $newAttr['is_used_for_price_rules'] = $attr->getIsUsedForPriceRules();
                        $newAttr['used_in_product_listing'] = $attr->getUsedInProductListing();
                    } else{
                        $newAttr['attribute_id'] = $attr->getAttributeId();
                        $newAttr['is_filterable'] = $attr->getIsFilterable();
                        $newAttr['attribute_code'] = $attr->getAttributeCode();
                        $newAttr['frontend_label'] = $attr->getFrontendLabel();
                        $newAttr['is_visible'] = $attr->getIsVisible();
                        $newAttr['is_visible_on_front'] = $attr->getIsVisibleOnFront();
                        $newAttr['is_searchable'] = $attr->getIsSearchable();
                        $newAttr['is_filterable_in_search'] = $attr->getIsFilterableInSearch();
                        $newAttr['position'] = $attr->getPosition();
                        $newAttr['used_in_product_listing'] = $attr->getUsedInProductListing();
                    }

                    $allAttrs[] = $newAttr;
                }
            } else{
                $allAttrs = array();
            }

            $this->getResponse()
                ->setBody(json_encode(array('attributes' => $allAttrs, 'version' => self::API_VERSION)))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);
        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return $this;
    }


    public function modifyfacetattributeAction() {
        try {
            if(!$this->_authorise()) {
                return $this;
            }

            $facetData = $this->getRequest()->getParam('newData', false);
            $newFacetData = json_decode($facetData);

            if(!$newFacetData){
                if ($facetData == "") {
                    $input = file_get_contents('php://input');
                    $input = utf8_encode($input);
                    $newFacetData = json_decode($input)->newData;
                }
            }

            if(!$newFacetData && !$newFacetData->attribute_id)
                throw new Exception("Insufficient data");

            // updates the facet
            $resourceModel = Mage::getResourceModel('catalog/product_attribute_collection');
            $attributeObj = $resourceModel->getItemById($newFacetData->attribute_id);

            // Not required anymore
            unset($newFacetData->attribute_id);

            foreach ($newFacetData as $key => $value) {
                $attributeObj->setData($key, $value);
            }
            $updateStatus = $attributeObj->save();

            if(!$updateStatus)
                throw new Exception("Couldn't update");
            else
                Mage::app()->getCache()->save(null, "sysFacets", array("facets"));

            $this->getResponse()
                ->setBody(json_encode(array("status"=>"ok", 'version' => self::API_VERSION)))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);
        } catch(Exception $e) {
            if($e->getMessage())
                $errorMsg = $e->getMessage();
            else
                $errorMsg = "Internal server error";

            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => $errorMsg, 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return $this;
    }

    // Formats the attribute for save
    private function _prepareAttributeForSave($data) {
        /** @var $helperCatalog Mage_Catalog_Helper_Data */
        $helperCatalog = Mage::helper('catalog');

        if ($data['scope'] == 'global') {
            $data['is_global'] = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL;
        } else if ($data['scope'] == 'website') {
            $data['is_global'] = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_WEBSITE;
        } else {
            $data['is_global'] = Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE;
        }

        if (!isset($data['is_configurable'])) {
            $data['is_configurable'] = 0;
        }
        if (!isset($data['is_filterable'])) {
            $data['is_filterable'] = 1;
        }
        if (!isset($data['is_filterable_in_search'])) {
            $data['is_filterable_in_search'] = 1;
        }
        if (!isset($data['apply_to'])) {
            $data['apply_to'] = array();
        }
        // set frontend labels array with store_id as keys
        if (isset($data['frontend_label']) && is_array($data['frontend_label'])) {
            $labels = array();
            foreach ($data['frontend_label'] as $label) {
                $storeId = $label['store_id'];
                $labelText = $helperCatalog->stripTags($label['label']);
                $labels[$storeId] = $labelText;
            }
            $data['frontend_label'] = $labels;
        }
        // set additional fields
        if (isset($data['additional_fields']) && is_array($data['additional_fields'])) {
            $data = array_merge($data, $data['additional_fields']);
            unset($data['additional_fields']);
        }
        //default value
        if (!empty($data['default_value'])) {
            $data['default_value'] = $helperCatalog->stripTags($data['default_value']);
        }

        return $data;
    }


    public function addfacetattributeAction() {
        try {
            if(!$this->_authorise()) {
                return $this;
            }

            $facetData = $this->getRequest()->getParam('newData', false);
            $newFacetData = json_decode($facetData);

            if(!$newFacetData){
                if ($facetData == "") {
                    $input = file_get_contents('php://input');
                    $input = utf8_encode($input);
                    $newFacetData = json_decode($input)->newData;
                }
            }
            $newFacetData = (array)$newFacetData;

            if (!$newFacetData || empty($newFacetData['attribute_code']) || !isset($newFacetData['frontend_label'])) {
                throw new Exception("Insufficient data");
            }

            //validate attribute_code
            if (!preg_match('/^[a-z][a-z_0-9]{0,254}$/', $newFacetData['attribute_code'])) {
                throw new Exception("Invalid attribute_code");
            }

            /** @var $model Mage_Catalog_Model_Resource_Eav_Attribute */
            $model = Mage::getModel('catalog/resource_eav_attribute');
            /** @var $helper Mage_Catalog_Helper_Product */
            $helper = Mage::helper('catalog/product');


            $newFacetData['source_model'] = $helper->getAttributeSourceModelByInputType('multiselect');
            $newFacetData['backend_model'] = $helper->getAttributeBackendModelByInputType('multiselect');
            if (is_null($model->getIsUserDefined()) || $model->getIsUserDefined() != 0) {
                $newFacetData['backend_type'] = $model->getBackendTypeByInput('multiselect');
            }

            $newFacetData = $this->_prepareAttributeForSave($newFacetData);

            $entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();

            $model->addData($newFacetData);
            $model->setEntityTypeId($entityTypeId);
            $model->setIsUserDefined(1);

            $model->save();

            Mage::app()->getCache()->save(null, "sysFacets", array("facets"));
            Mage::app()->cleanCache(array(Mage_Core_Model_Translate::CACHE_TAG));

            $this->getResponse()
                ->setBody(json_encode(array("status"=>"ok", 'version' => self::API_VERSION)))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);
        } catch(Exception $e) {
            if($e->getMessage())
                $errorMsg = $e->getMessage();
            else
                $errorMsg = "Internal server error";

            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => $errorMsg, 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return $this;
    }


    public function usersAction() {

        try {

            if(!$this->_authorise()) {
                return $this;
            }

            $email = $this->getRequest()->getParam('email');

            $limit = $this->getRequest()->getParam('limit', 100);
            $offset = $this->getRequest()->getParam('offset', 0);

            $attributes = array(
                'id',
                'email',
                'firstname',
                'lastname',
                'created_at',
                'updated_at'
            );

            $usersCollection = Mage::getModel('customer/customer')->getCollection();

            if($email != null && strlen($email) > 0) {

                $usersCollection
                    ->addAttributeToFilter('email', $email);

            } else {

                $created_at_min = $this->getRequest()->getParam('created_at_min');
                $created_at_max = $this->getRequest()->getParam('created_at_max');

                $usersCollection->addAttributeToSelect($attributes);

                if($created_at_min != null && strlen($created_at_min) > 0) {
                    $usersCollection->addAttributeToFilter('created_at', array('from' => $created_at_min));
                }

                if($created_at_max != null && strlen($created_at_max) > 0) {
                    $usersCollection->addAttributeToFilter('created_at', array('to' => $created_at_max));
                }

                $usersCollection->getSelect()
                    ->limit($limit, $offset);

            }

            $users = array();

            foreach ($usersCollection as $user) {
                $formattedUser = array();
                $formattedUser["id"] = $user->getId();
                $formattedUser["email"] = $user->getEmail();
                $formattedUser["firstname"] = $user->getFirstname();
                $formattedUser["lastname"] = $user->getLastname();
                $formattedUser["created_at"] = $user->getCreatedAt();
                $formattedUser["updated_at"] = $user->getUpdatedAt();
                $users[] = $formattedUser;
            }


            $this->getResponse()
                ->setBody(json_encode(array('users' => $users, 'version' => self::API_VERSION)))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);

        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return $this;

    }


    public function stockAction() {

        try {

            if(!$this->_authorise()) {
                return $this;
            }

            $productId = $this->getRequest()->getParam('pid');
            $sku = $this->getRequest()->getParam('sku', 'false') === 'true';


            if(!$productId || strlen($productId) <= 0) {

                $this->getResponse()
                    ->setBody(json_encode(array('status' => 'error', 'message' => 'product id required', 'version' => self::API_VERSION)))
                    ->setHttpResponseCode(500)
                    ->setHeader('Content-type', 'application/json', true);

            } else {

                // load product if sku is given
                if($sku) {
                    $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $productId);
                    if($product == null) {
                        $this->getResponse()
                            ->setBody(json_encode(array('status' => 'error', 'message' => 'invalid sku', 'version' => self::API_VERSION)))
                            ->setHttpResponseCode(500)
                            ->setHeader('Content-type', 'application/json', true);
                        return $this;
                    }
                    $productId = $product->getId();
                }

                // get stock info
                $stockObj = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);

                $stock = $stockObj->getQty();

                $this->getResponse()
                    ->setBody(json_encode(array('id' => $productId, 'stock' => $stock, 'version' => self::API_VERSION)))
                    ->setHttpResponseCode(200)
                    ->setHeader('Content-type', 'application/json', true);

            }

        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return $this;

    }


    private function getFormattedProduct($product, $extras, $debug) {

        $formattedProduct = null;

        try {
            $formattedProduct = array(
                'id'            =>  $product->getId(),
                'sku'           =>  $product->getSku(),
                'name'          =>  $product->getName(),
                'cat'           =>  array(),
                'manufacturer'  =>  $product->getAttributeText('manufacturer'),
                'price'         =>  $product->getPrice(),
                'final_price'   =>  $product->getFinalPrice(),
                'special_price' =>  $product->getSpecialPrice(),
                'image'         =>  $product->getImageUrl(),
                'url'           =>  $product->getProductUrl(),
                'info'          =>  $product->getShortDescription(),
                'status'        =>  $product->getStatus(),
                'type'            =>  $product->getTypeId(),
                'created_at'        =>  $product->getCreatedAt(),
                'updated_at'        =>  $product->getUpdatedAt()
            );
            if(!$formattedProduct['manufacturer'] || strlen($formattedProduct['manufacturer']) === 0) {
                $product = Mage::getModel('catalog/product')->load($product->getId());
                $formattedProduct['manufacturer'] = $product->getAttributeText('manufacturer');
            }

            if($formattedProduct['type'] == "configurable") {
                // get associated product ids
                $associatedProducts = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($formattedProduct['id']);
                $formattedProduct['associated_products'] = $associatedProducts;
            }

            // get stock info
            $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
            $formattedProduct['stock'] = $stock->getQty();

            if($debug) {
                $attributes = $product->getAttributes();
                foreach($attributes as $key => $value) {
                    // Sanity check, or else crashes without proper error handling
                    $existenceCheck = $product->getResource()->getAttribute($key);

                    if($existenceCheck)
                        $formattedProduct['extras'][$key] = $product->getAttributeText($key);
                }
            } else {
                foreach($extras as $key) {
                    // Sanity check, or else crashes without proper error handling
                    $existenceCheck = $product->getResource()->getAttribute($key);

                    if($existenceCheck)
                        $formattedProduct['extras'][$key] = $product->getAttributeText($key);
                }
            }

            $categories = $product->getCategoryCollection()
                ->addAttributeToSelect('id')
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('path')
                ->addAttributeToSelect('level');
            foreach($categories as $category) {
                $formattedCategory = array();
                $formattedCategory['id'] = $category->getId();
                $formattedCategory['name'] = $category->getName();
                $formattedCategory['level'] = $category->getLevel();
                $formattedCategory['path'] = $category->getPath();
                $formattedProduct['cat'][$formattedCategory['id']] = $formattedCategory;
            }

            return $formattedProduct;
        } catch(Exception $e) {
            return NULL;
        }
    }

    private function getFormattedCategory($category) {

        $formattedCategory = null;

        try {
            $formattedCategory = array(
                'id'            =>  $category->getId(),
                'name'          =>  $category->getName(),
                'image'         =>  $category->getImageUrl(),
                'url'           =>  $category->getUrl(),
                'level'         =>  $category->getLevel(),
                'path'          => $category->getPath(),
                'is_anchor'     =>  $category->getIsAnchor(),
                'is_active'     =>  $category->getIsActive(),
                'created_at'    =>  $category->getCreatedAt(),
                'updated_at'    =>  $category->getUpdatedAt()
            );

        } catch(Exception $e) {}

        return $formattedCategory;
    }

    public function sortbyAction() {

        try {
            if(!$this->_authorise()) {
                return $this;
            }

            $sortByOptions = array();
//            $attributesData = Mage::getResourceModel('catalog/config')->getAttributesUsedForSortBy();

//            foreach ($attributesData as $attributeData) {
//                $sortByOptions[$attributeData['attribute_code']] = array(
//                    "attribute_id"=> $attributeData['attribute_id'],
//                    "attribute_code"=> $attributeData['attribute_code'],
//                    "frontend_label"=> $attributeData['frontend_label'],
//                    "store_label"=> $attributeData['store_label']
//                );
//            }

            $category = Mage::getModel('catalog/category');

            $attributesData = $category->getAvailableSortByOptions();
            $defaultSort = $category->getDefaultSortBy();

            $i = 1;

            foreach ($attributesData as $key => $attributeData) {
                $sortByOptions[] = array(
                    "_id"=> $key,
                    "name"=> $attributeData,
                    "default"=> $key==$defaultSort ? true:false,
                    "order"=> $i
                );
                $i++;
            }

            $this->getResponse()
                ->setBody(json_encode(array('status' => "ok", 'options' => $sortByOptions, 'version' => self::API_VERSION)))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);

        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('status' => 'error', 'message' => 'Internal server error', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return $this;
    }

    public function choicepageAction() {
        try{
            if(!$this->_authorise()) {
                return $this;
            }

            $responseObj = array();

            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                // Create Choice page
                // Expects title, url_path and content

                $pageData = $this->getRequest()->getParam('data');
                if ($pageData == "") {
                    $input = file_get_contents('php://input');
                    $input = utf8_encode($input);
                    $pageData = json_encode(json_decode($input)->data);
                }
                $pageData = json_decode($pageData);
                $urlPath = $pageData->url_path;

                if(!$urlPath){
                    // What can this poor guy do without the path?
                    throw new Exception("No urlPath");
                }

                // Ensure no other existing page contains the same URL path or say "identifier"
                $collection = Mage::getModel('cms/page')
                    ->getCollection()
                    ->addFieldToFilter('identifier', $urlPath);
//                Not useful anymore
//                $existingPages = Mage::getModel('cms/page')->load($collection->getFirstItem()->getId());

                // There can be multiple pages with same url path, but different store scope. Let's disable all
                foreach ($collection as $existingPage){
                    // Page already exists && is_active
                    if($existingPage->getId()) {
                        // Delete all other pages having same URL
                        // and then continue adding our new page
//                        $this->updateChoiceUrl(false, $existingPage->getId(), array("is_active" => 0, "identifier" => $urlPath));
                        $existingPage->delete();
                    }
                }

                // Setting default content if not provided
                $pageContent = (!isset($pageData->content)) ? "<script>window._caichoicePage = true;</script><div id='caichoicePage'></div>" : $pageData->content;

                // Setting default Title if not available
                $pageTitle = (!isset($pageData->title)) ? "Choice.AI" : $pageData->title;


                $choicePageData = array(
                    'title' => $pageTitle,
                    'root_template' => 'one_column',
                    //'meta_keywords' => 'meta,keywords',
                    //'meta_description' => 'meta description',
                    'identifier' => $urlPath,
                    //'content_heading' => 'content heading',
                    'stores' => array(0),//available for all store views
                    'content' => $pageContent
                );

                // Create new page
                $choicePage = Mage::getModel('cms/page')->setData($choicePageData)->save();

                $responseObj["status"] = "ok";
                $responseObj["id"] = $choicePage->getId();
            } else if ($_SERVER["REQUEST_METHOD"] == "PUT") {
                // Update Choice page's URL path
                $newPageData = $this->getRequest()->getParam('data');

                if ($newPageData == "") {
                    $input = file_get_contents('php://input');
                    $input = utf8_encode($input);
                    $newPageData = json_encode(json_decode($input)->data);
                }
                $newPageData = json_decode($newPageData);

                if ($newPageData == NULL || !isset($newPageData->page_id)) {
                    throw new Exception();
                }

                $pageId = $newPageData->page_id;

                $this->updateChoiceUrl($newPageData, $pageId);
                $responseObj["status"] = "ok";
            } else if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
                $newPageData = $this->getRequest()->getParam('data');

                if ($newPageData == "") {
                    $input = file_get_contents('php://input');
                    $input = utf8_encode($input);
                    $newPageData = json_encode(json_decode($input)->data);
                }
                $newPageData = json_decode($newPageData);

                if ($newPageData == NULL || !isset($newPageData->page_id)) {
                    throw new Exception();
                }

                $pageId = $newPageData->page_id;

                $pagesCollection = Mage::getModel('cms/page')
                    ->getCollection()
                    ->addFieldToFilter('page_id', array("eq"=> $pageId));
                $pageData = Mage::getModel('cms/page')->load($pagesCollection->getFirstItem()->getId());
                $pageData->delete();
//                Mage::app()->getStore()->resetConfig();
                $responseObj["status"] = "ok";
            } else {
//                Invalid, return error
                $responseObj['status'] = 'error';
                $responseObj['message'] = 'Invalid request';
            }

            $responseObj['version'] = self::API_VERSION;
            $this->getResponse()
                ->setBody(json_encode($responseObj))
                ->setHttpResponseCode(200)
                ->setHeader('Content-type', 'application/json', true);
        } catch(Exception $e) {
            $this->getResponse()
                ->setBody(json_encode(array('emsg'=> $e->getMessage(), 'status' => 'error', 'message' => 'Internal server error', 'version' => self::API_VERSION)))
                ->setHttpResponseCode(500)
                ->setHeader('Content-type', 'application/json', true);
        }

        return this;
    }

    private function updateChoiceUrl($newPageData, $pageId, $dataToUpdate=false){
//      Doesn't allow update without "identifier" field :/
        if(!$dataToUpdate) {
            // Update case
            $dataToUpdate = array();

            if (isset($newPageData->url_path)) {
                $urlPath = $newPageData->url_path;

                // Ensure no other existing page contains the same URL path / "identifier"
                $collection = Mage::getModel('cms/page')
                    ->getCollection()
                    ->addFieldToFilter('identifier', $urlPath)
                    ->addFieldToFilter('page_id', array("neq"=> $pageId));
                $page = Mage::getModel('cms/page')->load($collection->getFirstItem()->getId());

                // URL path already being used?
                if ($page->getId())
                    throw new Exception();
                else
                    $dataToUpdate['identifier'] = $urlPath;
            }

            if (isset($newPageData->content)) {
                $dataToUpdate['content'] = $newPageData->content;
            }

            if (isset($newPageData->title)) {
                $dataToUpdate['title'] = $newPageData->title;
            }
        }

        $dataToUpdate['page_id'] = $pageId;

        // Ensuring proper scope per update
        $dataToUpdate['stores'] = array(0);

        if(!isset($dataToUpdate['identifier'])){
            // Get existing "identifier"
            $pagesCollection = Mage::getModel('cms/page')
                ->getCollection()
                ->addFieldToFilter('page_id', array("eq"=> $pageId));
            $pageData = Mage::getModel('cms/page')->load($pagesCollection->getFirstItem()->getId());

            $dataToUpdate['identifier'] = $pageData->getIdentifier();
        }

        return Mage::getModel('cms/page')->setData($dataToUpdate)->save();
    }

}
