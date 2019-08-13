<?php

class ChoiceAI_Personalisation_Model_Observer {

  const CONFIG_ACTIVE = 'choiceai_personalisation/settings/active';
  const CONFIG_API_KEY = 'choiceai_personalisation/settings/api_key';

  public function onSaveSettings($observer) {

    $enabled = Mage::getStoreConfig(self::CONFIG_ACTIVE);
    $api_key = Mage::getStoreConfig(self::CONFIG_API_KEY);
    $store_url = Mage::getBaseUrl();
    $magento_version = Mage::getVersion();

    $error_message = "";

    try {

      $roles = Mage::getModel('api/roles')->getCollection()->addFieldToFilter('role_name', 'ChoiceAI');

      if ($roles && sizeof($roles) > 0) {

        $api_role = $roles->getFirstItem();

      } else {

        $api_role = Mage::getModel('api/roles')
          ->setName('ChoiceAI')
          ->setPid("ChoiceAI")
          ->setRoleType('G')
          ->save();
      }

      Mage::getModel('api/rules')
        ->setRoleId($api_role->getId())
        ->setResources(array('all'))
        ->saveRel();

      $users = Mage::getModel('api/user')->getCollection()->addFieldToFilter('email', 'magento@choice.ai');
      if ($users && sizeof($users) > 0) {

        $api_user = $users->getFirstItem();

      } else {

        $api_user = Mage::getModel('api/user');
        $api_user->setData(array(
          'username' => 'choiceai',
          'firstname' => 'ChoiceAI',
          'lastname' => 'Personalisation',
          'email' => 'magento@choice.ai',
          'api_key' => $api_key,
          'api_key_confirmation' => $api_key,
          'is_active' => 1,
          'user_roles' => '',
          'assigned_user_role' => '',
          'role_name' => '',
          'roles' => array($api_role->getId())
        ));

        $api_user->save()->load($api_user->getId());

      }

      $api_user->setRoleIds(array($api_role->getId()))
        ->setRoleUserId($api_user->getUserId())
        ->saveRelations();

    } catch (Exception $e) {

      $error_message = $e->getMessage();

    }

    file_get_contents("https://app.choice.ai/stats/magentoinstall?enabled=".$enabled."&api_key=".$api_key."&store_url=".$store_url."&magentoversion=".$magento_version."&error=".$error_message);

  }

  public function logCartAdd($observer) {

    if (!$observer->getQuoteItem()->getProduct()->getId()) {
      return;
    }

    $product = $observer->getProduct();
    $id = $observer->getQuoteItem()->getProduct()->getId();
    $bundle = array();

    if($product->getTypeId() == 'bundle') {

      $id = $product->getId();
      $optionCollection = $product->getTypeInstance()->getOptionsCollection();
      $selectionCollection = $product->getTypeInstance()->getSelectionsCollection($product->getTypeInstance()->getOptionsIds());
      $options = $optionCollection->appendSelections($selectionCollection);
      foreach( $options as $option )
      {
        $_selections = $option->getSelections();
        foreach( $_selections as $selection )
        {
          $bundleItem = array();
          $bundleItem['pid'] = $selection->getId();
          $bundleItem['sku'] = $selection->getSku();
          $bundleItem['price'] = $selection->getPrice();
          $bundle[] = $bundleItem;
        }
      }

    }

    $parentId = '';
    $parentIds = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($id);
    if($parentIds != null && count($parentIds) > 0) {
      $parentId = $parentIds[0];
    }
    Mage::getModel('core/session')->setProductToShoppingCart(
      array(
        'id' => $id,
        'sku' => $product->getSku(),
        'parentId' => $parentId,
        'qty' => $product->getQty(),
        'bundle' => $bundle
      )
    );

  }
}