<?php
/**
 * Created by PhpStorm.
 * User: harkirat
 * Date: 13/7/17
 * Time: 2:47 PM
 */

/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;

$installer->startSetup();
$pluginVersion = "1.1.1";
$magentoVersion = Mage::getVersion();
$server = Mage::app()->getRequest()->getServer();
if (isset($server['REQUEST_SCHEME']) && isset($server['SERVER_NAME'])) {
    $storeUrl = $server['REQUEST_SCHEME'] . "://" . $server['SERVER_NAME'];
} else {
    $storeUrl = Mage::getBaseUrl();
}

$ctx = stream_context_create(
    array('http' =>
        array(
            'timeout' => 5,  //Wait for only 5 secs at max
        )
    )
);

file_get_contents(
    "https://app.choice.ai/stats/magentoinstall?installed=1&base_url=" . Mage::getBaseUrl() .
    "store_url=" . $storeUrl . "&magento_version=" . $magentoVersion . "&plugin_version=" . $pluginVersion,
    false, $ctx
);

$installer->endSetup();