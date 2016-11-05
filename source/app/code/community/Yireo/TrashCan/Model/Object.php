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
 * Class Yireo_TrashCan_Model_Object
 */
class Yireo_TrashCan_Model_Object extends Mage_Core_Model_Abstract
{
    /**
     * @var Mage_Admin_Model_User
     */
    protected $user;

    /**
     * @var Yireo_TrashCan_Helper_Data
     */
    protected $helper;

    /**
     * @var Mage_Adminhtml_Model_Session
     */
    protected $adminSession;

    /**
     * @var Mage_Core_Model_Abstract
     */
    protected $targetObject;

    /**
     * Constructor
     *
     * @return mixed
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_init('trashcan/object');

        $this->user = Mage::getModel('admin/session')->getUser();
        $this->helper = Mage::helper('trashcan');
        $this->adminSession = Mage::getModel('adminhtml/session');
    }

    /**
     * Method to fill this object with data from another to-be-removed object
     *
     * @param Mage_Core_Model_Abstract $object
     * @param string $resourceClass
     *
     * @return boolean
     */
    public function loadFromObject($object, $resourceClass)
    {
        $this->targetObject = $object;

        // Add a label
        $label = $this->getLabelFromObject();

        // Add the resource-model
        if (empty($resourceClass)) {
            $resourceClass = $this->getResourceClassFromObject();
        }

        // Run the data through the parser model
        $searchData = $this->getSearchData($resourceClass);

        // Add the resource-data
        $this->targetObject->setTrashcanResourceClass($resourceClass);
        $targetObject = clone( $this->targetObject);
        $this->setResourceObject($targetObject);

        // Set extra meta-data
        $this->setLabel($label);
        $this->setSearchData(implode('|', $searchData));
        $this->setResourceClass($resourceClass);
        $this->setTrashedBy($this->user->getId());
        $this->setTrashedTimestamp(date('y-m-d H:i', time()));

        return true;
    }

    /**
     * Helper-method to add meta-data to the object
     *
     * @param Mage_Core_Model_Abstract $object
     * @param string $name
     * @param mixed $data
     *
     * @return object
     */
    public function setTrashcanData($object, $name, $data)
    {
        $trashcanData = $object->getData('trashcan_data');

        if (!is_array($trashcanData)) {
            $trashcanData = array();
        }

        $trashcanData[$name] = $data;
        $object->setData('trashcan_data', $trashcanData);
        return $object;
    }

    /**
     * Helper-method to retrieve meta-data from the object
     *
     * @param Mage_Core_Model_Abstract $object
     * @param string $name
     *
     * @return mixed
     */
    public function getTrashcanData($object, $name = null)
    {
        $trashcanData = $object->getData('trashcan_data');
        if (isset($trashcanData[$name])) {
            return $trashcanData[$name];
        }

        if (empty($name)) {
            return $trashcanData;
        }

        return null;
    }

    /**
     * Save-method
     *
     * @return Mage_Core_Model_Abstract
     */
    public function save()
    {
        $this->setResourceData(serialize($this->getResourceObject()));

        return parent::save();
    }

    /**
     * Restore-method
     *
     * @return boolean
     */
    public function restore()
    {
        // Do not continue, if no ID is set
        if (!$this->getObjectId() > 0) {
            $this->adminSession->addError($this->helper->__('Can not locate object'));
            return false;
        }

        // Check if this object is supported
        if ($this->isResourceClassAllowed($this->getResourceClass()) === false) {
            Mage::getModel('adminhtml/session')->addError($this->helper->__('Restoration is not possible, because this resource is not supported'));
            return false;
        }

        // Construct the proper resource class
        $resourceClass = str_replace('_', '/', $this->getResourceClass());

        /** @var Mage_Catalog_Model_Resource_Abstract $resourceModel */
        $resourceModel = Mage::getResourceSingleton($resourceClass);

        // Unserialize the data
        try {
            $this->targetObject = unserialize($this->getResourceData());
        } catch(Exception $e) {
            $this->adminSession->addError($this->helper->__('Restore failed'). ': ' . $e->getMessage());
            return false;
        }

        $this->targetObject->setId(null);

        // Run the data through the parser model
        $parserModel = $this->helper->getParserModel($this->getResourceClass());
        if (!empty($parserModel)) {
            $parserModel->setData($this->targetObject->getData());
            $parserModel->restore();
            $this->targetObject->setData($parserModel->getData());
        }

        Mage::dispatchEvent('trashcan_object_restore_before', array('object' => $this->targetObject));

        $this->runTransaction($resourceModel);

        // Try to find the last inserted ID
        if (!$this->targetObject->getId() > 0) {
            if ($resourceModel->lastInsertId() > 0) {
                $this->targetObject->setId($resourceModel->lastInsertId());
            }
        }

        // Post-restore procedures
        if (!empty($parserModel)) {
            $parserModel->setData($this->targetObject->getData());
            $parserModel->postRestore();
        }

        Mage::dispatchEvent('trashcan_object_restore_after', array('object' => $this->targetObject, 'model' => $parserModel));

        // Remove the trashed item
        $this->delete();

        return true;
    }

    /**
     * @return Mage_Core_Model_Mysql4_Abstract
     */
    public function getResourceModel()
    {
        return $this->_getResource();
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
     * @param Mage_Catalog_Model_Resource_Abstract $resourceModel
     *
     * @return bool
     */
    protected function runTransaction($resourceModel)
    {
        // Save it with the resource
        $resourceModel->beginTransaction();

        try {
            $resourceModel->save($this->targetObject);

        } catch (Exception $e) {
            $resourceModel->rollBack();
            $this->adminSession->addError($this->helper->__('Save failed'). ': ' . $e->getMessage());
            return false;
        }

        // Commit the resource
        try {
            $resourceModel->commit();
        } catch (Exception $e) {
            $resourceModel->rollBack();
            $this->adminSession->addError($this->helper->__('Commit failed'). ': ' . $e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    protected function getLabelFromObject()
    {
        $label = $this->targetObject->getData('name');
        if (empty($label)) {
            $label = $this->targetObject->getData('title');
        }

        if (empty($label)) {
            $label = $this->targetObject->getData('label');
        }

        return $label;
    }

    /**
     * @param $resourceClass
     *
     * @return array
     */
    protected function getSearchData($resourceClass)
    {
        $parserModel = $this->helper->getParserModel($resourceClass);

        if (empty($parserModel)) {
            return array();
        }

        $parserModel->setData($this->targetObject->getData());
        $parserModel->prepare();
        $this->targetObject->setData($parserModel->getData());

        return $parserModel->getSearchData();
    }

    /**
     * @return mixed
     */
    protected function getResourceClassFromObject()
    {
        $resourceClass = preg_replace('/\//', '_', $this->targetObject->getResourceName(), 1);

        if (empty($resourceClass)) {
            $resourceClass = $this->targetObject->getResourceName();
        }

        return $resourceClass;
    }
}
