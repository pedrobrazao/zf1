<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    /**
     * Initializes the configuration
     *
     * @return Array The configuration loaded.
     */
    protected function _initConfigs()
    {
        Zend_Registry::isRegistered('config')
                || Zend_Registry::set('config', $this->_options, true);
        return $this->_options;
    }

}

