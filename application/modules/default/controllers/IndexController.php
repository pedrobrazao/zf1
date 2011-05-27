<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
    }

    public function indexAction()
    {
        $language = Zend_Registry::get('language');
        $this->_helper->translatedView($language);
    }


}

