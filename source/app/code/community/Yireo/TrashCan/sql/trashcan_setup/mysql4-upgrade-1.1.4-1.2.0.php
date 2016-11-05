<?php
/**
 * Yireo TrashCan for Magento
 *
 * @package     Yireo_TrashCan
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 * @link        https://www.yireo.com/
 */

/** @var $installer Mage_Catalog_Model_Resource_Eav_Mysql4_Setup */
$installer = $this;
$installer->startSetup();

$installer->run("
ALTER TABLE {$this->getTable('trashcan_object')} ADD `search_data` TEXT NOT NULL DEFAULT '' AFTER `label`;
");

$installer->endSetup();
