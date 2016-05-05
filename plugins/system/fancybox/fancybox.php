<?php
/**
 * Joomla! System plugin - jQuery Fancybox
 *
 * @author    Yireo (info@yireo.com)
 * @copyright Copyright 2015 Yireo.com. All rights reserved
 * @license   GNU Public License
 * @link      http://www.yireo.com
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Import the parent class
jimport('joomla.plugin.plugin');

/**
 * Fancybox System Plugin
 */
class plgSystemFancybox extends JPlugin
{
	/**
	 * @var JApplication
	 */
	protected $app;

	/**
	 * Event onAfterRender
	 */
	public function onAfterDispatch()
	{
		// Dot not load if this is not the right document-class
		$document = JFactory::getDocument();
		
		if ($document->getType() != 'html')
		{
			return false;
		}

		// Perform actions on the frontend
		if ($this->app->isSite())
		{
			$elements = $this->getElements();
			
			if (empty($elements))
			{
				return false;
			}

			// Get and parse the components from the plugin parameters
			$components = $this->params->get('exclude_components');
			
			if (empty($components))
			{
				$components = array();
			}
			elseif (!is_array($components))
			{
				$components = array($components);
			}

			// Don't do anything if the current component is excluded
			if (in_array($this->app->input->getCmd('option'), $components))
			{
				return false;
			}

			$js_folder = 'media/plg_fancybox/js/';
			$transition = $this->params->get('transition', '');

			$this->loadStylesheet('jquery.fancybox.css', $this->params->get('load_css', 1));
			$this->jquery();

			// Load CSS and JavaScript
			$this->loadStylesheet('jquery.fancybox-buttons.css', $this->params->get('load_buttons', 0));
			$this->loadStylesheet('jquery.fancybox-thumbs.css', $this->params->get('load_thumbs', 0));
			$this->loadScript('jquery.fancybox.pack.js', $this->params->get('load_fancybox', 1));
			$this->loadScript('jquery.mousewheel-3.0.6.pack.js', $this->params->get('load_mousewheel', 0));
			$this->loadScript('jquery.fancybox-buttons.js', $this->params->get('load_buttons', 0));
			$this->loadScript('jquery.fancybox-media.js', $this->params->get('load_media', 0));
			$this->loadScript('jquery.fancybox-thumbs.js', $this->params->get('load_thumbs', 0));

			// Construct basic options
			$options = array();

			// Enable mouse-wheel
			$options['mouseWheel'] = true;
			
			if ($this->params->get('enable_mousewheel', 0) == 0)
			{
				$options['mouseWheel'] = false;
			}

			// Determine the content-type
			$content_type = $this->params->get('content_type');
			
			if (!empty($content_type))
			{
				$options['type'] = $content_type;
			}

			if (!in_array($transition, array('', 'fade', 'elastic', 'none')))
			{
				$this->loadScript('jquery.easing-1.3.pack.js', $this->params->get('load_easing', 1));

				if (in_array($transition, array('swing', 'linear')))
				{
					$options['openEasing'] = $transition;
					$options['closeEasing'] = $transition;
				}
				else
				{
					$options['openEasing'] = 'easeInOut' . ucfirst($transition);
					$options['closeEasing'] = 'easeInOut' . ucfirst($transition);
				}

				$options['openSpeed'] = $this->params->get('speed', 200);
				$options['closeSpeed'] = $this->params->get('speed', 200);
				$options['nextSpeed'] = $this->params->get('speed', 200);
				$options['prevSpeed'] = $this->params->get('speed', 200);

			}
			else
			{
				$options['openEffect'] = $transition;
				$options['closeEffect'] = $transition;
				$options['nextEffect'] = $transition;
				$options['prevEffect'] = $transition;
				$options['openSpeed'] = $this->params->get('speed', 200);
				$options['closeSpeed'] = $this->params->get('speed', 200);
				$options['nextSpeed'] = $this->params->get('speed', 200);
				$options['prevSpeed'] = $this->params->get('speed', 200);
			}

			// Load the extra options
			$extraOptions = trim($this->params->get('options'));

			if (!empty($extraOptions))
			{
				$extraOptions = explode("\n", $extraOptions);
				
				foreach ($extraOptions as $extraOption)
				{
					$extraOption = explode('=', $extraOption);

					if (!empty($extraOption[0]) && !empty($extraOption[1]))
					{
						$options[$extraOption[0]] = trim($extraOption[1]);
					}
				}
			}

			// Sanitize the options
			foreach ($options as $name => $value)
			{
				if (is_bool($value))
				{
					$bool = ($value) ? "true" : "false";
					$options[$name] = "'$name':$bool";
				}
				elseif (is_numeric($value))
				{
					$options[$name] = "'$name':$value";
				}
				elseif (empty($value))
				{
					unset($options[$name]);
				}
				else
				{
					if ($value != 'true' && $value != 'false')
					{
						$value = "'$value'";
					}
					elseif ($value == "'true'")
					{
						$value = 'true';
					}
					elseif ($value == "'false'")
					{
						$value = 'false';
					}
					
					$options[$name] = "'$name':$value";
				}
			}

			// Helper options
			$helpers = array();

			// Overlay helper
			$closeClick = (bool) $this->params->get('hide_on_click', true);
			$closeClick = ($closeClick) ? 'true' : 'false';
			$helpers[] = 'overlay: {closeClick:' . $closeClick . '}';

			// Buttons helper
			if ($this->params->get('load_buttons', 0) == 1)
			{
				$options[] = 'closeBtn: false';
				$helpers[] = 'buttons: {}';
			}

			// Media helper
			if ($this->params->get('load_media', 0))
			{
				$helpers[] = 'media: {}';
			}

			// Thumbs helper
			if ($this->params->get('load_thumbs', 0))
			{
				$helpers[] = 'thumbs: {width:50, height:50}';
			}

			$options[] = 'helpers: {' . implode(', ', $helpers) . '}';

			// Get the script-output
			$variables = array('elements' => $elements, 'options' => $options,);
			$script = $this->loadTemplate('script.php', $variables);

			// Add the script-declaration
			$document->addScriptDeclaration($script);

		}
	}

	/**
	 * Load a template
	 *
	 * @param string $file
	 * @param array $variables
	 */
	private function loadTemplate($file = null, $variables = array())
	{
		// Base file
		$templateFile = JPATH_SITE . '/plugins/system/fancybox/tmpl/' . $file;

		// Check for overrides
		$template = JFactory::getApplication()->getTemplate();

		if (file_exists(JPATH_SITE . '/templates/' . $template . '/html/plg_fancybox/' . $file))
		{
			$templateFile = JPATH_SITE . '/templates/' . $template . '/html/plg_fancybox/' . $file;
		}

		$output = null;

		// Include the variables here
		if (!empty($variables))
		{
			foreach ($variables as $name => $value)
			{
				$$name = $value;
			}
		}

		// Unset so as not to introduce into template scope
		unset($file);

		// Never allow a 'this' property
		if (isset($this->this))
		{
			unset($this->this);
		}

		// Unset variables
		unset($variables);
		unset($name);
		unset($value);

		// Start capturing output into a buffer
		ob_start();
		include $templateFile;

		// Done with the requested template; get the buffer and clear it.
		$output = ob_get_contents();
		ob_end_clean();

		$output = str_replace("\n", "", $output);

		return $output;
	}

	/**
	 * Load a script
	 *
	 * @param string $file
	 * @param bool $condition
	 */
	private function loadScript($file = null, $condition = true)
	{
		$condition = (bool) $condition;
		
		if ($condition == true)
		{

			if (preg_match('/^jquery-([0-9\.]+).min.js$/', $file, $match) && $this->params->get('use_google_api', 0) == 1)
			{

				if (JURI::getInstance()->isSSL() == true)
				{
					$script = 'https://ajax.googleapis.com/ajax/libs/jquery/' . $match[1] . '/jquery.min.js';
				}
				else
				{
					$script = 'http://ajax.googleapis.com/ajax/libs/jquery/' . $match[1] . '/jquery.min.js';
				}

				JFactory::getDocument()->addScript($script);

				return;
			}

			$folder = 'media/plg_fancybox/js/';

			// Check for overrides
			$template = JFactory::getApplication()->getTemplate();
			
			if (file_exists(JPATH_SITE . '/templates/' . $template . '/html/plg_fancybox/js/' . $file))
			{
				$folder = 'templates/' . $template . '/html/plg_fancybox/js/';
			}

			JFactory::getDocument()->addScript($folder . $file);
		}
	}

	/**
	 * Load a stylesheet
	 *
	 * @param string $file
	 * @param bool $condition
	 */
	private function loadStylesheet($file = null, $condition = true)
	{
		$condition = (bool) $condition;
		
		if ($condition == true)
		{

			$folder = 'media/plg_fancybox/css/';

			// Check for overrides
			$template = JFactory::getApplication()->getTemplate();
			
			if (file_exists(JPATH_SITE . '/templates/' . $template . '/html/plg_fancybox/css/' . $file))
			{
				$folder = 'templates/' . $template . '/html/plg_fancybox/css/';
			}

			JFactory::getDocument()->addStylesheet($folder . $file);
		}
	}

	/**
	 * Get the HTML elements
	 *
	 * @return array
	 */
	private function getElements()
	{
		$elements = $this->params->get('elements');
		$elements = trim($elements);
		$elements = explode(",", $elements);
		
		if (!empty($elements))
		{
			foreach ($elements as $index => $element)
			{
				$element = trim($element);
				$element = preg_replace('/([^a-zA-Z0-9\[\]\=\-\_\.\#\ ]+)/', '', $element);
				if (empty($element))
				{
					unset($elements[$index]);
				}
				else
				{
					$elements[$index] = $element;
				}
			}
		}

		return $elements;
	}

	/**
	 * Simple method to load jQuery
	 */
	private function jquery()
	{
		JLoader::import('joomla.version');
		$version = new JVersion();
		
		if (version_compare($version->RELEASE, '2.5', '<='))
		{
			if (JFactory::getApplication()->get('jquery') == false)
			{
				$this->loadScript('jquery-1.9.0.min.js', $this->params->get('load_jquery', 1));
				JFactory::getApplication()->set('jquery', true);
			}
		}
		else
		{
			JHtml::_('jquery.framework');
		}
	}
}

