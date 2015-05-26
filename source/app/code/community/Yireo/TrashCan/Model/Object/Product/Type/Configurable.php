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
 * Class Yireo_TrashCan_Model_Object_Product_Configurable
 */
class Yireo_TrashCan_Model_Object_Product_Type_Configurable extends Mage_Core_Model_Abstract implements Yireo_TrashCan_Model_Object_Product_Type_Contract
{
    /**
     * @param Mage_Catalog_Model_Product $object
     */
    public function prepare(Mage_Catalog_Model_Product &$object)
    {
        /* @var $productType Mage_Catalog_Model_Product_Type_Configurable */
        $productType = $object->getTypeInstance();
        $object->setTrashcanData('configurable_product_attribute_ids', $productType->getUsedProductAttributeIds($object));
        $object->setTrashcanData('configurable_product_ids', $productType->getUsedProductIds($object));
        $object->setTrashcanData('configurable_attributes', $productType->getConfigurableAttributesAsArray($object));
    }


    /**
     * @param Mage_Catalog_Model_Product $object
     */
    public function restore(Mage_Catalog_Model_Product &$object)
    {
    }

    /**
     * @param Mage_Catalog_Model_Product $object
     */
    public function postRestore(Mage_Catalog_Model_Product &$object)
    {
        if ($object->getId() > 0) {
            $resource = Mage::getSingleton('core/resource');
            $write = $resource->getConnection('core_write');
            $table = $resource->getTableName('catalog/product_super_attribute');
            $write->delete($table, 'product_id = ' . $object->getId());
        }

        $object->setCanSaveConfigurableAttributes(true);

        $trashcanData = $object->getData('trashcan_data');

        if (!empty($trashcanData['configurable_product_ids'])) {

            // Check whether these product IDs are still valid
            $productIds = $trashcanData['configurable_product_ids'];

            $collection = Mage::getResourceModel('catalog/product_collection');
            $collection->addFieldToFilter('entity_id', array('in' => $productIds))->load();
            $foundIds = array_intersect($productIds, array_keys($collection->toArray()));
            $configurableProductIds = array();

            foreach($foundIds as $foundId) {
                $configurableProductIds[$foundId] = $foundId;
            }

            $object->setConfigurableProductsData($configurableProductIds);
        }

        if (!empty($trashcanData['configurable_attributes'])) {
            $configurableAttibutes = $trashcanData['configurable_attributes'];

            foreach($configurableAttibutes as &$configurableAttibute) {

                $configurableAttibute['id'] = null;

                foreach($configurableAttibute['values'] as &$value) {
                    if (isset($value['product_super_attribute_id'])) {
                        unset($value['product_super_attribute_id']);
                    }
                }
            }

            $object->setConfigurableAttributesData($configurableAttibutes);
        }

        $object->save();
    }
}