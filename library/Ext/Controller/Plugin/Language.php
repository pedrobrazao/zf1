<?php

class Ext_Controller_Plugin_Language extends Zend_Controller_Plugin_Abstract
{

    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
    }

    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $config = Zend_Registry::get('config');
        $language = $request->getParam('language', false);
        if (!$language) {
            $locale = new Zend_Locale();
            $language = $locale->getLanguage();
        }
        if (!$language || !isset($config['languages']['allowed'][$language])) {
            $language = $config['languages']['defaults'];
        }
        $request->setParam('language', $language);
        Zend_Registry::set('language', $language);
    }

    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
    }

}