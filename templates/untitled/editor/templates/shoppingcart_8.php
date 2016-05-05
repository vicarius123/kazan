<!DOCTYPE html>
<html dir="ltr">
<head>
	<meta charset="utf-8" />
    <?php
        $base = $document->getBase();
        if (!empty($base)) {
            echo '<base href="' . $base . '" />';
            $document->setBase('');
        }
    ?>
    
    <script>
    var themeHasJQuery = !!window.jQuery;
</script>
<script src="<?php echo addThemeVersion($document->templateUrl . '/jquery.js'); ?>"></script>
<script>
    window._$ = jQuery.noConflict(themeHasJQuery);
</script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="<?php echo addThemeVersion($document->templateUrl . '/bootstrap.min.js'); ?>"></script>
<script src="<?php echo addThemeVersion($document->templateUrl . '/CloudZoom.js'); ?>" type="text/javascript"></script>
    
    <?php echo $document->head; ?>
    <?php if ($GLOBALS['theme_settings']['is_preview'] || !file_exists($themeDir . '/css/bootstrap.min.css')) : ?>
    <link rel="stylesheet" href="<?php echo addThemeVersion($document->templateUrl . '/css/bootstrap.css'); ?>" media="screen" />
    <?php else : ?>
    <link rel="stylesheet" href="<?php echo addThemeVersion($document->templateUrl . '/css/bootstrap.min.css'); ?>" media="screen" />
    <?php endif; ?>
    <?php if ($GLOBALS['theme_settings']['is_preview'] || !file_exists($themeDir . '/css/template.min.css')) : ?>
    <link rel="stylesheet" href="<?php echo addThemeVersion($document->templateUrl . '/css/template.css'); ?>" media="screen" />
    <?php else : ?>
    <link rel="stylesheet" href="<?php echo addThemeVersion($document->templateUrl . '/css/template.min.css'); ?>" media="screen" />
    <?php endif; ?>
    <!--[if lte IE 9]>
    <link rel="stylesheet" href="<?php echo addThemeVersion($document->templateUrl . '/css/template.ie.css'); ?>" media="screen"/>
    <![endif]-->
    <?php if(('edit' == JRequest::getVar('layout') && 'form' == JRequest::getVar('view')) ||
        ('com_config' == JRequest::getVar('option') && 'config.display.modules' == JRequest::getVar('controller'))) : ?>
    <link rel="stylesheet" href="<?php echo addThemeVersion($document->templateUrl . '/css/media.css'); ?>" media="screen" />
    <script src="<?php echo addThemeVersion($document->templateUrl . '/js/template.js'); ?>"></script>
    <?php endif; ?>
    <script src="<?php echo addThemeVersion($document->templateUrl . '/script.js'); ?>"></script>
    <!--[if lte IE 9]>
    <script src="<?php echo addThemeVersion($document->templateUrl . '/script.ie.js'); ?>"></script>
    <![endif]-->
    
</head>
<body class="data-control-id-37 bootstrap bd-body-8 bd-pagebackground">
    <header class="data-control-id-751822 bd-headerarea-1">
        <div class="bd-containereffect-1 container data-control-id-1256035"><section class="data-control-id-1256033 bd-section-3 bd-tagstyles " id="section3" data-section-title="">
    <div class="bd-section-inner">
        <div class="bd-section-align-wrapper">
            <div class="data-control-id-1255877 bd-layoutbox-3 bd-background-width  clearfix">
    <div class="bd-container-inner">
        <?php
$app = JFactory::getApplication();
$themeParams = $app->getTemplate(true)->params;
$sitename = $app->getCfg('sitename');
$logoSrc = '';
ob_start();
?>
src="<?php echo JURI::base() . 'templates/' . JFactory::getApplication()->getTemplate(); ?>/images/designer/9bc0ae1e2fbd611db598b2079af7511c_logo.png"
<?php

$logoSrc = ob_get_clean();
$logoLink = '';

if ($themeParams->get('logoFile'))
    $logoSrc = 'src="' . JURI::root() . $themeParams->get('logoFile') . '"';

if ($themeParams->get('logoLink'))
    $logoLink = $themeParams->get('logoLink');

if (!$logoLink)
    $logoLink = JUri::base(true);

?>
<a class="data-control-id-1255886 bd-logo-2" href="<?php echo $logoLink; ?>">
<img class="data-control-id-1255884 bd-imagestyles" <?php echo $logoSrc; ?> alt="<?php echo $sitename; ?>">
</a>
	
		<?php
    renderTemplateFromIncludes('hmenu_1', array());
?>
    </div>
</div>
        </div>
    </div>
</section></div>
</header>
	
		<?php 
    renderTemplateFromIncludes('breadcrumbs_1');
?>
	
		<div class="bd-containereffect-12 container data-control-id-49435">
<div class="bd-contentlayout-8  bd-sheetstyles data-control-id-385">
    <div class="bd-container-inner">

        
        <div class="bd-flex-vertical bd-stretch-inner">
            
            <div class="bd-flex-horizontal bd-flex-wide">
                
 <?php renderTemplateFromIncludes('sidebar_area_3'); ?>
                <div class="bd-flex-vertical bd-flex-wide">
                    

                    <div class="data-control-id-946565 bd-layoutitemsbox-26 bd-flex-wide">
    <div class="data-control-id-876 bd-content-8">
    <div class="bd-container-inner">
        <?php
            $document = JFactory::getDocument();
            echo $document->view->renderSystemMessages();
            $document->view->componentWrapper('common');
            echo '<jdoc:include type="component" />';
        ?>
    </div>
</div>
</div>

        
                    
                </div>
                
            </div>
            
        </div>

    </div>
</div></div>
	
		<footer class="data-control-id-751829 bd-footerarea-1">
        <section class="data-control-id-1247009 bd-section-2 bd-tagstyles" id="section2" data-section-title="">
    <div class="bd-section-inner">
        <div class="bd-section-align-wrapper">
            <div class="bd-containereffect-6 container data-control-id-1247209"><div class="data-control-id-2241 bd-layoutcontainer-28  bd-columns
    
    ">
    <div class="bd-container-inner">
        <div class="container-fluid">
            <div class="row">
                <div class="data-control-id-2233 bd-columnwrapper-60 
 col-md-3
 col-sm-6
 col-xs-12">
    <div class="bd-layoutcolumn-60 bd-column" ><div class="bd-vertical-align-wrapper"><?php
    renderTemplateFromIncludes('joomlaposition_2');
?></div></div>
</div>
	
		<div class="data-control-id-2235 bd-columnwrapper-61 
 col-md-3
 col-sm-6
 col-xs-12">
    <div class="bd-layoutcolumn-61 bd-column" ><div class="bd-vertical-align-wrapper"><?php
    renderTemplateFromIncludes('joomlaposition_3');
?></div></div>
</div>
	
		<div class="data-control-id-2237 bd-columnwrapper-62 
 col-md-3
 col-sm-6
 col-xs-12">
    <div class="bd-layoutcolumn-62 bd-column" ><div class="bd-vertical-align-wrapper"><?php
    renderTemplateFromIncludes('joomlaposition_4');
?></div></div>
</div>
	
		<div class="data-control-id-2239 bd-columnwrapper-63 
 col-md-3
 col-sm-6
 col-xs-12">
    <div class="bd-layoutcolumn-63 bd-column" ><div class="bd-vertical-align-wrapper"><?php
    renderTemplateFromIncludes('joomlaposition_5');
?></div></div>
</div>
            </div>
        </div>
    </div>
</div></div>
	
		<div class="bd-containereffect-7 container data-control-id-1247224">
<div class="data-control-id-2248 bd-pagefooter-1 ">
    <div class="bd-container-inner">
        <a href="http://www.billionthemes.com/joomla_templates" target="_blank">Joomla Template</a> created with <a href ='http://www.themler.com' target="_blank">Themler</a>.
    </div>
</div>
</div>
        </div>
    </div>
</section>
</footer>
	
		<div data-smooth-scroll data-animation-time="250" class="data-control-id-491381 bd-smoothscroll-3"><a href="#" class="data-control-id-2256 bd-backtotop-1 ">
    <span class="bd-icon-66 bd-icon data-control-id-2255"></span>
</a></div>
</body>
</html>