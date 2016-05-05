<?php

function outPutTemplates($error) {
  	require_once dirname(__DIR__) . '/classes/Config.php';

    header('Content-Type: application/javascript');
    header("Pragma: no-cache");

    $templates = Config::getThemeTemplates();

  	if (null !== $error) {
  		echo 'config.error = ' . json_encode($error) . ';';
    }
    echo 'config.infoData.startPage = "' . $templates['home'] . '";';
    echo 'config.infoData.templates = ' . json_encode($templates['templates']) . ';';
}

function shutdownFunc()
{
  	outPutTemplates(error_get_last());
}

register_shutdown_function("shutdownFunc");

define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);

define('JPATH_BASE', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
require_once JPATH_BASE . DS . 'includes' . DS . 'defines.php';
require_once JPATH_BASE . DS . 'includes' . DS . 'framework.php';

$app = JFactory::getApplication('site');

$language = JFactory::getLanguage();
if (version_compare(JVERSION, '3.0', '>')) {
    $app->loadLanguage($language);
}
JFactory::$language = $app->getLanguage();

JPluginHelper::importPlugin('system');
$app->triggerEvent('onAfterInitialise');

?>