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
