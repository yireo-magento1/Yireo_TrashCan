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
     * @param null
     * @return Yireo_TrashCan_IndexController
     */
    protected function _initAction()
    {
        // @todo: Mage::helper('yireo')->addNotification('New version for TrashCan available', 'There is a new version of TrashCan available: ...', 'http://www.yireo.com/');

        $this->loadLayout()
            ->_setActiveMenu('system/trashcan')
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('System'), Mage::helper('adminhtml')->__('System'))
            ->_addBreadcrumb(Mage::helper('adminhtml')->__('Trashed Items'), Mage::helper('adminhtml')->__('Trashed Items'))
        ;
        return $this;
    }

    /**
     * Overview page
     *
     * @access public
     * @param null
     * @return null
     */
    public function indexAction()
    {
        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('trashcan/overview'))
            ->renderLayout();
    }

    /**
     * Alias for overview
     *
     * @access public
     * @param null
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
     * @param null
     * @return null
     */
    public function deleteAction()
    {
        // Load the objects
        $object_ids = $this->getRequest()->getParam('object_id');
        if(!is_array($object_ids)) $object_ids = array($object_ids);

        // Delete the objects
        if(!empty($object_ids)) {
            foreach($object_ids as $object_id) {
                $object = Mage::getModel('trashcan/object')->load($object_id);
                if(Mage::helper('trashcan')->allowModify($object)) {
                    $object->delete();
                }
            }
        }

        // Set a message
        Mage::getModel('adminhtml/session')->addNotice($this->__('Deleted %s objects succesfully', count($object_ids)));

        // Redirect
        $this->_redirect('trashcan/index/index');
    }

    /**
     * Restore action
     *
     * @access public
     * @param null
     * @return null
     */
    public function restoreAction()
    {
        // Counter
        $success = 0;
        $fail = 0;

        // Load the objects
        $object_ids = $this->getRequest()->getParam('object_id');
        if(!is_array($object_ids)) $object_ids = array($object_ids);

        // Redirect
        if(empty($object_ids)) {
            Mage::getModel('adminhtml/session')->addNotice($this->__('No objects selected'));
            $this->_redirect('trashcan/index/index');
        }

        // Restore the objects
        foreach($object_ids as $object_id) {
            $object = Mage::getModel('trashcan/object')->load($object_id);
            if(Mage::helper('trashcan')->allowModify($object)) {
                $result = $object->restore();
                if($result == true) {
                    $success++;
                } else {
                    $fail++;
                }
            } else {
                $fail++;
            }
        }

        // Set success-message
        if($success > 0) {
            Mage::getModel('adminhtml/session')->addSuccess($this->__('Restored %s objects succesfully', $success));
        }

        // Set fail-message
        if($fail > 0) {
            Mage::getModel('adminhtml/session')->addError($this->__('Failed to restore %s objects', $fail));
        }

        // Redirect
        $this->_redirect('trashcan/index/index');
    }
}
