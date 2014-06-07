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

/**
 * Trashed objects resource collection
 */
class Yireo_TrashCan_Model_Mysql4_Object_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{

    /**
     * Constructor
     */
    protected function _construct()
    {
        $this->_init('trashcan/object');
    }
}