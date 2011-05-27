<?php

class Ext_Controller_Plugin_Language extends Zend_Controller_Plugin_Abstract
{

    /**
     * Ensure we have a valid language
     * @param Zend_Controller_Request_Abstract $request 
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        // get the registered configuration
        $config = Zend_Registry::get('config');
        
        // get the language parameter from the request
        $language = $request->getParam('language', false);
        
        // user locale
        $locale = new Zend_Locale();
        
        // if language does not in the request parameters, get it from the user locale
        if (!$language) {
            $language = $locale->getLanguage();
        }
        
        // if language stills undefined or not in allowed languages, setting it to the default value
        if (!$language || !isset($config['languages']['allowed'][$language])) {
            $language = $config['languages']['defaults'];
        }
        
        // force the language parameter on the request
        $request->setParam('language', $language);
        
        // register language for future use
        Zend_Registry::set('language', $language);
        
        // setup the translation file
        $file = sprintf('%s/configs/languages/%s.php', APPLICATION_PATH, $language);
        
        // setup the translator object
        $translate = new Zend_Translate('array', $file, $language);
        
        // register the translator for future use
        Zend_Registry::set('Zend_Translate', $translate);
    
    }

}