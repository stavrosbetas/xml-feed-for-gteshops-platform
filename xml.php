<?php
require_once '../app/Mage.php';
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
Mage::app('default');
try{
    $handle = fopen('../gteshops-feed'.'.xml', 'w');
    $heading = '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
    $heading .= '<Products xmlns="https://gteshops.gr"
xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="https://gteshops.gr products.xsd">
'."\r\n";
    $feed_line = $heading;
    fwrite($handle, $feed_line);
    //GET THE PRODUCTS
    $products = Mage::getModel('catalog/product')->getCollection();
    $products->addAttributeToFilter('status', 1); //Enabled
    $products->addAttributeToFilter('visibility', 4); //Catalog, Search
    $products->addAttributeToSelect('*');
    $prodIds = $products->getAllIds();
    $product = Mage::getModel('catalog/product');
    foreach($prodIds as $productId) {
        $product->load($productId);
        $productType = $product->getTypeID();
        if($productType == 'configurable') {
            $productgetId = Mage::getModel('catalog/product')->load($product->getId());
            $product_data = array();
            $product_data['start']  = "\t\t".'<Product>'."\r\n";
            /*Product Sku */
            $product_data['id']     = "\t".'<SKU>'.$product->getSku().'</SKU>'."\r\n"; //ID
            /*Product Category*/
            foreach($product->getCategoryIds() as $_categoryId){
                $category = Mage::getModel('catalog/category')->load($_categoryId);
                $product_data['category'].=$category->getName().' > ';
            }
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $collection = $category->getResourceCollection();
            $pathIds = $category->getPathIds();
            $collection->addAttributeToSelect('name');
            $collection->addAttributeToFilter('entity_id', array('in' => $pathIds));
            $result = '';
            $i = 0;
            foreach ($collection as $cat) {
                $i++;
                if ($i >= 3){
                    $xxx = $result .= $cat->getName(). ' / ';
                }
            }
            $categoriesList = $productgetId->getCategoryIds();
            $categoryId = end($categoriesList);
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $product_data['category'] = "\t".'<Category><![CDATA['.$xxx .']]></Category>'."\r\n";
            /*Product Name*/
            $product_data['name']   = "\t".'<Title><![CDATA['.$product->getName().' ('.$product->getResource()->getAttribute('color')->getFrontend()->getValue($product).')]]></Title>'."\r\n";
            /*Product Brand*/
            $product_data['manufacturer'] = "\t".'<Manufacturer><![CDATA['.$product->getResource()->getAttribute('brand')->getFrontend()->getValue($product).']]></Manufacturer>'."\r\n";
            /*Product Description*/
            $product_data['description'] = "\t".'<Description><![CDATA['.$product->getShortDescription().']]></Description>'."\r\n";
            /*Product Image Url*/
            $product_data['image']  = "\t".'<ImageLink><![CDATA['.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$product->getImage().']]></ImageLink>'."\r\n";
            /*Product Price*/
            $normal_price = number_format($product->getPrice(), 2, '.', '');
            $product_data['normal_price'] = "\t".'<ListPrice>'.$normal_price.'</ListPrice>'."\r\n";
            /*Product Final Price*/
            $special_price = number_format($product->getFinalPrice(), 2, '.', ''); //Special Price
            $product_data['sale_price'] = "\t".'<SalePrice>'.$special_price.'</SalePrice>'."\r\n";
            /*Product Availability*/
            $product_data['availability'] = "\t".'<AvailabilityLevel>available</AvailabilityLevel>'."\r\n";
            /*Product Has Dimensions*/
            $product_data['dimensions'] = "\t".'<HasDimensions>no</HasDimensions>'."\r\n";
            /*Product Status*/
            $product_data['status'] = "\t".'<Status>active</Status>'."\r\n";
            /*Product Additional Image Urls*/
            if (count($productgetId->getMediaGalleryImages()) > 1){
                $product_data['secondary_images']  = "\t\t".'<SecondaryImages>'."\r\n";
                foreach($productgetId->getMediaGalleryImages() as $image) {
                    $product_data['additionalimage'] .= "\t".'<SecondaryImageLink><![CDATA['.$image->getUrl().']]></SecondaryImageLink>'."\r\n";
                }
                $product_data['end_secondary_images'] = '</SecondaryImages>';
            }
            /*Product Has Attributes*/
            $product_data['availability'] = "\t".'<HasAttributes>yes</HasAttributes>'."\r\n";
            /*Product Attribute Name*/
            $product_data['attribute_label']  = "\t\t".'<AttributeLabels>'."\r\n";
                $product_data['attribute_name'] .= "\t".'<AttributeLabel>Μέγεθος</AttributeLabel>'."\r\n";
            $product_data['end_attribute_label'] = '</AttributeLabels>';
            /*Product Attribute Sets*/
            $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
            $attributeSetModel->load($product->getAttributeSetId());
            $attributeSetName = $attributeSetModel->getAttributeSetName();
            $sizes = "";
            $shoe_sizes = "";
            $misc_sizes = "";
            foreach($productgetId->getTypeInstance(true)->getUsedProducts(null, $productgetId) as $simple) {
                $associatedStock = (int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($simple)->getQty();
                if($associatedStock !== 0) {
                    // There MUST be at least one Associated Product with size!
                    $attributeSetModel = Mage::getModel("eav/entity_attribute_set");
                    $attributeSetModel->load($product->getAttributeSetId());
                    $attributeSetName = $attributeSetModel->getAttributeSetName();
                    $attribute_shoes = 'Shoes';
                    $attribute_clothing = 'Clothing';
                    $attribute_misc = 'Misc';
                    $product_sku = $simple->getResource()->getAttribute('sku')->getFrontend()->getValue($simple);
                    
                    if ($attributeSetName = $attribute_shoes){
                        $no = $simple->getResource()->getAttribute('shoe_size')->getFrontend()->getValue($simple);
                        if ($no != 'No') {
                            $shoe_sizes .= "\t\t" . '<AttributeSet>' . "\r\n" . "\t\t" . '<AttributeSetValues>' . "\r\n" . '<AttributeSetValue>' . $simple->getResource()->getAttribute('shoe_size')->getFrontend()->getValue($simple) . '</AttributeSetValue>' . "\r\n" . '</AttributeSetValues>' . "\r\n"  . "\t".'<AttributeSetSKU>'.$product_sku."\r\n".'</AttributeSetSKU>' . "\t".'<AttributeSetAvailabilityLevel>'.'available'.'</AttributeSetAvailabilityLevel>'.'</AttributeSet>' . "\r\n";
                        }
                    }
                    if ($attributeSetName = $attribute_clothing){
                        $no = $simple->getResource()->getAttribute('size')->getFrontend()->getValue($simple);
                        if ($no != 'No') {
                            $shoe_sizes .= "\t\t".'<AttributeSet>'."\r\n" . "\t\t".'<AttributeSetValues>'."\r\n" .'<AttributeSetValue>'.$simple->getResource()->getAttribute('size')->getFrontend()->getValue($simple). '</AttributeSetValue>'."\r\n" .'</AttributeSetValues>'."\r\n"  . "\t".'<AttributeSetSKU>'.$product_sku."\r\n".'</AttributeSetSKU>' . "\t".'<AttributeSetAvailabilityLevel>'.'available'.'</AttributeSetAvailabilityLevel>' .'</AttributeSet>'."\r\n";
                        }
                    }
                    if ($attributeSetName = $attribute_misc){
                        $no = $simple->getResource()->getAttribute('accessories_size')->getFrontend()->getValue($simple);
                        if ($no != 'No') {
                            $shoe_sizes .= "\t\t".'<AttributeSet>'."\r\n" . "\t\t".'<AttributeSetValues>'."\r\n" .'<AttributeSetValue>'.$simple->getResource()->getAttribute('accessories_size')->getFrontend()->getValue($simple). '</AttributeSetValue>'."\r\n" .'</AttributeSetValues>'."\r\n" . "\t".'<AttributeSetSKU>'.$product_sku."\r\n". "\t".'<AttributeSetAvailabilityLevel>'.'available'.'</AttributeSetAvailabilityLevel>' .'</AttributeSet>'."\r\n";
                        }
                    }
                }
            }
        
            foreach($productgetId->getTypeInstance(true)->getUsedProducts(null, $productgetId) as $simple) {
                $associatedStock = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($simple)->getQty();
                if ($associatedStock !== 0) {
                    $product_data['attribute_sets'] = "\t\t" . '<AttributeSets>' . "\r\n";
                    $product_data['shoe_size'] = $shoe_sizes;
                    $product_data['end_attribute_sets'] = '</AttributeSets>';
                }
            }
            /*Product Url*/
            $product_data['end'] = '</Product>';
            foreach($product_data as $k=>$val){
                $product_data[$k] = $val;
            }
            $feed_line = implode("\t\t", $product_data)."\r\n";
            fwrite($handle, $feed_line);
            fflush($handle);
        }
    }
    $footer .= '</Products>';
    $feed_line = $footer;
    fwrite($handle, $feed_line);
    fclose($handle);
} catch(Exception $e) {
    die($e->getMessage());
}