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

JHtml::_('behavior.caption');
?>
<div class="category-list<?php echo $this->pageclass_sfx;?>">

<?php
	echo '<p>'.$this->pageclass_sfx.'</p>';
if($this->pageclass_sfx == 'all_news'){
	$this->subtemplatename = 'all_news';
	echo JLayoutHelper::render('joomla.content.category_default', $this);
}else{
	$this->subtemplatename = 'articles';
	echo JLayoutHelper::render('joomla.content.category_default', $this);
}
?>

</div>
