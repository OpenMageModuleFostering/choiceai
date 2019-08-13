<?php
$installer = $this;
/* @var $installer Mage_Searchcore_Model_Resource_Setup */

$installer->startSetup();
$fieldTable = $installer->getTable('choiceai_field_conf');
$configTable = $installer->getTable('choiceai_recommendation_conf');
try {
    $installer->run("ALTER TABLE `{$configTable}` CHANGE `key` `choiceai_key` VARCHAR(50)");
} catch (Exception $e) {
    //ignore the exceptions
}

$websiteCollection = Mage::getModel('core/website')->getCollection()->load();
foreach($websiteCollection as $website) {
    $websiteId = $website->getWebsiteId();
    if (is_null($websiteId)) {
        continue;
    }
    $fieldTable = Mage::getResourceModel('choiceai_searchcore/field')->getTableName();
    $insertQuery = "
    INSERT INTO `{$fieldTable}` (`website_id`, `field_name`, `datatype`, `autosuggest`, `featured_field`, `multivalued`, `displayed`)
VALUES
    ({$websiteId}, '" . ChoiceAI_Searchcore_Model_Resource_Field::QTY_MANAGE_ASSOCIATED . "',
                   '" .ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_NUMBER . "', 0, NULL, 1, 0),
    ({$websiteId}, '" .ChoiceAI_Searchcore_Model_Resource_Field::QTY_ASSOCIATED . "',
                   '" .ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_NUMBER . "', 0, NULL, 1, 0),
    ({$websiteId}, '" .ChoiceAI_Searchcore_Model_Resource_Field::QTY_CONFIG_USE_MANAGE_STOCK_ASSOCIATED. "',
                   '" .ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_NUMBER . "', 0, NULL, 1, 0),
    ({$websiteId}, 'statusAssociated',
                   '" .ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_NUMBER . "', 0, NULL, 1, 0),
    ({$websiteId}, '" .ChoiceAI_Searchcore_Model_Resource_Field::AVAILABILITY_ASSOCIATED . "',
                   '" .ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_BOOL . "', 0, NULL, 1, 0),
    ({$websiteId}, '" .ChoiceAI_Searchcore_Model_Resource_Field::QTY_MANAGE . "',
                   '" .ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_NUMBER . "', 0, NULL, 0, 0),
    ({$websiteId}, '" .ChoiceAI_Searchcore_Model_Resource_Field::QTY_CONFIG_USE_MANAGE_STOCK . "',
                   '" .ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_NUMBER . "', 0, NULL, 0, 0),
    ({$websiteId}, '" .ChoiceAI_Searchcore_Model_Resource_Field::QTY . "',
                   '" .ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_NUMBER . "', 0, NULL, 0, 0),
    ({$websiteId}, 'type_id',
                   '" .ChoiceAI_Searchcore_Helper_Constants::CHOICEAI_DATATYPE_LONGTEXT . "', 0, NULL, 0, 1)
    ON DUPLICATE KEY UPDATE `field_name`=`field_name`;";
    $installer->run($insertQuery);
}

?>
