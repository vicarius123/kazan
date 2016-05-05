<?php
/**
 * Joomla! System plugin - jQuery Fancybox
 *
 * @author Yireo (info@yireo.com)
 * @copyright Copyright 2015 Yireo.com. All rights reserved
 * @license GNU Public License
 * @link http://www.yireo.com
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

?>
jQuery.noConflict();
jQuery(document).ready(function() {
<?php foreach($elements as $element) : ?>
jQuery("<?php echo $element; ?>").fancybox({<?php echo implode(', ', $options); ?>});
<?php endforeach; ?>
});
