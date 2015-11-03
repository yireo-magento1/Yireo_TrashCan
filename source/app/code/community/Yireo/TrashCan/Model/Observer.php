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

class Yireo_TrashCan_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * Method that is thrown with the event "model_delete_before"
     *
     * @param Varien_Event_Observer $observer
     * @return Yireo_TrashCan_Model_Observer
     */
    public function modelDeleteBefore($observer)
    {
        if (Mage::helper('trashcan')->redirectOnWrongPhpVersion()) {
            exit;
        }

        // Fetch the event-object
        $currentObject = $observer->getEvent()->getObject();
        $currentResourceClass = str_replace('/','_', $currentObject->getResourceName());

        // Check if this object is supported
        $supportedModels = Mage::helper('trashcan')->getSupportedModels();
        if(array_key_exists($currentResourceClass, $supportedModels) == false) {
            return $this;
        }

        // Check the configuration whether trash-can is enabled
        $config = Mage::helper('trashcan')->setting('enable_'.$currentResourceClass);
        if($config != 1) {
            return $this;
        }
        
        // Create a new object
        $trashObject = Mage::getModel('trashcan/object');

        if($trashObject->loadFromObject($currentObject, $currentResourceClass)) {
            $trashObject->save();

        } else {
			Mage::getSingleton('adminhtml/session')->addError('Unable to create trashcan-object');
        }

        // Return nothing
        return $this;
    }
}
