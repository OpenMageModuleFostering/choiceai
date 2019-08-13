<?php

/**
 * @category    ChoiceAI
 * @package     ChoiceAI_Personalisation
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChoiceAI_Personalisation_Helper_Data extends Mage_Core_Helper_Data
{

    const CONFIG_ACTIVE = 'choiceai_personalisation/settings/active';
    const CONFIG_API_KEY = 'choiceai_personalisation/settings/api_key';

    public function isModuleEnabled($moduleName = null)
    {
        if (Mage::getStoreConfig(self::CONFIG_ACTIVE) == '0') {
            return false;
        }

        return parent::isModuleEnabled($moduleName = null);
    }

    public function getBaseScript($store = null)
    {

//        $baseScript = "";

        try {
            $baseScript = "\n<!-- ChoiceAI Script begins -->\n<script type='text/javascript'>!function(){function t(){
  if(!window.CAIBASE){var t=document.createElement('script'),n='beaconhttp.choice.ai';
  t.type='text/javascript',t.async=!0,
  'https:'==location.protocol&&(n='d3caf2da6t944y.cloudfront.net'),t.src='//'+n+'/site/ethno/ORG_HANDLE/choice.js';
  var e=document.getElementsByTagName('script')[0];e.parentNode.insertBefore(t,e)}}
  window.CAIBASE&&window.CAIBASE.reinit&&window.CAIBASE.reinit(),!window.CAIBASE && t()}();</script>
  \n<!-- ChoiceAI Script ends -->\n";
            $baseScript = str_replace(
                "ORG_HANDLE", explode(
                    "_", Mage::getStoreConfig(
                        self::CONFIG_API_KEY, $store
                    )
                )[0], $baseScript
            );
        } catch (Exception $e) {
            $baseScript = "";
        }

        return $baseScript;

    }

}