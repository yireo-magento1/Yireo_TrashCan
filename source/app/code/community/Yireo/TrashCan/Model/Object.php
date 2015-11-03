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
class Yireo_TrashCan_Model_Object extends Mage_Core_Model_Abstract
{
    /**
     * Constructor
     *
     * @return mixed
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('trashcan/object');
    }

    /**
     * Method to fill this object with data from another to-be-removed object
     *
     * @param Varien_Object $object
     * @param string $resourceClass
     * @return boolean
     */
    public function loadFromObject($object, $resourceClass)
    {
        // Add a label
        $label = $object->getData('name');
        if(empty($label)) $label = $object->getData('title');
        if(empty($label)) $label = $object->getData('label');

        // Add the resource-model
        $resourceClass = preg_replace('/\//', '_', $object->getResourceName(), 1);
        if(empty($resourceClass)) {
            $resourceClass = $object->getResourceName();
        }

        // Run the data through the parser model
        $parserModel = Mage::helper('trashcan')->getParserModel($resourceClass);
        if (!empty($parserModel)) {
            $parserModel->setData($object->getData());
            $parserModel->prepare();
            $object->setData($parserModel->getData());
        }

        // Add the resource-data
        $object->setTrashcanResourceClass($resourceClass);
        $this->setResourceData(serialize($object));

        // Set extra meta-data
        $currentUser = Mage::getModel('admin/session')->getUser();
        $this->setLabel($label);
        $this->setResourceClass($resourceClass);
        $this->setTrashedBy($currentUser->getId());
        $this->setTrashedTimestamp(date('y-m-d H:i', time()));

        return true;
    }

    /**
     * Helper-method to add meta-data to the object
     *
     * @param object $object
     * @param string $name
     * @param mixed $data
     * @return object
     */
    public function setTrashcanData($object, $name, $data)
    {
        $trashcanData = $object->getData('trashcan_data');
        if(!is_array($trashcanData)) $trashcanData = array();
        $trashcanData[$name] = $data;
        $object->setData('trashcan_data', $trashcanData);
        return $object;
    }

    /**
     * Helper-method to retrieve meta-data from the object
     *
     * @param mixed $object
     * @param string $name
     * @return mixed 
     */
    public function getTrashcanData($object, $name = null)
    {
        $trashcanData = $object->getData('trashcan_data');
        if(isset($trashcanData[$name])) {
            return $trashcanData[$name];
        } elseif(empty($name)) {
            return $trashcanData;
        } 
        return null; 
    }

    /**
     * Restore-method
     *
     * @return boolean
     */
    public function restore()
    {
        // Do not continue, if no ID is set
        if(!$this->getObjectId() > 0) {
            Mage::getModel('adminhtml/session')->addError(Mage::helper('trashcan')->__('Can not locate object'));
            return false;
        }

        // Check if this object is supported
        $supportedModels = Mage::helper('trashcan')->getSupportedModels();
        if(array_key_exists($this->getResourceClass(), $supportedModels) == false) {
            Mage::getModel('adminhtml/session')->addError(Mage::helper('trashcan')->__('Restoration is not possible, because this resource is not supported'));
            return false;
        }

        // Check the configuration whether trash-can is enabled
        $config = Mage::helper('trashcan')->setting('enable_'.$this->getResourceClass());
        if($config != 1) {
            Mage::getModel('adminhtml/session')->addError(Mage::helper('trashcan')->__('Disabled restoration through settings in the Magento Configuration'));
            return false;
        }
        
        // Construct the proper resource class
        $resourceClass = str_replace('_','/', $this->getResourceClass());
        $resourceModel = Mage::getResourceSingleton($resourceClass);

        // Unserialize the data 
        $object = unserialize($this->getResourceData());
        $object->setId(null);

        // Run the data through the parser model
        $parserModel = Mage::helper('trashcan')->getParserModel($this->getResourceClass());
        if (!empty($parserModel)) {
            $parserModel->setData($object->getData());
            $parserModel->restore();
            $object->setData($parserModel->getData());
        }

        // Save it with the resource
        $resourceModel->beginTransaction();
        try {
            $resourceModel->save($object);

        } catch (Exception $e){
            $resourceModel->rollBack();
            Mage::getModel('adminhtml/session')->addError(Mage::helper('trashcan')->__('Save failed: '.$e->getMessage()));
            return false;
        }

        // Commit the resource
        try {
            $resourceModel->commit();
        } catch (Exception $e){
            $resourceModel->rollBack();
            Mage::getModel('adminhtml/session')->addError(Mage::helper('trashcan')->__('Commit failed: '.$e->getMessage()));
            return false;
        }

        // Try to find the last inserted ID
        if (!$object->getId() > 0) {
            if ($resourceModel->lastInsertId() > 0) {
                $object->setId($resourceModel->lastInsertId());
            }
        }

        // Post-restore procedures
        if (!empty($parserModel)) {
            $parserModel->setData($object->getData());
            $parserModel->postRestore();
        }

        // Remove the trashed item
        $this->delete();

        return true;
    }
}
