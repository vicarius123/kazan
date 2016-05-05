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
<div class="row newspage" onclick="location.href='<?=JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));?>'">
	<div class="col-xs-12">
		<em><?=$date->format('d').' '.$months[($date->format('n'))].' '.$date->format('Y');?></em>
	</div>
	<div class="col-xs-4">
		<img src="<?=$images;?>"/>
	</div>
	
	<div class="col-xs-8">
		<b><?=$item->title;?></b>
		<?=$item->introtext;?>
	</div>	
</div>
<? endforeach;?>
</div>
<br clear="all"/>