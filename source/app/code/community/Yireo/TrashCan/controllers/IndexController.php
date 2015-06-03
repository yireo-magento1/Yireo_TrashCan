<?php
/**
 * Yireo TrashCan for Magento
 *
 * @package     Yireo_TrashCan
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright 2015 Yireo (http://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 */

/**
 * TrashCan admin controller
 *
 * @category   TrashCan
 * @package     Yireo_TrashCan
 */
class Yireo_TrashCan_IndexController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Common method
     *
     * @access protected
     *
     * @param null
     *
     * @return Yireo_TrashCan_IndexController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/trashcan')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('System'), Mage::helper('adminhtml')->__('System'))
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Trashed Items'), Mage::helper('adminhtml')->__('Trashed Items'));
        return $this;
    }

    /**
     * Overview page
     *
     * @access public
     *
     * @param null
     *
     * @return null
     */
    public function indexAction()
    {
        Mage::helper('trashcan')->redirectOnWrongPhpVersion();

        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('trashcan/overview'))
            ->renderLayout();
    }

    /**
     * Alias for overview
     *
     * @access public
     *
     * @param null
     *
     * @return null
     */
    public function gridAction()
    {
        $this->indexAction();
    }

    /**
     * Delete action
     *
     * @access public
     *
     * @param null
     *
     * @return null
     */
    public function deleteAction()
    {
        Mage::helper('trashcan')->redirectOnWrongPhpVersion();

        // Load the objects
        $objectIds = $this->getRequest()->getParam('object_id');
        if (!is_array($objectIds)) {
            $objectIds = array($objectIds);
        }

        // Delete the objects
        if (!empty($objectIds)) {
            foreach ($objectIds as $objectId) {
                $object = Mage::getModel('trashcan/object')->load($objectId);
                if (Mage::helper('trashcan')->allowModify($object)) {
                    $object->delete();
                }
            }
        }

        // Set a message
        Mage::getModel('adminhtml/session')->addNotice($this->__('Deleted %s objects succesfully', count($objectIds)));

        // Redirect
        $this->_redirect('trashcan/index/index');
    }

    /**
     * Restore action
     *
     * @access public
     *
     * @param null
     *
     * @return null
     */
    public function restoreAction()
    {
        Mage::helper('trashcan')->redirectOnWrongPhpVersion();

        // Counter
        $success = 0;
        $fail = 0;

        // Load the objects
        $objectIds = $this->getRequest()->getParam('object_id');
        if (!is_array($objectIds)) {
            $objectIds = array($objectIds);
        }

        // Redirect
        if (empty($objectIds)) {
            Mage::getModel('adminhtml/session')->addNotice($this->__('No objects selected'));
            $this->_redirect('trashcan/index/index');
        }

        // Restore the objects
        foreach ($objectIds as $objectId) {
            $object = Mage::getModel('trashcan/object')->load($objectId);
            if (Mage::helper('trashcan')->allowModify($object)) {
                $result = $object->restore();
                if ($result == true) {
                    $success++;
                } else {
                    $fail++;
                }
            } else {
                $fail++;
            }
        }

        // Set success-message
        if ($success > 0) {
            Mage::getModel('adminhtml/session')->addSuccess($this->__('Restored %s objects succesfully', $success));
        }

        // Set fail-message
        if ($fail > 0) {
            Mage::getModel('adminhtml/session')->addError($this->__('Failed to restore %s objects', $fail));
        }

        // Redirect
        $this->_redirect('trashcan/index/index');
    }
}
