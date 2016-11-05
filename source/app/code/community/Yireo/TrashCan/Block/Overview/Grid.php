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
 * Class Yireo_TrashCan_Block_Overview_Grid
 */
class Yireo_TrashCan_Block_Overview_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * @var Yireo_TrashCan_Model_Object
     */
    protected $objectModel;

    /**
     * @var Yireo_TrashCan_Helper_Data
     */
    protected $helper;

    /**
     * @var Mage_Admin_Model_User
     */
    protected $adminUser;

    /**
     * Yireo_TrashCan_Block_Overview_Grid constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->objectModel = Mage::getModel('trashcan/object');
        $this->helper = Mage::helper('trashcan');
        $this->adminUser = Mage::getModel('admin/user');

        $this->setId('trashcanGrid');
        $this->setDefaultSort('object_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare the collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->objectModel->getCollection();
        $this->setCollection($collection);
        parent::_prepareCollection();

        $collection = $this->getCollection();
        foreach ($collection as $itemKey => $item) {
            /** @var $item Yireo_TrashCan_Model_Object */
            $item->setResourceClass($this->__($item->getData('resource_class')));
            $item->setResourceSize(round(strlen($item->getData('resource_data')) / 1000, 2) . 'Kb');

            $user = $this->adminUser->load($item->getData('trashed_by'));
            $item->setTrashedByUser($user->getName());

            $allow = $this->helper->allowModify($item);
            if ($this->helper->setting('only_show_allowed', 'manager') == 1) {
                if ($allow == false) {
                    $collection->removeItemByKey($itemKey);
                    continue;
                }
            }

            if ($allow == true) {
                $item->setAllowActions($this->__('Yes'));
            } else {
                $item->setAllowActions($this->__('No'));
            }
        }

        $this->setCollection($collection);
        return $this;
    }

    /**
     * Allow the label search also to search through search_data
     *
     * @param $collection
     * @param $column
     *
     * @return $this
     */
    protected function _setLabelFilter($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $this->getCollection()->addFieldToFilter('search_data', array('like' => '%' . $value . '%'));
        return $this;
    }

    /**
     * Prepare the columns
     *
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('object_id', array(
            'header' => $this->helper->__('Trash ID'),
            'width' => '50px',
            'index' => 'object_id',
            'type' => 'number',
            'sortable' => false,
            'filter' => false,
        ));

        $this->addColumn('label', array(
            'header' => $this->helper->__('Label'),
            'index' => 'label',
            'type' => 'text',
            'filter_condition_callback' => array($this, '_setLabelFilter'),
        ));

        $this->addColumn('resource_class', array(
            'header' => $this->helper->__('Resource'),
            'width' => '200px',
            'index' => 'resource_class',
        ));

        $this->addColumn('resource_size', array(
            'header' => $this->helper->__('Data size'),
            'width' => '50px',
            'index' => 'resource_size',
            'type' => 'text',
            'filter' => false,
            'sortable' => false,
        ));

        $this->addColumn('trashed_timestamp', array(
            'header' => $this->helper->__('Removed on'),
            'index' => 'trashed_timestamp',
            'type' => 'datetime',
        ));

        $this->addColumn('trashed_by_user', array(
            'header' => $this->helper->__('Removed by'),
            'index' => 'trashed_by_user',
            'type' => 'text',
            'filter' => false,
        ));

        $this->addColumn('allow_actions', array(
            'header' => $this->helper->__('Allowed'),
            'index' => 'allow_actions',
            'type' => 'text',
            'filter' => false,
            'sortable' => false,
        ));

        $actions = array(
            array(
                'caption' => $this->helper->__('Restore'),
                'url' => array('base' => '*/*/restore'),
                'field' => 'object_id'
            ),
            array(
                'caption' => $this->helper->__('Delete'),
                'url' => array('base' => '*/*/delete'),
                'field' => 'object_id'
            )
        );

        if (Mage::helper('trashcan')->setting('enable_debug')) {
            $actions[] = array(
                'caption' => $this->helper->__('Debug'),
                'url' => array('base' => '*/*/debug'),
                'field' => 'object_id'
            );
        }

        $this->addColumn('actions', array(
            'header' => $this->helper->__('Action'),
            'type' => 'action',
            'getter' => 'getObjectId',
            'actions' => $actions,
            'filter' => false,
            'sortable' => false,
        ));

        return parent::_prepareColumns();
    }

    /**
     * Prepare the mass actions
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('object_id');
        $this->getMassactionBlock()->setFormFieldName('object_id');
        $this->getMassactionBlock()->setUseSelectAll(false);

        $this->getMassactionBlock()->addItem('restore', array(
            'label' => $this->helper->__('Restore'),
            'url' => $this->getUrl('*/*/restore'),
        ));

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => $this->helper->__('Delete'),
            'url' => $this->getUrl('*/*/delete'),
        ));
    }
}
