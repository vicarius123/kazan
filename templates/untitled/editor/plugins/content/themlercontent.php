<?php

defined('_JEXEC') or die;
 
/**
* Themler plugin
*/

class plgContentThemlercontent extends JPlugin
{
    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        if (JFactory::getApplication()->isAdmin())
            return;

        $templateName = $this->_getCurrentTemplate();
        if (file_exists(JPATH_THEMES . '/' . $templateName . '/library/Designer.php') &&
            !file_exists(JPATH_THEMES . '/' . $templateName . '/plugins/content.zip'))
            return;

        $isPreview = 'on' == JRequest::getVar('is_preview', '');
        if ($isPreview || ('' != JRequest::getVar('template', '') && $isPreview))
            return;

        $lib = JPATH_PLUGINS . '/content/themlercontent/lib';

        $doc = JFactory::getDocument();

        if (!isset($GLOBALS['themlerThemePluginFilesDeleted'])) {
            $jquery = $this->params->get('jquery', '0');
            if ($jquery == '1') {
                $doc->addScript(JURI::root(true) . '/plugins/content/themlercontent/lib/beforejq.js');
                $doc->addScript(JURI::root(true) . '/plugins/content/themlercontent/lib/jquery.js');
                $doc->addScript(JURI::root(true) . '/plugins/content/themlercontent/lib/afterjq.js');
            }
            $bootstrapjs = $this->params->get('bootstrapjs', '0');
            if ($bootstrapjs == '1') {
                $doc->addScript(JURI::root(true) . '/plugins/content/themlercontent/lib/bootstrap.min.js');
            }
            $bootstrapcss = $this->params->get('bootstrapcss', '0');
            if ($bootstrapcss == '1') {
                $doc->addStyleSheet(JURI::root(true) . '/plugins/content/themlercontent/lib/bootstrap.css');
            }
        }

        $GLOBALS['shortcodes_columns_24'] = $this->_isColumns24Theme();
        $scpath = $lib . '/Shortcodes.php';
        require_once $scpath;
        if (isset($row->text))
             $row->text = DesignerShortcodes::process($row->text);
         if (isset($row->introtext))
             $row->introtext = DesignerShortcodes::process($row->introtext);
         if (isset($row->fulltext))
             $row->fulltext = DesignerShortcodes::process($row->fulltext);
        return;
    }

    private function _getCurrentTemplate()
    {
        return strtolower(JFactory::getApplication()->getTemplate());
    }

    private function _isColumns24Theme()
    {
        $templateName = $this->_getCurrentTemplate();

        $bootstrap = JPATH_THEMES . '/' . $templateName . '/css/bootstrap.css';
        $bootstrapMin = JPATH_THEMES . '/' . $templateName . '/css/bootstrap.min.css';

        $content = '';
        if (file_exists($bootstrap))
            $content = file_get_contents($bootstrap);

        if ('' == $content && file_exists($bootstrapMin))
            $content = file_get_contents($bootstrapMin);

        if (preg_match('/col-(sm|md|lg|xs)-24/', $content))
            return true;
        else
            return false;
    }
}