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

class Yireo_TrashCan_Helper_Product extends Mage_Core_Helper_Abstract
{
    /*
     * Helper-method to return a specific setting
     *
     * @access public
     * @param Mage_Catalog_Model_Product $product
     * @return mixed
     */
    public function backupImages($product)
    {
        $trashcanFolder = BP.DS.'media'.DS.'trashcan';
        if(is_dir($trashcanFolder) == false) {
            @mkdir($trashcanFolder);
        }

        $trashcanData = array();
        $images = $product->getMediaGallery('images');
        if(!empty($images)) {
            foreach($images as $image) {

                $image['type'] = array();
                if($image['file'] == $product->getSmallImage()) $image['type'][] = 'small_image';
                if($image['file'] == $product->getImage()) $image['type'][] = 'image';
                if($image['file'] == $product->getThumbnail()) $image['type'][] = 'thumbnail';

                $sourceFile = BP.DS.'media'.DS.'catalog'.DS.'product'.DS.$image['file'];
                $destinationFile = $trashcanFolder.DS.basename($image['file']);

                if(!is_file($sourceFile) || !is_readable($sourceFile)) {
                    Mage::log('[Trashcan] Empty source-image '.$sourceFile);
                    continue;
                }

                if(@copy($sourceFile, $destinationFile)) {
                    $trashcanData[] = array(
                        'file' => $destinationFile,
                        'label' => $image['label'],
                        'position' => $image['position'],
                        'disabled' => $image['disabled'],
                        'type' => $image['type'],
                    );
                    @unlink($sourceFile);
                }
            }
        }

        return $trashcanData;
    }

    public function restoreImages($product, $trashcanData)
    {
        if(!empty($trashcanData)) {
            foreach($trashcanData as $image) {
                $file = $image['file'];
                if(file_exists($file)) {
                    $disabled = (bool)$image['disabled'];
                    $product = $product->addImageToMediaGallery($file, $image['type'], true, $disabled);
                }
            }
        }

        return $product;
    }
}
