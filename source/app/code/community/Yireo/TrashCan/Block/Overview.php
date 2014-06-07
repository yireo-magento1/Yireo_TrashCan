<?php
/**
 * Yireo TrashCan for Magento
 *
 * @package     Yireo_TrashCan
 * @author      Yireo (http://www.yireo.com/)
 * @copyright   Copyright (C) 2014 Yireo (http://www.yireo.com/)
 * @license     Open Source License (OSL v3)
 * @link        http://www.yireo.com/
 */

class Yireo_TrashCan_Block_Overview extends Mage_Adminhtml_Block_Widget_Container
{
    /*
     * Constructor method
     */
    public function _construct()
    {
        $this->setTemplate('trashcan/overview.phtml');
        parent::_construct();
    }

    protected function _prepareLayout()
    {
        $this->setChild('grid', $this->getLayout()
            ->createBlock('trashcan/overview_grid', 'trashcan.grid')
            ->setSaveParametersInSession(true)
        );
        return parent::_prepareLayout();
    }

    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }

    /*
     * Helper to return the header of this page
     */
    public function getHeader()
    {
        return $this->__('Trashed Items');
    }

    /**
     * Return the version
     *
     * @access public
     * @param null
     * @return string
     */
    public function getVersion()
    {
        $config = Mage::app()->getConfig()->getModuleConfig('Yireo_TrashCan');
        return (string)$config->version;
    }
}
