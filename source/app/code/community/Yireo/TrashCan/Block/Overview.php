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
 * Class Yireo_TrashCan_Block_Overview
 */
class Yireo_TrashCan_Block_Overview extends Mage_Adminhtml_Block_Widget_Container
{
    /**
     * Constructor method
     */
    public function _construct()
    {
        $this->setTemplate('trashcan/overview.phtml');
        parent::_construct();
    }

    /**
     * Prepare the layout
     * 
     * @return Mage_Core_Block_Abstract
     */
    protected function _prepareLayout()
    {
        $this->setChild('grid', $this->getLayout()
            ->createBlock('trashcan/overview_grid', 'trashcan.grid')
            ->setSaveParametersInSession(true)
        );
        return parent::_prepareLayout();
    }

    /**
     * Return the grid HTML
     * 
     * @return string
     */
    public function getGridHtml()
    {
        return $this->getChildHtml('grid');
    }

    /**
     * Helper to return the header of this page
     * 
     * @return string
     */
    public function getHeader()
    {
        return $this->__('Trashed Items');
    }

    /**
     * Return the version
     *
     * @return string
     */
    public function getVersion()
    {
        $config = Mage::app()->getConfig()->getModuleConfig('Yireo_TrashCan');
        return (string)$config->version;
    }
}
