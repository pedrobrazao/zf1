<?php
/**
 * Zend Framework Extended Library
 *
 * @category   Ext
 * @package    Ext_Controller
 * @subpackage Ext_Controller_Action_Helper
 * @author     Pedro Brazao <pbf@frameweb.net>
 */
class Ext_Controller_Action_Helper_TranslatedView extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * Renders or outputs the name of the view script according to the language.
     * If the intended script doesn't exist, uses the default script
     * 
     * @param string $language The needed language code
     * @param bool $render If true, the view is rendered here
     * @return string The view script name
     */
    public function direct($language = '', $render = true)
    {
        // get the view object registered with the current controller
        $view = $this->getActionController()->view;
        // get the view script name
        $script = $this->getActionController()->getViewScript();
        // look for a translated script; the name will be defaultname-LANGUAGE.phtml
        foreach ($view->getScriptPaths() as $path) {
            $name = str_replace('.phtml', '-'.$language.'.phtml', $script);
            if (file_exists($path.'/'.$name)) {
                $script = $name;
                break;
            }
        }
        if ($render) {
            $view->render($script);
        }
        return $script;
    }

}