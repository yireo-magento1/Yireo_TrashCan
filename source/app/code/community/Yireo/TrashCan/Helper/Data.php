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
class Yireo_TrashCan_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Check for the right PHP version
     *
     * @return bool
     */
    public function hasRightPhpVersion()
    {
        $phpversion = phpversion();

        if (version_compare($phpversion, '5.4.0', 'lt')) {
            return false;
        }

        return true;
    }

    /**
     * Redirect if the PHP version is not correct
     */
    public function redirectOnWrongPhpVersion()
    {
        if ($this->hasRightPhpVersion() == false) {
            $link = 'https://www.yireo.com/software/magento-extensions/trashcan/faq#does-this-extension-work-under-php-5-3';
            Mage::getModel('adminhtml/session')->addError($this->__('The Yireo Trashcan module requires PHP 5.4 to work. See our <a target="_new" href="%s">FAQ</a> for details', $link));
            session_write_close();

            Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('adminhtml/dashboard/index'));
            Mage::app()->getResponse()->sendResponse();
            return true;
        }

        return false;
    }

    /**
     * Helper-method to return a specific setting
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function setting($name, $group = 'settings', $default = null)
    {
        $value = Mage::getStoreConfig('trashcan/' . $group . '/' . $name);
        if (empty($value)) $value = $default;
        return $value;
    }

    /**
     * Helper-method to return the supported models
     *
     * @return array
     */
    public function getSupportedModels()
    {
        return array(
            'cms_page' => array('title' => $this->__('CMS Pages')),
            'cms_block' => array('title' => $this->__('Static Blocks')),
            'catalog_product' => array('title' => $this->__('Products')),
            'catalog_category' => array('title' => $this->__('Categories')),
        );
    }

    /**
     * Helper-method to return the object parsers used for archiving and restoring additional data
     *
     * @return array
     */
    public function getParserModels()
    {
        return array(
            'catalog_category' => 'trashcan/object_category',
            'catalog_product' => 'trashcan/object_product',
        );
    }

    /**
     * Helper-method to return the object parser for a specific data-type
     *
     * @param string $type
     * @return object|false
     */
    public function getParserModel($type)
    {
        $parserModels = $this->getParserModels();

        if (!isset($parserModels[$type])) {
            return false;
        }

        $model = Mage::getModel($parserModels[$type]);

        return $model;
    }

    /**
     * Helper-method to determine whether the current user has access
     *
     * @param Yireo_TrashCan_Model_Object
     * @return boolean
     */
    public function allowModify($item)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('system/trashcan/all' == true)) {
            return true;
        }

        if (Mage::getSingleton('admin/session')->isAllowed('system/trashcan/owner') == true) {
            $itemUser = Mage::getModel('admin/user')->load($item->getData('trashed_by'));
            $currentUser = Mage::getSingleton('admin/session')->getUser();
            if ($currentUser->getId() == $itemUser->getId()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper-method to log something to the system-log
     *
     * @param string $string
     * @param mixed $mixed
     */
    public function log($string, $mixed = null)
    {
        if ($mixed) {
            $string .= ': ' . var_export($mixed, true);
        }

        Mage::log('[Trashcan]: ' . $string);
    }

    /**
     * Helper-method to quickly log a debug-entry
     *
     * @param string $variable
     * @param string $text
     */
    public function debug($variable, $text = null)
    {
        $log = null;
        if (!empty($text)) $log .= $text . " - ";
        if (is_object($variable)) $log .= get_class($variable) . ": ";
        $log .= var_export($variable, true);

        if (!is_dir(BP . DS . 'var' . DS . 'log')) @mkdir(BP . DS . 'var' . DS . 'log');
        $tmp_file = BP . DS . 'var' . DS . 'log' . DS . 'trashcan.log';

        file_put_contents($tmp_file, $log . "\n", FILE_APPEND);
    }
}
