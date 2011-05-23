<?php

/**
 * Setup layout based on current module name
 *
 * @author jon
 */
class Ext_Controller_Plugin_Layout extends Zend_Controller_Plugin_Abstract {

    public function preDispatch(Zend_Controller_Request_Abstract $request) 
    {
        Zend_Layout::getMvcInstance()->setLayout($request->getModuleName());
    }

}

