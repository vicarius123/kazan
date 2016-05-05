<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_articles_news
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

$item_heading = $params->get('item_heading', 'h4');

$images = json_decode($item->images)->image_intro;
$months = array(
    1 => 'Января', 2 => 'Февраля', 3 => 'Марта', 4 => 'Апреля',
    5 => 'Мая', 6 => 'Июня', 7 => 'Июля', 8 => 'Августа',
    9 => 'Сентября', 10 => 'Октября', 11 => 'Ноября', 12 => 'Декабря'
);
$date = new DateTime($item->created);
$id = (int)JRequest::getVar('id');
?>

<div class="" onclick="location.href='<?=JRoute::_(ContentHelperRoute::getArticleRoute($item->slug, $item->catid, $item->language));?>'">
	<img src="<?=$images;?>"> 
	<a href="#">
		<strong><?=$date->format('d').' '.$months[($date->format('n'))].' '.$date->format('Y');?></strong>
	</a>
	<p>
		<?=$item->introtext;?>
	</p>
</div>



