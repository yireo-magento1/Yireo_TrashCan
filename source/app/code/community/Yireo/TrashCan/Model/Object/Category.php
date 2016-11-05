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
 * Class Yireo_TrashCan_Model_Object_Category
 */
class Yireo_TrashCan_Model_Object_Category extends Mage_Catalog_Model_Category implements Yireo_TrashCan_Model_Object_Contract
{
    /**
     * Include traits
     */
    use Yireo_TrashCan_Model_Object_Trait;

    /**
     * Method to run when this object is being stored into the trashcan
     */
    public function prepare()
    {
        $this->setTrashcanData('original_parent_id', $this->getParentId());
    }

    /**
     * Method to run when this object is being restored from the trashcan
     */
    public function restore()
    {
        $originalParentId = $this->getTrashcanData('original_parent_id');

        $parent = Mage::getModel('catalog/category')->load($originalParentId);
        if ($parent->getId() > 0 == false) {
            $rootCategoryId = Mage::app()->getAnyStoreView()->getRootCategoryId();
            $parent = Mage::getModel('catalog/category')->load($rootCategoryId);
        }

        $this->setParentId($parent->getId());
        $this->setPath($parent->getPath());
    }

    /**
     * Method to run after this object has been restored
     */
    public function postRestore()
    {
    }

    /**
     * Return searchable data
     * 
     * @return array
     */
    public function getSearchData()
    {
        return array(
            $this->getName(),
        );
    }
}