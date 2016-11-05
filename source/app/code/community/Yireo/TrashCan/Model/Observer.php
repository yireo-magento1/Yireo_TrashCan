<?php
/**
 * Yireo TrashCan for Magento
 *
 * @package     Yireo_TrashCan
 * @author      Yireo (https://www.yireo.com/)
 * @copyright   Copyright 2016 Yireo (https://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 * @link        https://www.yireo.com/
 */

/**
 * Class Yireo_TrashCan_Model_Observer
 */
class Yireo_TrashCan_Model_Observer extends Mage_Core_Model_Abstract
{
    /**
     * @var Yireo_TrashCan_Model_Object
     */
    protected $trashObject;

    /**
     * Method that is thrown with the event "model_delete_before"
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Yireo_TrashCan_Model_Observer
     */
    public function modelDeleteBefore($observer)
    {
        if (Mage::helper('trashcan')->redirectOnWrongPhpVersion()) {
            exit;
        }

        // Fetch the event-object
        $currentObject = $observer->getEvent()->getObject();
        $currentResourceClass = $this->getResourceClassFromObject($currentObject);

        // Check if this object is supported
        if ($this->isResourceClassAllowed($currentResourceClass) === false) {
            return $this;
        }

        $this->createTrashcanObject($currentObject, $currentResourceClass);

        return $this;
    }

    /**
     * Method that is thrown with the event "model_delete_after"
     *
     * @param Varien_Event_Observer $observer
     *
     * @return Yireo_TrashCan_Model_Observer
     */
    public function modelDeleteAfter($observer)
    {
        if (empty($this->trashObject)) {
            return $this;
        }

        //$this->trashObject->getResourceModel()->save($this->trashObject);
        $this->trashObject->save();

        return $this;
    }

    /**
     * @param $object
     * @param $resourceClass
     *
     * @return bool
     */
    protected function createTrashcanObject($object, $resourceClass)
    {
        // Create a new object
        $trashObject = Mage::getModel('trashcan/object');

        if ($trashObject->loadFromObject($object, $resourceClass) === false) {
            Mage::getSingleton('adminhtml/session')->addError('Unable to create trashcan-object');
            return false;
        }

        $this->trashObject = $trashObject;
        return true;
    }

    /**
     * @param $resourceClass
     *
     * @return bool
     */
    protected function isResourceClassAllowed($resourceClass)
    {
        // Check if this object is supported
        $supportedModels = Mage::helper('trashcan')->getSupportedModels();
        if (array_key_exists($resourceClass, $supportedModels) === false) {
            return false;
        }

        // Check the configuration whether trash-can is enabled
        $config = (bool) Mage::helper('trashcan')->setting('enable_' . $resourceClass);
        if ($config !== true) {
            return false;
        }

        return true;
    }

    /**
     * @param Mage_Core_Model_Abstract $object
     *
     * @return string
     */
    protected function getResourceClassFromObject($object)
    {
        return str_replace('/', '_', $object->getResourceName());
    }
}
