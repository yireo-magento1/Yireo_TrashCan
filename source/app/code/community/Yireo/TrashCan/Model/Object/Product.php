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
class Yireo_TrashCan_Model_Object_Product extends Mage_Catalog_Model_Product implements Yireo_TrashCan_Model_Object_Contract
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
        // Reset the product registry
        $this->resetProductRegistry();

        // Bundled Products
        if($this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $dataHandler = Mage::getModel('trashcan/object_product_type_bundle');
            $dataHandler->prepare($this);
        }

        // Configurable Products
        if($this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $dataHandler = Mage::getModel('trashcan/object_product_type_configurable');
            $dataHandler->prepare($this);
        }

        // Add additional product-data
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($this->getId());
        $stockData = $stockItem->getData();
        $this->setTrashcanData('stock_data', $stockData);
        $this->setTrashcanData('website_ids', $this->getWebsiteIds());
        $this->setTrashcanData('category_ids', $this->getCategoryIds());
        $this->setTrashcanData('images', Mage::helper('trashcan/product')->backupImages($this));

        // Unset certain values
        $this->setMediaGallery(array());
        $this->setRelatedProduct(null);

        //Mage::helper('trashcan')->log('Prepared data', $this->debug());
    }

    /**
     * Method to run when this object is being restored from the trashcan
     */
    public function restore()
    {
        // Reset the product registry
        $this->resetProductRegistry();

        // Reset the media-gallery
        $this->setMediaGallery(array('images' => array()));

        // Get the meta-data
        $trashcanData = $this->getData('trashcan_data');

        // Bundled Products
        if($this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $dataHandler = Mage::getModel('trashcan/object_product_type_bundle');
            $dataHandler->restore($this);
        }

        // Configurable Products
        if($this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $dataHandler = Mage::getModel('trashcan/object_product_type_configurable');
            $dataHandler->restore($this);
        }

        // Load additional data of this object
        if(!empty($trashcanData)) {
            foreach($trashcanData as $trashcanId => $trashcanValue) {
                switch($trashcanId) {
                    case 'website_ids':
                        $this->setWebsiteIds($trashcanValue);
                        break;

                    case 'category_ids':
                        $this->setCategoryIds($trashcanValue);
                        break;

                    case 'images':
                        Mage::helper('trashcan/product')->restoreImages($this, $trashcanValue);
                        break;
                }
            }
        }
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    protected function getAdditionalData($name)
    {
        $trashcanData = $this->getData('trashcan_data');

        if (isset($trashcanData[$name])) {
            return $trashcanData[$name];
        }
    }

    /**
     * Method to run after this object has been restored
     */
    public function postRestore()
    {
        //Mage::helper('trashcan')->log('Restored data', $this->debug());

        $product = Mage::getModel('catalog/product')->load($this->getId());

        if(!empty($product)) {

            $storedProductImages = $this->getAdditionalData('images');
            Mage::helper('trashcan/product')->restoreImageLabels($product, $storedProductImages);

            $storedStockData = $this->getAdditionalData('stock_data');
            Mage::helper('trashcan/product')->restoreStockData($product, $storedStockData);
            
            // Re-save the product
            $product->save();
        }

        // Bundled Products
        if($this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
            $dataHandler = Mage::getModel('trashcan/object_product_type_bundle');
            $dataHandler->postRestore($this);
        }

        // Configurable Products
        if($this->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $dataHandler = Mage::getModel('trashcan/object_product_type_configurable');
            $dataHandler->postRestore($this);
        }
    }

    /**
     * Return searchable data
     *
     * @return array
     */
    public function getSearchData()
    {
        return array(
            $this->getSku(),
            $this->getName(),
        );
    }

    /**
     * Reset all product instances in the Magento registry
     */
    protected function resetProductRegistry()
    {
        if(Mage::registry('product')) {
            Mage::unregister('product');
        }

        if(Mage::registry('current_product')) {
            Mage::unregister('current_product');
        }

        Mage::register('product', $this);
        Mage::register('current_product', $this);
    }
}
