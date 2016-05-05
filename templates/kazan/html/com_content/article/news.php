<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_content
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT . '/helpers');

// Create shortcuts to some parameters.
$params  = $this->item->params;
$images  = json_decode($this->item->images);
$urls    = json_decode($this->item->urls);
$canEdit = $params->get('access-edit');
$user    = JFactory::getUser();
$info    = $params->get('info_block_position', 0);
JHtml::_('behavior.caption');
$item = $this->item;
$images = json_decode($item->images)->image_intro;
$months = array(
    1 => 'Января', 2 => 'Февраля', 3 => 'Марта', 4 => 'Апреля',
    5 => 'Мая', 6 => 'Июня', 7 => 'Июля', 8 => 'Августа',
    9 => 'Сентября', 10 => 'Октября', 11 => 'Ноября', 12 => 'Декабря'
);
$date = new DateTime($item->created);
?>
<div class="newspage news_inn item-page<?php echo $this->pageclass_sfx; ?>" itemscope itemtype="https://schema.org/Article">
	<meta itemprop="inLanguage" content="<?php echo ($this->item->language === '*') ? JFactory::getConfig()->get('language') : $this->item->language; ?>" />
	<?php if ($this->params->get('show_page_heading')) : ?>

		<h1> <?php echo $this->escape($this->params->get('page_heading')); ?> </h1>

	<? endif;?>
	<em><?=$date->format('d').' '.$months[($date->format('n'))].' '.$date->format('Y');?></em>
	<strong><?=$item->title;?></strong>
	<img src="<?=$images;?>"/>
	<?=$item->fulltext;?>
	
	<br>
	<h3 class="news_h">НОВОСТИ</h3>
	<?
	jimport( 'joomla.application.module.helper' );
	$module = JModuleHelper::getModule( 'mod_articles_news','НОВОСТИ inn' );
	$attribs['style'] = 'xhtml';
	echo JModuleHelper::renderModule( $module, $attribs );
	
	?>
</div>
