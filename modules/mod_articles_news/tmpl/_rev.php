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

?>
<div class="col-xs-3">
	<img src="<?=$images;?>"> 
</div>
<div class=" col-xs-9">
<?php echo $item->introtext; ?>
</div>

