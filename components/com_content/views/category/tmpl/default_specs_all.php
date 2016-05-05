<?
	/**
		* @package     Joomla.Site
		* @subpackage  com_content
		*
		* @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
		* @license     GNU General Public License version 2 or later; see LICENSE.txt
	*/
	
	defined('_JEXEC') or die;
	
	JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
	
	// Create some shortcuts.
	$params    = &$this->item->params;
	
	
	$months = array(
    1 => 'Января', 2 => 'Февраля', 3 => 'Марта', 4 => 'Апреля',
    5 => 'Мая', 6 => 'Июня', 7 => 'Июля', 8 => 'Августа',
    9 => 'Сентября', 10 => 'Октября', 11 => 'Ноября', 12 => 'Декабря'
	);
	
	$items = $this->items;
	
?>
<br clear="all"/>
<div style="margin-top:-20px">
<?	foreach($items as $key=>$item):
	$date = new DateTime($item->created);
	$images = json_decode($item->images)->image_intro;
?>

<div class="row" onclick="location.href='<?=JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));?>'">
<?=$item->introtext;?>
</div>
<? endforeach;?>
</div>
<?
	jimport( 'joomla.application.module.helper' );
	$module = JModuleHelper::getModule( 'mod_custom','СРОК ПОДАЧИ ДОКУМЕНТОВ' );
	$attribs['style'] = 'xhtml';
	echo JModuleHelper::renderModule( $module, $attribs );
	
	$module = JModuleHelper::getModule( 'mod_custom','Заявка  на поступление - art' );
	$attribs['style'] = 'xhtml';
	echo JModuleHelper::renderModule( $module, $attribs );
	
	?>
<br clear="all"/>