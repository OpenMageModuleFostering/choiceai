<?php


$installer = $this;
/* @var $installer Mage_Searchcore_Model_Resource_Setup */

$installer->startSetup();

$configTable = $installer->getTable('choiceai_recommendation_conf');
$fieldTable = $installer->getTable('choiceai_field_conf');
$productSyncTable = $installer->getTable('choiceai_product_sync');

$installer->run("

CREATE TABLE IF NOT EXISTS  `{$fieldTable}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `website_id` tinyint(5) unsigned NOT NULL,
  `field_name` varchar(100) NOT NULL DEFAULT '',
  `datatype` varchar(20) NOT NULL DEFAULT '',
  `autosuggest` tinyint(1) NOT NULL DEFAULT '0',
  `featured_field` varchar(100) DEFAULT NULL,
  `multivalued` tinyint(1) NOT NULL DEFAULT '0',
  `displayed` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `website_id` (`website_id`,`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS  `{$configTable}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `website_id` smallint(5) unsigned NOT NULL,
  `key` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `{$productSyncTable}` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `website_id` smallint(5) unsigned NOT NULL,
  `synced` tinyint(1) NOT NULL DEFAULT '0',
  `updated_time` datetime DEFAULT NULL,
  `sync_time` datetime DEFAULT NULL,
  `operation` enum('ADD','DELETE') NOT NULL DEFAULT 'ADD',
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`,`website_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
");

$websiteCollection = Mage::getModel('core/website')->getCollection()->load();
foreach($websiteCollection as $website) {
    $websiteId = $website->getWebsiteId();
    if(is_null($websiteId)) {
        continue;
    }
    $fieldTable = Mage::getResourceModel('choiceai_searchcore/field')->getTableName();
    $insertQuery = "
INSERT INTO `{$fieldTable}` (`website_id`, `field_name`, `datatype`, `autosuggest`, `featured_field`, `multivalued`, `displayed`)
VALUES
	({$websiteId}, 'name', 'text', 1, 'title', 0, 1),
	({$websiteId}, 'final_price', 'decimal', 0, 'price', 0, 1),
	({$websiteId}, 'price', 'decimal', 0, NULL, 0, 1),".
	(Mage::helper('choiceai_searchcore/feedhelper')->isAttributePresent('brand')?"({$websiteId}, 'brand', 'text', 0, 'brand', 0, 1),":"").
	(Mage::helper('choiceai_searchcore/feedhelper')->isAttributePresent('color')?"({$websiteId}, 'color', 'text', 0, 'color', 1, 1),":"").
	(Mage::helper('choiceai_searchcore/feedhelper')->isAttributePresent('size')?"({$websiteId}, 'size', 'text', 0, 'size', 1, 1),":"").
	"({$websiteId}, 'image', 'link', 0, 'imageUrl', 1, 1),
	({$websiteId}, 'url_path', 'link', 0, 'productUrl', 0, 1),
	({$websiteId}, 'gender', 'text', 0, 'gender', 0, 1),
	({$websiteId}, 'description', 'longText', 0, 'description', 0, 1),
        ({$websiteId}, 'catlevel1Name', 'text', 0, 'catlevel1Name', 0, 0),
        ({$websiteId}, 'catlevel2Name', 'text', 0, 'catlevel2Name', 0, 0),
        ({$websiteId}, 'catlevel3Name', 'text', 0, 'catlevel3Name', 0, 0),
        ({$websiteId}, 'catlevel4Name', 'text', 0, 'catlevel4Name', 0, 0),
        ({$websiteId}, 'categoryLevel1', 'text', 0, NULL, 1, 0),
        ({$websiteId}, 'categoryLevel2', 'text', 0, NULL, 1, 0),
        ({$websiteId}, 'categoryLevel3', 'text', 0, NULL, 1, 0),
        ({$websiteId}, 'categoryLevel4', 'text', 0, NULL, 1, 0),
	({$websiteId}, 'created_at', 'date', 0, NULL, 0, 1),
	({$websiteId}, 'availability', 'bool', 0, 'availability', 0, 0),
	({$websiteId}, 'status', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'visibility', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'qty', 'number', 0, NULL, 0, 0),
	({$websiteId}, 'categoryIds', 'longText', 0, NULL, 1, 0),
	({$websiteId}, 'category', 'text', 0, 'category', 1, 0),
	({$websiteId}, 'uniqueId', 'longText', 0, NULL, 0, 0),
	({$websiteId}, 'type_id', 'longText', 0, NULL, 0, 0),
	({$websiteId}, 'entity_id', 'longText', 0, NULL, 0, 0)
	ON DUPLICATE KEY UPDATE `field_name`=`field_name`;";
    $installer->run($insertQuery);
}
$installer->endSetup();
?>
