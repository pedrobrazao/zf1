<?php

class LanguagesController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        
        // assign allowed languages
        $config = Zend_Registry::get('config');
        $languages = $config['languages']['allowed'];
        $this->view->assign('languages', $languages);
        
        // assign current language
        $language = $this->_request->getParam('language');
        $this->view->assign('language', $language);
    }


}

