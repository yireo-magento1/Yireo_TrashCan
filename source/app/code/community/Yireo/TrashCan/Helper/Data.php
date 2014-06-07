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

class Yireo_TrashCan_Helper_Data extends Mage_Core_Helper_Abstract
{
    /*
     * Helper-method to return a specific setting
     *
     * @access public
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function setting($name, $group = 'settings', $default = null)
    {
        $value = Mage::getStoreConfig('trashcan/'.$group.'/'.$name);
        if(empty($value)) $value = $default;
        return $value;
    }

    /*
     * Helper-method to return the supported models
     *
     * @access public
     * @param null
     * @return array
     */
    public function getSupportedModels()
    {
        return array(
            'cms_page' => array('title' => $this->__('CMS Pages')),
            'cms_block' => array('title' => $this->__('Static Blocks')),
            'catalog_product' => array('title' => $this->__('Products')),
            //'catalog_category' => array('title' => $this->__('Categories')),
            //'customer_customer' => array('title' => $this->__('Customers')),
        );
    }

    /*
     * Helper-method to determine whether the current user has access
     *
     * @access public
     * @param Yireo_TrashCan_Model_Object
     * @return boolean
     */
    public function allowModify($item)
    {
        if(Mage::getSingleton('admin/session')->isAllowed('system/trashcan/all' == true)) {
            return true;
        }

        if(Mage::getSingleton('admin/session')->isAllowed('system/trashcan/owner') == true) {
            $itemUser = Mage::getModel('admin/user')->load($item->getData('trashed_by'));
            $currentUser = Mage::getSingleton('admin/session')->getUser();
            if($currentUser->getId() == $itemUser->getId()) {
                return true;
            }
        }

        return false;
    }

    /*
     * Helper-method to log something to the system-log
     *
     * @param string $string
     * @param mixed $mixed
     * @return null
     */
    public function log($string, $mixed = null)
    {
        if($mixed) {
            $string .= ': '.var_export($mixed, true);
        }
        Mage::log('[Trashcan]: '.$string);
    }

    /*
     * Helper-method to quickly log a debug-entry
     *
     * @param string $string
     * @param mixed $mixed
     * @return null
     */
    public function debug($variable, $text = null)
    {
        $log = null;
        if(!empty($text)) $log .= $text." - ";
        if(is_object($variable)) $log .= get_class($variable).": ";
        $log .= var_export($variable, true);

        if(!is_dir(BP.DS.'var'.DS.'log')) @mkdir(BP.DS.'var'.DS.'log');
        $tmp_file = BP.DS.'var'.DS.'log'.DS.'trashcan.log';

        file_put_contents($tmp_file, $log."\n", FILE_APPEND);
    }
}
