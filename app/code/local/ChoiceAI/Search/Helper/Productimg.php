<?php

class ChoiceAI_Search_Helper_Productimg extends Mage_ConfigurableSwatches_Helper_Productimg
{
    /**
     * Determine if the passed text matches the label of any of the passed product's images
     *
     * @param string $text
     * @param Mage_Catalog_Model_Product $product
     * @param string $type
     * @return Varien_Object|null
     */
    // Required to avoid issue where $this->_productImagesByLabel doesn't exist
    public function getProductImgByLabel($text, $product, $type = null)
    {
        $this->indexProductImages($product);

        if (count($this->_productImagesByLabel)) {
            //Get the product's image array and prepare the text
            $images = $this->_productImagesByLabel[$product->getId()];
            $text = Mage_ConfigurableSwatches_Helper_Data::normalizeKey($text);

            $resultImages = array(
                'standard' => isset($images[$text]) ? $images[$text] : null,
                'swatch' => isset($images[$text . self::SWATCH_LABEL_SUFFIX]) ? $images[$text . self::SWATCH_LABEL_SUFFIX]
                    : null,
            );

            if (!is_null($type) && array_key_exists($type, $resultImages)) {
                $image = $resultImages[$type];
            } else {
                $image = (!is_null($resultImages['swatch'])) ? $resultImages['swatch'] : $resultImages['standard'];
            }
        } else {
            $image = null;
        }

        return $image;
    }

}

?>