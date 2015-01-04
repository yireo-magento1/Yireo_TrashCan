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

class Yireo_TrashCan_Block_Overview_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('trashcanGrid');
        $this->setDefaultSort('object_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);

    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel('trashcan/object')->getCollection();
        $this->setCollection($collection);
        parent::_prepareCollection();

        $collection = $this->getCollection();
        foreach($collection as $itemKey => $item) {
            $item->setResourceClass($this->__($item->getData('resource_class')));
            $item->setResourceSize(round(strlen($item->getData('resource_data')) / 1000, 2).'Kb');

            $user = Mage::getModel('admin/user')->load($item->getData('trashed_by'));
            $item->setTrashedByUser($user->getName());

            $allow = Mage::helper('trashcan')->allowModify($item);
            if(Mage::helper('trashcan')->setting('only_show_allowed', 'manager') == 1) {
                if($allow == false) {
                    $collection->removeItemByKey($itemKey);
                    continue;
                }
            }

            if($allow == true) {
                $item->setAllowActions($this->__('Yes'));
            } else {
                $item->setAllowActions($this->__('No'));
            }
        }

        $this->setCollection($collection);
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('object_id', array(
            'header'=> Mage::helper('trashcan')->__('Trash ID'),
            'width' => '50px',
            'index' => 'object_id',
            'type' => 'number',
			'sortable' => false,
			'filter' => false,
        ));

        $this->addColumn('label', array(
            'header'=> Mage::helper('trashcan')->__('Label'),
            'index' => 'label',
            'type' => 'text',
        ));

        $this->addColumn('resource_class', array(
            'header'=> Mage::helper('trashcan')->__('Resource'),
            'width' => '200px',
            'index' => 'resource_class',
        ));

        $this->addColumn('resource_size', array(
            'header'=> Mage::helper('trashcan')->__('Data size'),
            'width' => '50px',
            'index' => 'resource_size',
            'type' => 'text',
			'filter'    => false,
			'sortable' => false,
        ));

        $this->addColumn('trashed_timestamp', array(
            'header'=> Mage::helper('trashcan')->__('Removed on'),
            'index' => 'trashed_timestamp',
            'type' => 'datetime',
        ));

        $this->addColumn('trashed_by_user', array(
            'header'=> Mage::helper('trashcan')->__('Removed by'),
            'index' => 'trashed_by_user',
            'type' => 'text',
			'filter' => false,
        ));

        $this->addColumn('allow_actions', array(
            'header'=> Mage::helper('trashcan')->__('Allowed'),
            'index' => 'allow_actions',
            'type' => 'text',
			'filter' => false,
			'sortable' => false,
        ));

        $this->addColumn('actions', array(
            'header'=> Mage::helper('trashcan')->__('Action'),
            'type' => 'action',
            'getter' => 'getObjectId',
            'actions' => array(
                array(
                    'caption' => Mage::helper('trashcan')->__('Restore'),
                    'url' => array('base' => '*/*/restore'),
                    'field' => 'object_id'
                ),
                array(
                    'caption' => Mage::helper('trashcan')->__('Delete'),
                    'url' => array('base' => '*/*/delete'),
                    'field' => 'object_id'
                )
            ),
            'filter'    => false,
            'sortable'  => false,
        ));

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('object_id');
        $this->getMassactionBlock()->setFormFieldName('object_id');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('restore', array(
            'label'=> Mage::helper('trashcan')->__('Restore'),
            'url'  => $this->getUrl('*/*/restore'),
        ));

        $this->getMassactionBlock()->addItem('delete', array(
            'label'=> Mage::helper('trashcan')->__('Delete'),
            'url'  => $this->getUrl('*/*/delete'),
        ));
    }
}
