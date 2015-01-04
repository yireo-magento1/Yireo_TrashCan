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

/**
 * Trashed objects model
 */
class Yireo_TrashCan_Model_Object extends Mage_Core_Model_Abstract
{
    /**
     * Constructor
     * 
     * @param null
     * @return mixed
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('trashcan/object');
    }

    /*
     * Method to fill this object with data from another to-be-removed object
     * 
     * @param mixed $object
     * @return boolean
     */
    public function loadFromObject($object)
    {
        // Add a label
        $label = $object->getData('name');
        if(empty($label)) $label = $object->getData('title');
        if(empty($label)) $label = $object->getData('label');

        // Add the resource-model
        $resourceClass = preg_replace('/\//', '_', $object->getResourceName(), 1);
        if(empty($resourceClass)) {
            $resourceClass = $object->getResourceName(); // @todo: Does this work?
        }

        // Load additional data of this object
        if($resourceClass == 'catalog_product') {

            // Add the product-type to the label
            $label .= ' ['.$object->getTypeId().']';

            // Set the current product
            if(Mage::registry('product')) Mage::unregister('product');
            if(Mage::registry('current_product')) Mage::unregister('current_product');
            Mage::register('product', $object);
            Mage::register('current_product', $object);

            // Initialize the type-instance
            $productType = $object->getTypeInstance(true);

            // Bundled Products
            if($object->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {

                // Initialize data
                $productOptionsData = array();
                $productSelectionsData = array();

                // Gather the bundle-options
                $productOptions = Mage::getResourceModel('bundle/option_collection')->setProductIdFilter($object->getId());
                foreach($productOptions as $productOptionId => $productOption) {

                    $productOptionsData[$productOptionId] = array(
                        'title' => 'Option '.$productOption->getData('option_id'),
                        'default_title' => 'Option '.$productOption->getData('option_id'),
                        'type' => $productOption->getData('type'),
                        'required' => $productOption->getData('required'),
                        'position' => $productOption->getData('position'),
                        'option_id' => '',
                        'delete' => '',
                    );

                    // Gather the bundle-product-selections
                    $productSelections = Mage::getResourceModel('bundle/selection_collection')->setOptionIdsFilter(array($productOption->getData('option_id')));
                    foreach($productSelections as $productSelection) {
                        $productSelectionsData[$productOptionId][] = array(
                            'product_id' => $productSelection->getData('product_id'),
                            'selection_price_type' => $productSelection->getData('selection_price_type'),
                            'selection_price_value' => $productSelection->getData('selection_price_value'),
                            'selection_qty' => $productSelection->getData('selection_qty'),
                            'selection_can_change_qty' => $productSelection->getData('selection_can_change_qty'),
                            'position' => $productSelection->getData('position'),
                            'selection_id' => '',
                            'option_id' => '',
                            'delete' => '',
                        );
                    }
                }

                // Set the bundle-data
                $object = $this->setTrashcanData($object, 'bundle_options', $productOptionsData);
                $object = $this->setTrashcanData($object, 'bundle_selections', $productSelectionsData);
            }

            // Add additional product-data
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($object->getId());
            $stockData = $stockItem->getData();
            $object = $this->setTrashcanData($object, 'stock_data', $stockData);
            $object = $this->setTrashcanData($object, 'website_ids', $object->getWebsiteIds());
            $object = $this->setTrashcanData($object, 'category_ids', $object->getCategoryIds());
            $object = $this->setTrashcanData($object, 'images', Mage::helper('trashcan/product')->backupImages($object));

            // Unset certain values
            $object->setMediaGallery(array());
            $object->setRelatedProduct(null);
        }

        // Add the resource-data
        $this->setResourceData(serialize($object));

        // Set extra meta-data
        $currentUser = Mage::getModel('admin/session')->getUser();
        $this->setLabel($label);
        $this->setResourceClass($resourceClass);
        $this->setTrashedBy($currentUser->getId());
        $this->setTrashedTimestamp(date('y-m-d H:i', time()));

        return true;
    }

    /*
     * Helper-method to add meta-data to the object
     * 
     * @param object $object
     * @param string $name
     * @param mixed $data
     * @return object
     */
    public function setTrashcanData($object, $name, $data)
    {
        $trashcanData = $object->getData('trashcan_data');
        if(!is_array($trashcanData)) $trashcanData = array();
        $trashcanData[$name] = $data;
        $object->setData('trashcan_data', $trashcanData);
        return $object;
    }

    /*
     * Helper-method to retrieve meta-data from the object
     * 
     * @param mixed $object
     * @param string $name
     * @return mixed 
     */
    public function getTrashcanData($object, $name = null)
    {
        $trashcanData = $object->getData('trashcan_data');
        if(isset($trashcanData[$name])) {
            return $trashcanData[$name];
        } elseif(empty($name)) {
            return $trashcanData;
        } 
        return null; 
    }

    /*
     * Restore-method
     * 
     * @param null
     * @return boolean
     */
    public function restore()
    {
        // Do not continue, if no ID is set
        if(!$this->getObjectId() > 0) {
            Mage::getModel('adminhtml/session')->addError(Mage::helper('trashcan')->__('Can not locate object'));
            return false;
        }

        // Check if this object is supported
        $supportedModels = Mage::helper('trashcan')->getSupportedModels();
        if(array_key_exists($this->getResourceClass(), $supportedModels) == false) {
            Mage::getModel('adminhtml/session')->addError(Mage::helper('trashcan')->__('Restoration is not possible, because this resource is not supported'));
            return false;
        }

        // Check the configuration whether trash-can is enabled
        $config = Mage::helper('trashcan')->setting('enable_'.$this->getResourceClass());
        if($config != 1) {
            Mage::getModel('adminhtml/session')->addError(Mage::helper('trashcan')->__('Disabled restoration through settings in the Magento Configuration'));
            return false;
        }
        
        // Construct the proper resource class
        $resourceClass = str_replace('_','/', $this->getResourceClass());
        $resourceModel = Mage::getResourceSingleton($resourceClass);

        // Unserialize the data 
        $object = unserialize($this->getResourceData());
        $object->setId(null);

        // Load meta-data
        $trashcanData = $object->getData('trashcan_data');

        // Product-specific unserializing
        if($resourceClass == 'catalog/product') {

            // Reset the media-gallery
            $object->setMediaGallery(array('images' => array()));

            // Specific things for bundled products
            if($object->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                $object->setCanSaveCustomOptions(true);
                $object->setCanSaveBundleSelections(true);
                $object->setAffectBundleProductSelections(true);
                if(Mage::registry('product')) Mage::unregister('product');
                if(Mage::registry('current_product')) Mage::unregister('current_product');
                Mage::register('product', $object);
                Mage::register('current_product', $object);
            }

            // Load additional data of this object
            if(!empty($trashcanData)) {
                foreach($trashcanData as $trashcanId => $trashcanValue) {
                    switch($trashcanId) {
                        case 'stock_data':
                            $storedStockData = $trashcanValue;
                            break;
                        case 'website_ids':
                            $object->setWebsiteIds($trashcanValue);
                            break;
                        case 'category_ids':
                            $object->setCategoryIds($trashcanValue);
                            break;
                        case 'images':
                            $object = Mage::helper('trashcan/product')->restoreImages($object, $trashcanValue);
                            $storedProductImages = $trashcanValue;
                            break;
                        case 'bundle_options':
                            $bundleOptionsData = $trashcanValue;
                            $object->setBundleOptionsData($bundleOptionsData);
                            //$optionModel = Mage::getModel('bundle/option')->addSelection
                            break;
                        case 'bundle_selections':
                            $bundleSelectionsData = $trashcanValue;
                            // @todo: Check whether product-ID is still valid
                            $object->setBundleSelectionsData($bundleSelectionsData);
                            break;
                    }
                }
            }
        }

        // Category-specific unserializing
        if($resourceClass == 'catalog/category') {
        }

        // Customer-specific unserializing
        if($resourceClass == 'customer/customer') {
        }

        // Save it with the resource
        $resourceModel->beginTransaction();
        try {
            $rt = $resourceModel->save($object);

        } catch (Exception $e){
            $resourceModel->rollBack();
            Mage::getModel('adminhtml/session')->addError(Mage::helper('trashcan')->__('Save failed: '.$e->getMessage()));
            return false;
        }

        // Commit the resource
        try {
            $rt = $resourceModel->commit();
        } catch (Exception $e){
            $resourceModel->rollBack();
            Mage::getModel('adminhtml/session')->addError(Mage::helper('trashcan')->__('Commit failed: '.$e->getMessage()));
            return false;
        }

        // Post-restore procedures
        if($resourceClass == 'catalog/product') {

            $product = Mage::getModel('catalog/product')->load($object->getId());

            if(!empty($product)) {
            
                // Restore labels && position of images
                if(!empty($storedProductImages)) {
                    $gallery = $product->getData('media_gallery');
                    foreach($gallery['images'] as $imageIndex => $image) {
                        foreach($storedProductImages as $storedProductImage) {
                            if(basename($image['file']) == basename($storedProductImage['file'])) {
                                $image['label'] = $storedProductImage['label'];
                                $image['position'] = $storedProductImage['position'];
                                $gallery['images'][$imageIndex] = $image;
                                break;
                            }
                        }
                    }
                    $product->setData('media_gallery', $gallery);
                }

                // Check for stock-data
                if(!empty($storedStockData)) {
                    if(isset($storedStockData['item_id'])) unset($storedStockData['item_id']);
                    if(isset($storedStockData['product_id'])) unset($storedStockData['product_id']);
                    if(isset($storedStockData['stock_id'])) unset($storedStockData['stock_id']);
                    $product->setStockData($storedStockData);
                }

                // Re-save the product
                $product->save();
            }
        }

        // Remove the trashed item itself
        $this->delete();

        return true;
    }

    /*
     * Delete-method
     * 
     * @param null
     * @return mixed
     */
    public function delete()
    {
        return parent::delete();
    }
}
