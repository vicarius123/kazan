<?php
	defined('_JEXEC') or die;
	
	/**
		* Template for Joomla! CMS, created with Artisteer.
		* See readme.txt for more details on how to use the template.
	*/
	
	require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions.php';
	
	// Create alias for $this object reference:
	$document = $this;
	
	// Shortcut for template base url:
	$templateUrl = $document->baseurl . '/templates/' . $document->template;
	
	Artx::load("Artx_Page");
	
	// Initialize $view:
	$view = $this->artx = new ArtxPage($this);
	
	// Decorate component with Artisteer style:
	$view->componentWrapper();
	
	JHtml::_('behavior.framework', true);
	
?>
<!DOCTYPE html>
<html dir="ltr" lang="<?php echo $document->language; ?>">
	<head>
		<jdoc:include type="head" />
		<link rel="stylesheet" href="<?php echo $document->baseurl; ?>/templates/system/css/system.css" />
		<link rel="stylesheet" href="<?php echo $document->baseurl; ?>/templates/system/css/general.css" />
		
		<!--[if lt IE 9]><script src="https://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
		
		<script>if ('undefined' != typeof jQuery) document._artxJQueryBackup = jQuery;</script>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
		<script>jQuery.noConflict();</script>
		
		<script src="<?php echo $templateUrl; ?>/script.js"></script>
		<script src="<?php echo $templateUrl; ?>/modules.js"></script>
		<script src="<?php echo $templateUrl; ?>/jssor.js"></script>
		<script src="<?php echo $templateUrl; ?>/jssor.slider.js"></script>
		
		
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
		
		<!-- Optional theme -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap-theme.min.css" integrity="sha384-fLW2N01lMqjakBkx3l/M9EahuwpSfeNvV63J5ezn3uZzapT0u7EYsXMjQV+0En5r" crossorigin="anonymous">
		
		<!-- Latest compiled and minified JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
		
		<link rel="stylesheet" href="<?php echo $templateUrl; ?>/css/template.css" media="screen" type="text/css" />
		<link rel="stylesheet" href="<?php echo $templateUrl; ?>/css/custom.css" media="screen" type="text/css" />
		
		<?php $view->includeInlineScripts() ?>
		<script>if (document._artxJQueryBackup) jQuery = document._artxJQueryBackup;</script>
	</head>
	<body>
		
		<div id="main" class="">
			<div class="more_op" style="display: none;">
				<div class="more_inn">
					<div class="more_inn_txt">
						<p style=" margin: 0;">Версия сайта<br>для слабовидящих</p>
					</div>
					<div class="font_type">
						<span>ШРИФТ:</span>
						<a href="#" class="big">A</a>
						<a href="#" class="bigger">A</a>
						<a href="#" class="biggest">A</a>
					</div>
					<div class="color_site">
						<span>ЦВЕТ САЙТА:</span>
						<a href="#" class="white_c">A</a>
						<a href="#" class="black_c">A</a>
						<a href="#" class="blue_c">A</a>
					</div>
					<br clear="all">
					<a href="#" class="close_b">
						<img src="http://penza-gtk.ru/images/close_n.svg">
					</a>
				</div>
			</div>
			<div class="container-fluid sheet clearfix">
				<div class="clearfix">
					<?php echo $view->position('header_top', 'nostyle'); ?>
				</div>
				<header class="header"><?php echo $view->position('header', 'nostyle'); ?>
					
					<div class="nav_new">
						<?php if ($view->containsModules('user3', 'extra1', 'extra2')) : ?>
						
						
						<?php if ($view->containsModules('extra1')) : ?>
						<div class="hmenu-extra1"><?php echo $view->position('extra1'); ?></div>
						<?php endif; ?>
						<?php if ($view->containsModules('extra2')) : ?>
						<div class="hmenu-extra2"><?php echo $view->position('extra2'); ?></div>
						<?php endif; ?>
						<?php echo $view->position('user3'); ?>
						
						
						<?php endif; ?>
					</div>
                    
				</header>
				<?php echo $view->position('banner1', 'nostyle'); ?>
				<?php if ($view->containsModules('left')) : ?>
				<div class="left_block col-xs-3" style="    padding-left: 0;">
					<?php echo $view->position('left', 'nostyle'); ?>
				</div>
				<? endif; ?>
				<?php echo $view->positions(array('top1' => 33, 'top2' => 33, 'top3' => 34), 'block'); ?>
				<div class="layout-wrapper <?php if ($view->containsModules('left')) : ?>col-xs-9<?endif;?>">
					<div class="content-layout">
						<div class="content-layout-row">
							<div class="layout-cell content">
								<?php
									echo $view->position('banner2', 'nostyle');
									if ($view->containsModules('breadcrumb'))
									echo artxPost($view->position('breadcrumb'));
									echo $view->positions(array('user1' => 50, 'user2' => 50), 'article');
									echo $view->position('banner3', 'nostyle');
									echo artxPost(array('content' => '<jdoc:include type="message" />', 'classes' => ' messages'));
									echo '<jdoc:include type="component" />';
									echo $view->position('banner4', 'nostyle');
									echo $view->positions(array('user4' => 50, 'user5' => 50), 'article');
									echo $view->position('banner5', 'nostyle');
								?>
								
								
								
							</div>
						</div>
					</div>
				</div>
				<br clear="all"/>
				<?php echo $view->positions(array('bottom1' => 33, 'bottom2' => 33, 'bottom3' => 34), 'block'); ?>
				<?php echo $view->position('banner6', 'nostyle'); ?>
				
				<footer class="footer">
					<?php if ($view->containsModules('copyright_left')) : ?>
					<div class="col-xs-3">
						<?php echo $view->position('copyright_left', 'nostyle'); ?>
					</div>
					<?php endif; ?>
					
					<?php if ($view->containsModules('copyright_right')) : ?>
					<div class="col-xs-9">
						<?php echo $view->position('copyright_right', 'nostyle'); ?>
					</div>
					<?php endif; ?>
					<br clear="all"/>
				</footer>
				
			</div>
		</div>
		<?php echo $view->position('debug'); ?>
	</body>
</html>	