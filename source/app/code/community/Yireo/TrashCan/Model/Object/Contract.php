<?php
/**
 * Yireo TrashCan for Magento
 *
 * @package     Yireo_TrashCan
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 * @link        http://www.yireo.com/
 */

/**
 * Trashed object contract
 */
interface Yireo_TrashCan_Model_Object_Contract
{
    /**
     * Method to run when this object is being stored into the trashcan
     */
    public function prepare();

    /**
     * Method to run when this object is being restored from the trashcan
     */
    public function restore();

    /**
     * Method to run after this object has been restored
     */
    public function postRestore();
}