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
class Yireo_TrashCan_Helper_Product extends Mage_Core_Helper_Abstract
{
    /**
     * @var Yireo_TrashCan_Helper_Data
     */
    protected $helper;

    /**
     * Yireo_TrashCan_Helper_Product constructor.
     */
    public function __construct()
    {
        $this->helper = Mage::helper('trashcan');
    }

    /**
     * Helper-method to return a specific setting
     *
     * @param Mage_Catalog_Model_Product $product
     *
     * @return mixed
     */
    public function backupImages(Mage_Catalog_Model_Product $product)
    {
        $images = $product->getMediaGallery('images');
        if (empty($images)) {
            return array();
        }

        $trashcanData = array();
        foreach ($images as $image) {
            $imageData = $this->backupImage($image, $product);

            if (!empty($imageData)) {
                $trashcanData[] = $imageData;
            }
        }

        return $trashcanData;
    }

    /**
     * @param array $image
     * @param Mage_Catalog_Model_Product $product
     *
     * @return bool
     */
    protected function backupImage($image, Mage_Catalog_Model_Product $product)
    {
        $trashcanFolder = $this->helper->getTrashcanFolder();
        $sourceFile = BP . DS . 'media' . DS . 'catalog' . DS . 'product' . DS . $image['file'];
        $destinationFile = $trashcanFolder . DS . basename($image['file']);

        $image['type'] = $this->getImageTypesFromProduct($image['file'], $product);

        if (!is_file($sourceFile) || !is_readable($sourceFile)) {
            $this->helper->log('Empty source-image ' . $sourceFile);
            return false;
        }

        if (!copy($sourceFile, $destinationFile)) {
            return false;
        }

        $imageData = array(
            'file' => $destinationFile,
            'label' => $image['label'],
            'position' => $image['position'],
            'disabled' => $image['disabled'],
            'type' => $image['type'],
        );

        if (file_exists($sourceFile)) {
            unlink($sourceFile);
        }

        return $imageData;
    }

    /**
     * @param string $imageFile
     * @param Mage_Catalog_Model_Product $product
     *
     * @return array
     */
    protected function getImageTypesFromProduct($imageFile, Mage_Catalog_Model_Product $product)
    {
        $types = array();
        if ($imageFile == $product->getSmallImage()) {
            $types[] = 'small_image';
        }

        if ($imageFile == $product->getImage()) {
            $types[] = 'image';
        }

        if ($imageFile == $product->getThumbnail()) {
            $types[] = 'thumbnail';
        }

        return $types;
    }

    /**
     * Method to restore images from the trashed data
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $images
     *
     * @return bool
     */
    public function restoreImages(Mage_Catalog_Model_Product &$product, $images)
    {
        if (empty($images)) {
            return false;
        }

        foreach ($images as $image) {
            $this->restoreImage($product, $image);
        }

        return true;
    }

    /**
     * Method to restore a single image
     *
     * @param Mage_Catalog_Model_Product $product
     * @param array $image
     *
     * @return bool
     */
    protected function restoreImage(Mage_Catalog_Model_Product &$product, $image)
    {
        $file = $image['file'];
        if (!file_exists($file)) {
            return false;
        }

        $disabled = (bool)$image['disabled'];
        $product = $product->addImageToMediaGallery($file, $image['type'], true, $disabled);

        return true;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $storedImages
     *
     * @return bool
     */
    public function restoreImageLabels(Mage_Catalog_Model_Product &$product, $storedImages)
    {
        if (empty($storedImages)) {
            return false;
        }

        // Restore labels && position of images
        $gallery = $product->getData('media_gallery');
        foreach ($gallery['images'] as $imageIndex => $image) {
            foreach ($storedImages as $storedImage) {
                if (basename($image['file']) == basename($storedImage['file'])) {
                    $image['label'] = $storedImage['label'];
                    $image['position'] = $storedImage['position'];
                    $gallery['images'][$imageIndex] = $image;
                    break;
                }
            }
        }

        $product->setData('media_gallery', $gallery);

        return true;
    }

    /**
     * @param Mage_Catalog_Model_Product $product
     * @param array $storedStockData
     *
     * @return bool
     */
    public function restoreStockData(Mage_Catalog_Model_Product &$product, $storedStockData)
    {
        if (empty($storedStockData)) {
            return false;
        }

        // Check for stock-data
        if (isset($storedStockData['item_id'])) {
            unset($storedStockData['item_id']);
        }

        if (isset($storedStockData['product_id'])) {
            unset($storedStockData['product_id']);
        }

        if (isset($storedStockData['stock_id'])) {
            unset($storedStockData['stock_id']);
        }

        $product->setStockData($storedStockData);

        return true;
    }
}
