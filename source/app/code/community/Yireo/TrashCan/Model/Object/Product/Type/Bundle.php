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
 * Class Yireo_TrashCan_Model_Object_Product_Bundle
 */
class Yireo_TrashCan_Model_Object_Product_Type_Bundle extends Mage_Core_Model_Abstract implements Yireo_TrashCan_Model_Object_Product_Type_Contract
{
    /**
     * @param Mage_Catalog_Model_Product $object
     */
    public function prepare(Mage_Catalog_Model_Product &$object)
    {
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

        $object->setTrashcanData('bundle_options', $productOptionsData);
        $object->setTrashcanData('bundle_selections', $productSelectionsData);
    }

    /**
     * @param Mage_Catalog_Model_Product $object
     */
    public function restore(Mage_Catalog_Model_Product &$object)
    {
        $object->setCanSaveCustomOptions(true);
        $object->setCanSaveBundleSelections(true);
        $object->setAffectBundleProductSelections(true);

        $trashcanData = $object->getData('trashcan_data');

        if (!empty($trashcanData['bundle_options'])) {
            $object->setBundleOptionsData($trashcanData['bundle_options']);
        }

        if (!empty($trashcanData['bundle_selections'])) {
            $object->setBundleSelectionsData($trashcanData['bundle_selections']);
        }
    }

    /**
     * @param Mage_Catalog_Model_Product $object
     */
    public function postRestore(Mage_Catalog_Model_Product &$object)
    {
    }
}