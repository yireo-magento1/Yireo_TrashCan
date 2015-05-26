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
 * Trashed objects model
 */
trait Yireo_TrashCan_Model_Object_Trait
{
    /**
     * Helper-method to add meta-data to the object
     *
     * @param string $name
     * @param mixed $data
     */
    public function setTrashcanData($name, $data)
    {
        $trashcanData = $this->getData('trashcan_data');

        if (!is_array($trashcanData)) {
            $trashcanData = array();
        }

        $trashcanData[$name] = $data;

        $this->setData('trashcan_data', $trashcanData);
    }

    /**
     * Helper-method to retrieve meta-data from the object
     *
     * @param string $name
     * @return mixed
     */
    public function getTrashcanData($name = null)
    {
        $trashcanData = $this->getData('trashcan_data');

        if (isset($trashcanData[$name])) {
            return $trashcanData[$name];
        }

        return null;
    }
}