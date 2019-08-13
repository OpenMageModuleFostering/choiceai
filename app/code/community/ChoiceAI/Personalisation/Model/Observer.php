<?php

class ChoiceAI_Personalisation_Model_Observer
{

    const CONFIG_ACTIVE = 'choiceai_personalisation/settings/active';
    const CONFIG_API_KEY = 'choiceai_personalisation/settings/api_key';

    public function onSaveSettings($observer)
    {

        $enabled = Mage::getStoreConfig(self::CONFIG_ACTIVE);
        $apiKey = Mage::getStoreConfig(self::CONFIG_API_KEY);
        $errorMessage = "";

        try {
            $roles = Mage::getModel('api/roles')->getCollection()->addFieldToFilter('role_name', 'ChoiceAI');

            if ($roles && !empty($roles)) {
                $apiRole = $roles->getFirstItem();
            } else {
                $apiRole = Mage::getModel('api/roles')
                    ->setName('ChoiceAI')
                    ->setPid("ChoiceAI")
                    ->setRoleType('G')
                    ->save();
            }

            Mage::getModel('api/rules')
                ->setRoleId($apiRole->getId())
                ->setResources(array('all'))
                ->saveRel();

            $users = Mage::getModel('api/user')->getCollection()->addFieldToFilter(
                'email',
                'magento@choice.ai'
            );
            if ($users && !empty($users)) {
                $apiUser = $users->getFirstItem();
            } else {
                $apiUser = Mage::getModel('api/user');
                $apiUser->setData(
                    array(
                        'username' => 'choiceai',
                        'firstname' => 'ChoiceAI',
                        'lastname' => 'Personalisation',
                        'email' => 'magento@choice.ai',
                        'api_key' => $apiKey,
                        'api_key_confirmation' => $apiKey,
                        'is_active' => 1,
                        'user_roles' => '',
                        'assigned_user_role' => '',
                        'role_name' => '',
                        'roles' => array($apiRole->getId())
                    )
                );

                $apiUser->save()->load($apiUser->getId());
            }

            $apiUser->setRoleIds(array($apiRole->getId()))
                ->setRoleUserId($apiUser->getUserId())
                ->saveRelations();
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }

        file_get_contents(
            "https://app.choice.ai/stats/magentoinstall?enabled=" . $enabled . "&api_key="
            . $apiKey . "&error=" . $errorMessage
        );

    }

    public function logCartAdd($observer)
    {

        if (!$observer->getQuoteItem()->getProduct()->getId()) {
            return;
        }

        $product = $observer->getProduct();
        $id = $observer->getQuoteItem()->getProduct()->getId();
        $bundle = array();

        if ($product->getTypeId() == 'bundle') {
            $id = $product->getId();
            $optionCollection = $product->getTypeInstance()->getOptionsCollection();
            $selectionCollection = $product->getTypeInstance()->getSelectionsCollection(
                $product->getTypeInstance()
                ->getOptionsIds()
            );
            $options = $optionCollection->appendSelections($selectionCollection);
            foreach ($options as $option) {
                $_selections = $option->getSelections();
                foreach ($_selections as $selection) {
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
        if ($parentIds != null && !empty($parentIds)) {
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