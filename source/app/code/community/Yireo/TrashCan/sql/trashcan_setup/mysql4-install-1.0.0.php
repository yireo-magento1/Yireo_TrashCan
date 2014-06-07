<?php
/**
 * Yireo TrashCan for Magento
 *
 * @package     Yireo_TrashCan
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (C) 2014 Yireo (http://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 * @link        http://www.yireo.com/
 */

$installer = $this;
$installer->startSetup();
$installer->run("
CREATE TABLE IF NOT EXISTS `{$installer->getTable('trashcan_object')}` (
  `object_id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(255) NOT NULL,
  `resource_class` varchar(255) NOT NULL,
  `resource_data` longblob NOT NULL,
  `trashed_timestamp` datetime NOT NULL,
  `trashed_by` int(11) NOT NULL,
  PRIMARY KEY (`object_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();
