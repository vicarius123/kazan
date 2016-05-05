<?php

defined('_JEXEC') or die;

class ShortcodesUtility
{
    public static function atts($defaults, $atts, $additional = array('')) {
        foreach($additional as $prefix) {
            foreach(array('', '_md', '_sm', '_xs') as $mode) {
                $defaults[$prefix . 'css' . $mode] = '';
                $defaults[$prefix . 'typography' . $mode] = '';
            }
            $defaults[$prefix . 'hide'] = '';
        }

        $atts = (array)$atts;
        $ret = array();
        foreach($defaults as $name => $default) {
            if (array_key_exists($name, $atts))
                $ret[$name] = $atts[$name];
            else
                $ret[$name] = $default;
        }
        return $ret;
    }

    public static function getBool($value, $default_value = false) {
        if ($value === true || $value === '1' || $value === 'true' || $value === 'yes')
            return true;
        if ($value === false || $value === '0' || $value === 'false' || $value === 'no')
            return false;
        return $default_value;
    }

    public static function escape($text) {
        return $text;
    }

    public static function doShortcode($content, $enable_shortcodes = true, $allow_paragraphs = false) {
        if($enable_shortcodes)
            return DesignerShortcodes::buildShortCodes($content);
        else
            return $content;
    }

    public static function addShortcode($tag, $func) {
        DesignerShortcodes::$shortcodes[$tag] = $func;
    }

    private static $_styles = array();

    public static function addResult($content, $styles = '') {
        return '<!--style-->' .  $styles . '<!--/style-->' . $content;
    }

    public static function _resultCollectStylesCallback($matches) {
        self::$_styles[] = $matches[1];
        return '';
    }

    public static function processResult($content) {
        self::$_styles = array();
        $content = preg_replace_callback('#<!--style-->([\s\S]*?)<!--\/style-->#', 'ShortcodesUtility::_resultCollectStylesCallback', $content);
        return array($content, join("\n", self::$_styles));
    }
}

class ShortcodesEffects
{
    const CONTROL = 0;
    const HTML_EFFECT = 1;
    const CSS_EFFECT = 2;

    private static $_stack = array();

    public static $responsive_rules = array(
        '' => array('', ''),
        '_md' => array('@media (max-width: 1199px) {', '}'),
        '_sm' => array('@media (max-width: 991px) {', '}'),
        '_xs' => array('@media (max-width: 767px) {', '}')
    );

    public static $responsive_rules_min = array(
        '' => array('', ''),
        '_md' => array('@media (min-width: 1200px) {', '}'),
        '_sm' => array('@media (min-width: 992px) {', '}'),
        '_xs' => array('@media (min-width: 768px) {', '}')
    );

    public static function getResponsiveModes() {
        return array_keys(self::$responsive_rules);
    }

    // todo: fill from themler.CONST
    private static $_effects_defaults = array(
        'positioning' => array(
            'position' => 'relative',
            'left'     => 'auto',
            'right'    => 'auto',
            'top'      => 'auto',
            'bottom'   => 'auto',
            'float'    => 'none',
            'width'    => '100%',
            'height'   => '100%',
            'z-index'  => 'auto',
            'margin-left'   => '0',
            'margin-right'  => '0',
            'margin-top'    => '0',
            'margin-bottom' => '0',
            'margin' => '0',
            'display' => 'inline'
        ),
        'transform' => array(
            'transform' => 'rotate(0deg)'
        )
    );

    private static $_skip_attributes = array(
        'positioning' => array(
            'display' => array('inline-block', 'block')
        )
    );


    private static $_css_groups = array(
        'background' => array(
            'background-clip', 'background-origin', 'background-size',
            'background-attachment', 'background-color', 'background-image',
            'background-repeat', 'background-position'
        ),
        'border' => array(
            'border-top-width', 'border-top-style', 'border-top-color',
            'border-right-width', 'border-right-style', 'border-right-color',
            'border-bottom-width', 'border-bottom-style', 'border-bottom-color',
            'border-left-width', 'border-left-style', 'border-left-color'
        ),
        'border-radius' => array(
            'border-top-left-radius', 'border-top-right-radius',
            'border-bottom-right-radius', 'border-bottom-left-radius'
        ),
        'margin' => array('margin-top', 'margin-right', 'margin-bottom', 'margin-left'),
        'padding' => array('padding-top', 'padding-right', 'padding-bottom', 'padding-left'),
        'size' => array('width', 'min-width', 'max-width', 'height', 'min-height', 'max-height'),
        'overflow' => array('overflow-x', 'overflow-y'),
        'transform' => array('transform'),
        'transition' => array('transition', '-webkit-transition'),
        'positioning' => array(
            'top', 'left', 'right', 'bottom',
            'clear', 'clip', 'cursor', 'display',
            'position', 'visibility', 'z-index'
        )
    );

    private static $_tags_styles_types = array(
        // name => type_category
        "blockquotes" => "blockquotes",
        "list" => "BulletList",
        "button" => "Button",
        "image" => "Image",
        "table" => "Table",
        "input" => "inputs",
        "ordered" => "OrderedList"
    );

    public static function tagsStylesAtts($atts, $prefixes = array('')) {

        foreach($prefixes as $prefix) {
            $atts["{$prefix}tags_styles"] = true;
            foreach (self::$_tags_styles_types as $type => $type_category) {
                $atts["{$prefix}tag_{$type}_style"] = '';
            }
            $atts["{$prefix}tag_button_type"] = '';
            $atts["{$prefix}tag_button_style"] = '';
            $atts["{$prefix}tag_button_size"] = '';

            $atts["{$prefix}tag_image_type"] = '';
            $atts["{$prefix}tag_image_shape"] = '';
            $atts["{$prefix}tag_image_responsive"] = '';

            $atts["{$prefix}tag_table_type"] = '';
            $atts["{$prefix}tag_table_striped"] = '';
            $atts["{$prefix}tag_table_bordered"] = '';
            $atts["{$prefix}tag_table_hover"] = '';
            $atts["{$prefix}tag_table_condensed"] = '';
            $atts["{$prefix}tag_table_responsive"] = '';
        }
        return $atts;
    }

    private static function _cssGroups($prop) {
        $groups = array();
        $groups[] = $prop;

        if (array_key_exists($prop, self::$_css_groups)) {
            $groups = array_merge($groups, self::$_css_groups[$prop]);
        } else {
            $groups = array_merge($groups, array($prop));
        }

        return $groups;
    }

    public static function css_prop($atts, $prop, $mode = '', $prefix = '') {
        $atts = self::filter($atts, $prop, $prefix);
        $css = isset($atts[$prefix . "css$mode"]) ? $atts[$prefix . "css$mode"] : '';
        if (false !== ($pos = strpos($css, ':'))) {
            $css = trim(substr($css, $pos + 1));
            $css = preg_replace('#;$#', '', $css);
        }
        return $css;
    }

    public static function filter($atts, $filter, $prefix = '') {
        $filters = array_map('trim', explode(',', $filter));

        $excludes = array();
        $includes = array();

        foreach($filters as $filter) {
            if (!$filter)
                continue;
            if ($filter[0] === '!') {
                $excludes = array_merge(
                    $excludes,
                    self::_cssGroups(substr($filter, 1))
                );
            } else {
                $includes = array_merge(
                    $includes,
                    self::_cssGroups($filter)
                );
            }
        }

        $excludeRE = count($excludes) ? '#^(' . join('|', $excludes) . ')\s*:#' : '';
        $includeRE = count($includes) ? '#^(' . join('|', $includes) . ')\s*:#' : '';

        foreach(array('css', 'css_md', 'css_sm', 'css_xs') as $css_mode) {
            $css_mode = $prefix . $css_mode;
            if (empty($atts[$css_mode])) continue;

            preg_match_all ('#(?:[\w\-]+):(?:url\([^\)]+\)|[^;]+?)+#', $atts[$css_mode], $rules);
            $rules = array_map('trim', $rules[0]);

            if ($includeRE) {
                $rules = preg_grep($includeRE, $rules);
            }

            if ($excludeRE) {
                $rules = preg_grep($excludeRE, $rules, PREG_GREP_INVERT);
            }

            $rules = array_filter(array_map('trim', $rules));

            if ($rules)
                $atts[$css_mode] = join(';', $rules) . ';';
            else
                $atts[$css_mode] = '';
        }

        return $atts;
    }

    private static function _effectCssCurrent($rules = '', $groups, $skipProps) {
        $result = array();
        $rules = explode(';', $rules);

        foreach ($groups as $group) {
            $defaultCss = self::$_effects_defaults[$group];

            foreach ($rules as $rule) {
                if (!trim($rule)) continue;
                $ruleParts = explode(':', $rule, 2);
                if (count($ruleParts) !== 2) continue;

                list ($prop, $value) = $ruleParts;
                $prop = trim($prop);
                if (!in_array($prop, $skipProps) && isset($defaultCss[$prop])) {
                    $result[] = $prop . ':' . $value;
                }
            }
        }

        return join(';', $result);
    }

    private static function _effectCssDefaults($rules = '', $groups, $skipProps) {
        $result = array();
        $rules = explode(';', $rules);

        foreach ($groups as $group) {
            $defaultCss = self::$_effects_defaults[$group];

            foreach ($rules as $rule) {
                if (!trim($rule)) continue;
                $ruleParts = explode(':', $rule, 2);
                if (count($ruleParts) !== 2) continue;

                list ($prop, ) = $ruleParts;
                $prop = trim($prop);
                if (!in_array($prop, $skipProps) && isset($defaultCss[$prop])) {
                    $result[] = $prop . ':' . $defaultCss[$prop];
                }
            }
        }

        return join(';', $result);
    }

    public static function init($name, $atts, $type = ShortcodesEffects::CONTROL) {
        $id = rand();
        $css = array();
        $className = ($name ? $name : 'additional-class') . '-' . $id;

        foreach (array('css', 'css_md', 'css_sm', 'css_xs') as $key) {
            if (empty($atts[$key])) {
                $css[$key] = '';
            } else {
                $css[$key] = $atts[$key];
            }
        }

        self::$_stack[] = array(
            'id' => $id,
            'css' => $css,
            'className' => $className,
            'selector' => '.' . $className,
            'type' => is_array($type) ? $type['type'] : $type,
            'supportTransform' => is_array($type) ? $type['supportTransform'] : true,
            'atts' => $atts
        );

        return $id;
    }

    private static function _stackItem($id) {
        for ($i = 0, $length = count(self::$_stack); $i < $length; $i++) {
            if (self::$_stack[$i]['id'] === $id) {
                return array(
                    'index' => $i,
                    'info' => self::$_stack[$i]
                );
            }
        }

        return null;
    }

    private static function _stackHasHtmlEffect($item) {
        do {
            $item = self::_stackPrevControl($item);
            if ($item['info']['type'] === ShortcodesEffects::HTML_EFFECT) {
                return true;
            }
        } while ($item && $item['info']['type'] !== ShortcodesEffects::CONTROL);

        return false;
    }

    private static function stack_get_effect_name($item, $name) {
        do {
            $item = self::_stackPrevControl($item);
            if (strpos($item['info']['className'], $name) === 0) {
                return $item;
            }
        } while ($item && $item['info']['type'] !== ShortcodesEffects::CONTROL);

        return false;
    }

    private static function _stackGetBackgroundWidthEffect($item) {
        return self::stack_get_effect_name($item, 'bd-background-width');
    }

    private static function _stackGetPageWidthEffect($item) {
        return self::stack_get_effect_name($item, 'bd-page-width');
    }

    private static function _stackGetAlignContentEffect($item) {
        return self::stack_get_effect_name($item, 'bd-align-content');
    }

    private static function _stackPrevControl($item) {
        return empty(self::$_stack[$item['index'] - 1]) ?
            null :
            array(
                'index' => $item['index'] - 1,
                'info' => self::$_stack[$item['index'] - 1]
            );
    }

    private static function _stackInnerControl($item) {

        $i = $item['index'];

        do {
            $i++;
        } while (
            !empty(self::$_stack[$i]) &&
            self::$_stack[$i]['type'] !== ShortcodesEffects::CONTROL
        );

        return empty(self::$_stack[$i]) ?
            null :
            array(
                'index' => $i,
                'info' => self::$_stack[$i]
            );
    }

    public static function target_control($id) {
        $target = null;

        if ($item = self::_stackItem($id)) {
            $target = self::_stackInnerControl($item);
            $target = $target['info'];
        }

        return $target;
    }

    public static function print_all_css($atts, $prop, $selector) {

        $style = '';

        foreach (self::$responsive_rules as $sfx => $wrap) {
            $key = $prop . $sfx;
            if (empty($atts[$key])) continue;

            $style .= "\n" . $wrap[0] . $selector . '{' . $atts[$key] . '}' . $wrap[1];
        }

        return $style;
    }

    public static function print_all_typography($atts, $prop, $selector, $baseSelector = '') {

        $style = '';

        foreach (self::$responsive_rules as $sfx => $wrap) {
            $key = $prop . $sfx;
            if (empty($atts[$key])) continue;

            $style .= "\n" . $wrap[0] .
                self::_processTypographyCss($atts[$key], $selector, $baseSelector) .
                $wrap[1];
        }

        return $style;
    }

    private static function _controlCss($currentControl, $atts) {
        if (!self::_stackHasHtmlEffect($currentControl))
            return '';

        $groups = array('positioning', 'transform');
        $skipProps = array();
        if (!self::_hasCss($currentControl, 'position', array('absolute', 'fixed'))) {
            $skipProps = array('height', 'width');
        }

        foreach (self::$_skip_attributes as $group => $props) {
            foreach ($props as $prop => $values) {
                if (self::_hasCss($currentControl, $prop, $values)) {
                    $skipProps[] = $prop;
                }
            }
        }

        return self::print_all_css(array(
            ''    => self::_effectCssDefaults($atts['css'], $groups, $skipProps),
            '_md' => self::_effectCssDefaults($atts['css_md'], $groups, $skipProps),
            '_sm' => self::_effectCssDefaults($atts['css_sm'], $groups, $skipProps),
            '_xs' => self::_effectCssDefaults($atts['css_xs'], $groups, $skipProps)
        ), '', $currentControl['info']['selector']);
    }

    private static function _hasCss($control, $prop, $values, $responsive = 'css') {
        return preg_match('/' . $prop .  '\s*:\s*(' . join('|', $values) . ');/', $control['info']['css'][$responsive]);
    }

    private static function _htmlEffectCss($currentControl, $targetControl) {
        $style = '';
        $isAbsoluteControl = self::_hasCss($targetControl, 'position', array('absolute', 'fixed'));

        $groups = array('positioning');
        $skipProps = array();
        if (!$isAbsoluteControl) {
            $skipProps = array('height', 'width');
        }

        if ($currentControl['info']['supportTransform']) {
            $groups[] = 'transform';
        }

        if (self::_stackHasHtmlEffect($currentControl)) {
            // html эффект в html эффекте
            if ($isAbsoluteControl) {
                $style .= self::print_all_css(
                    array('' => 'height: 100%;'),
                    '',
                    $currentControl['info']['selector']
                );
            }
        } else {
            // top html эффект
            $style .= self::print_all_css(array(
                ''    => self::_effectCssCurrent($targetControl['info']['css']['css'], $groups, $skipProps),
                '_md' => self::_effectCssCurrent($targetControl['info']['css']['css_md'], $groups, $skipProps),
                '_sm' => self::_effectCssCurrent($targetControl['info']['css']['css_sm'], $groups, $skipProps),
                '_xs' => self::_effectCssCurrent($targetControl['info']['css']['css_xs'], $groups, $skipProps)
            ), '', $currentControl['info']['selector']);
        }

        return $style;
    }

    private static function _effectCss($currentControl, $atts) {
        $style = '';

        switch ($currentControl['info']['type']) {
            case ShortcodesEffects::CONTROL:
                $style .= self::_controlCss($currentControl, $atts);
                break;
            case ShortcodesEffects::HTML_EFFECT:
                $targetControl = self::_stackInnerControl($currentControl);
                $style .= self::_htmlEffectCss($currentControl, $targetControl);
                break;
        }

        return $style;
    }

    private static function _processTagStyles($atts, $prefix) {

        if (!isset($atts["{$prefix}tags_styles"]))
            return '';

        $classes = array();

        foreach(self::$_tags_styles_types as $name => $type) {

            $style = $atts["{$prefix}tag_{$name}_style"];

            $is_bootstrap = isset($atts["{$prefix}tag_{$name}_type"]) && $atts["{$prefix}tag_{$name}_type"] === 'bootstrap';

            if (!$style && !$is_bootstrap) {
                continue;
            }

            $class_name = ShortcodesStyles::getStyleClassname($type, $style);
            if (!$class_name && !$is_bootstrap) {
                continue;
            }

            switch($name) {
                case 'list':
                    $classes[] = 'bd-custom-bulletlist';
                    break;
                case 'button':
                    if ($is_bootstrap) {
                        $classes[] = 'bd-bootstrap-btn';
                        $classes[] = "bd-$style";
                        if ($size = $atts["{$prefix}tag_button_size"])
                            $classes[] = "bd-$size";
                    }
                    $classes[] = 'bd-custom-button';
                    break;
                case 'table':
                    if ($is_bootstrap) {
                        $classes[] = 'bd-bootstrap-tables';
                        if (ShortcodesUtility::getBool($atts["{$prefix}tag_table_striped"]))
                            $classes[] = 'bd-table-striped';
                        if (ShortcodesUtility::getBool($atts["{$prefix}tag_table_bordered"]))
                            $classes[] = 'bd-table-bordered';
                        if (ShortcodesUtility::getBool($atts["{$prefix}tag_table_hover"]))
                            $classes[] = 'bd-table-hover';
                        if (ShortcodesUtility::getBool($atts["{$prefix}tag_table_condensed"]))
                            $classes[] = 'bd-table-condensed';
                        if (ShortcodesUtility::getBool($atts["{$prefix}tag_table_responsive"]))
                            $classes[] = 'bd-table-responsive';
                    }
                    $classes[] = 'bd-custom-table';
                    break;
                case 'ordered':
                    $classes[] = 'bd-custom-orderedlist';
                    break;
                case 'blockquotes':
                    $classes[] = 'bd-custom-blockquotes';
                    break;
                case 'image':
                    if ($is_bootstrap) {
                        $classes[] = 'bd-bootstrap-img';
                        if ($shape = $atts["{$prefix}tag_image_shape"])
                            $classes[] = "bd-$shape";
                        if (ShortcodesUtility::getBool($atts["{$prefix}tag_image_responsive"]))
                            $classes[] = 'bd-img-responsive';
                    }
                    $classes[] = 'bd-custom-image';
                    break;
                case 'input':
                    $classes[] = 'bd-custom-inputs';
                    break;
            }

            if (!$is_bootstrap) {
                $classes[] = ShortcodesStyles::getMixinClassname($type, $style);
            }
        }
        return join(' ', $classes);
    }

    public static function css($id, $atts, $prefix = '', $selector_pattern = '{selector}', $additional_class = '', $baseSelector = '') {
        $currentControl = self::_stackItem($id);
        $className = $prefix . $currentControl['info']['className'];

        $className = $additional_class ? $additional_class : $className;
        $classNames = array($className);

        $selector_pattern = str_replace('{selector}', '.' . $className, $selector_pattern);
        $baseSelector = str_replace('{selector}', '.' . $className, $baseSelector);

        $style = '';
        $style .= self::print_all_css($atts, $prefix . 'css', $selector_pattern);
        $style .= self::print_all_typography($atts, $prefix . 'typography', $selector_pattern, $baseSelector);

        if (!$prefix) {
            // только топ контролы
            $style .= self::_effectCss($currentControl, $atts);

            if (self::_stackGetBackgroundWidthEffect($currentControl)) {
                $classNames[] = 'bd-background-width';
            }

            if (self::_stackGetPageWidthEffect($currentControl)) {
                $classNames[] = 'bd-page-width';
            }
        }

        $classNames = array_merge($classNames, self::hidden_classes($atts, $prefix));

        $classNames[] = self::_processTagStyles($atts, $prefix);

        return array(
            $style ? "<style>$style</style>" : '',
            ' ' . join(' ', $classNames),
            '.' . $className
        );
    }

    public static function hidden_classes($atts, $prefix = '') {
        $classNames = array();
        if (isset($atts[$prefix . 'hide']) && $atts[$prefix . 'hide']) {
            $hide = explode(',', $atts[$prefix . 'hide']);
            foreach($hide as $hide_type) {
                $classNames[] = (' hidden-' . $hide_type);
            }
        }
        return $classNames;
    }

    private static function _getTypographySelectors() {
        $typography_selectors = <<<EOT
{"TypographyLabel":"label","TypographyLabelTag":"label","TypographyInput":"input","TypographyInputTag":"input","TypographyButton":"button","TypographyButtonTag":"button","TypographySelect":"select","TypographySelectTag":"select","TypographyTextArea":"textarea","TypographyTextAreaTag":"textarea","TypographyQuote":"{selector}","TypographyQuoteTag":"blockquote","TypographyText":"{selector}","TypographyTextTag":"","TypographyTextLinkPassive":"{selector}","TypographyTextLinkPassiveTag":"a","TypographyTextLinkHovered":"{selector}:hover","TypographyTextLinkHoveredTag":"a","TypographyTextLinkVisited":"{selector}:visited","TypographyTextLinkVisitedTag":"a","TypographyTextLinkActive":"{selector}:active","TypographyTextLinkActiveTag":"a","TypographyH1":"{selector}","TypographyH1Tag":"h1","TypographyH1LinkPassive":"{selector} a","TypographyH1LinkPassiveTag":"h1","TypographyH1LinkHovered":"{selector} a:hover","TypographyH1LinkHoveredTag":"h1","TypographyH1LinkVisited":"{selector} a:visited","TypographyH1LinkVisitedTag":"h1","TypographyH1LinkActive":"{selector} a:active","TypographyH1LinkActiveTag":"h1","TypographyH2":"{selector}","TypographyH2Tag":"h2","TypographyH2LinkPassive":"{selector} a","TypographyH2LinkPassiveTag":"h2","TypographyH2LinkHovered":"{selector} a:hover","TypographyH2LinkHoveredTag":"h2","TypographyH2LinkVisited":"{selector} a:visited","TypographyH2LinkVisitedTag":"h2","TypographyH2LinkActive":"{selector} a:active","TypographyH2LinkActiveTag":"h2","TypographyH3":"{selector}","TypographyH3Tag":"h3","TypographyH3LinkPassive":"{selector} a","TypographyH3LinkPassiveTag":"h3","TypographyH3LinkHovered":"{selector} a:hover","TypographyH3LinkHoveredTag":"h3","TypographyH3LinkVisited":"{selector} a:visited","TypographyH3LinkVisitedTag":"h3","TypographyH3LinkActive":"{selector} a:active","TypographyH3LinkActiveTag":"h3","TypographyH4":"{selector}","TypographyH4Tag":"h4","TypographyH4LinkPassive":"{selector} a","TypographyH4LinkPassiveTag":"h4","TypographyH4LinkHovered":"{selector} a:hover","TypographyH4LinkHoveredTag":"h4","TypographyH4LinkVisited":"{selector} a:visited","TypographyH4LinkVisitedTag":"h4","TypographyH4LinkActive":"{selector} a:active","TypographyH4LinkActiveTag":"h4","TypographyH5":"{selector}","TypographyH5Tag":"h5","TypographyH5LinkPassive":"{selector} a","TypographyH5LinkPassiveTag":"h5","TypographyH5LinkHovered":"{selector} a:hover","TypographyH5LinkHoveredTag":"h5","TypographyH5LinkVisited":"{selector} a:visited","TypographyH5LinkVisitedTag":"h5","TypographyH5LinkActive":"{selector} a:active","TypographyH5LinkActiveTag":"h5","TypographyH6":"{selector}","TypographyH6Tag":"h6","TypographyH6LinkPassive":"{selector} a","TypographyH6LinkPassiveTag":"h6","TypographyH6LinkHovered":"{selector} a:hover","TypographyH6LinkHoveredTag":"h6","TypographyH6LinkVisited":"{selector} a:visited","TypographyH6LinkVisitedTag":"h6","TypographyH6LinkActive":"{selector} a:active","TypographyH6LinkActiveTag":"h6","TypographyBulletList":"{selector}","TypographyBulletListTag":"ul > li","TypographyBulletListLinkPassive":"{selector} a","TypographyBulletListLinkPassiveTag":"ul > li","TypographyBulletListLinkHovered":"{selector} a:hover","TypographyBulletListLinkHoveredTag":"ul > li","TypographyBulletListLinkVisited":"{selector} a:visited","TypographyBulletListLinkVisitedTag":"ul > li","TypographyBulletListLinkActive":"{selector} a:active","TypographyBulletListLinkActiveTag":"ul > li","TypographyOrderedList":"{selector}","TypographyOrderedListTag":"ol > li","TypographyOrderedListLinkPassive":"{selector} a","TypographyOrderedListLinkPassiveTag":"ol > li","TypographyOrderedListLinkHovered":"{selector} a:hover","TypographyOrderedListLinkHoveredTag":"ol > li","TypographyOrderedListLinkVisited":"{selector} a:visited","TypographyOrderedListLinkVisitedTag":"ol > li","TypographyOrderedListLinkActive":"{selector} a:active","TypographyOrderedListLinkActiveTag":"ol > li"}
EOT;
        return json_decode($typography_selectors, true);
    }

    private static function _parseRules($css) {
        return array_filter(array_map('trim', explode(';', $css)));
    }

    private static function _stringifyCss($selector, $rules) {
        if (empty($rules))
            return '';
        return $selector . "{\n\t" . implode(";\n\t", $rules) . ";\n}\n";
    }

    private static function _parseTypography($typography) {
        $groups = explode('}', $typography);
        $result = array();
        foreach($groups as $group) {
            $group = explode('{', $group);
            if (count($group) !== 2)
                continue;
            $result[trim($group[0])] = self::_parseRules($group[1]);
        }
        return $result;
    }

    private static function _getTypographyRules($typographyName, $selector, $rules) {
        if (substr($typographyName, -3) === 'Tag') {
            return self::_stringifyCss($selector, $rules);
        }
        $paragraphRules = array();
        $textRules = array();
        $linkRules = array();
        foreach($rules as $rule) {
            if (!preg_match('#([^:]*):(.*)$#', $rule, $matches)) {
                continue;
            }
            list(, $property, $val) = $matches;

            switch ($property) {
                case 'margin-top':
                case 'margin-right':
                case 'margin-bottom':
                case 'margin-left':
                case 'padding-top':
                case 'padding-right':
                case 'padding-bottom':
                case 'padding-left':
                case 'text-indent':
                    if ($typographyName === 'Text')
                        $paragraphRules[] = "$property: $val";
                    else
                        $textRules[] = "$property: $val";
                    break;

                case 'text-shadow':
                    $textRules[] = "-webkit-$property: $val";
                    $textRules[] = "-o-$property: $val";
                    $textRules[] = "-ms-$property: $val";
                    $textRules[] = "-moz-$property: $val";
                    $textRules[] = "$property: $val";
                    break;

                case 'transition':
                    $linkRules[] = "$property: $val";
                    break;

                default:
                    $textRules[] = "$property: $val";
                    break;
            }
        }
        return self::_stringifyCss($selector, $textRules) . self::_stringifyCss("$selector p", $paragraphRules) . self::_stringifyCss("$selector a", $linkRules);
    }

    private static function _processTypographyCss($css, $parentSelector, $baseSelector) {
        $typography = self::_parseTypography($css);

        $result = '';
        foreach($typography as $typographyName => $rules) {
            $selector = self::_getTypographySelector("Typography$typographyName", $parentSelector, $baseSelector);
            $result .= self::_getTypographyRules($typographyName, $selector, $rules);
        }
        return $result;
    }

    private static function _getTypographySelector($typographyName, $parentSelector, $baseSelector) {
        $typographySelectors = self::_getTypographySelectors();
        if (!$baseSelector) {
            $baseSelector = $parentSelector . ' {tag}';
        }
        $selector = $typographySelectors[$typographyName];
        $tag = $typographySelectors[$typographyName . 'Tag'] ? $typographySelectors[$typographyName . 'Tag'] : '';
        $baseSelectorValue = trim(str_replace('{tag}', $tag , $baseSelector));
        $selector = str_replace('{selector}', $baseSelectorValue , $selector);

        $tmp_selectors = explode(',', $selector);
        $selectors = array();
        for ($i = 0; $i < count($tmp_selectors); $i++) {
            $value = $tmp_selectors[$i];
            if ($value === '' && $typographyName === 'TypographyText') {
                $value = 'body';
            }
            $value = trim($value);
            if($value) {
                array_push($selectors, $value);
            }
        }
        return implode (', ', $selectors);
    }


    private static $_icon_styles = array();

    public static function putIconStyles($stylesJson) {
        $styles = (array)json_decode($stylesJson);
        self::$_icon_styles = array_merge(self::$_icon_styles, $styles);
    }

    public static function getIconStyle($icon) {
        return isset(self::$_icon_styles[$icon]) ? self::$_icon_styles[$icon] : '';
    }

    public static function addClassesAndAttrs($content, $target_control, $additional_classes, $additional_attributes) {
        $target_class = $target_control["className"];
        $open_tag_regexp = "/(class=['\"][^'\"]*\\b$target_class\\b)([^'\"]*)(['\"])([^>]*)>/";
        $content = preg_replace_callback(
            $open_tag_regexp,
            function ($matches) use ($additional_classes, $additional_attributes) {
                $existed_classes = $matches[2];
                $existed_classes = preg_split('/\s+/', $existed_classes, -1, PREG_SPLIT_NO_EMPTY);
                $existed_classes = array_merge($existed_classes, $additional_classes);
                $existed_classes = array_unique($existed_classes);
                $existed_classes = ' ' . implode(' ', $existed_classes);

                $existed_attrs = $matches[4];
                foreach($additional_attributes as $name => $val) {
                    if(false !== strpos ($existed_attrs, $name . '="')) {
                        $existed_attrs = str_replace($name . '="', $name . '="' . $val . ',', $existed_attrs);
                    } else {
                        $existed_attrs .= (' ' . $name . '=' . json_encode($val . ''));
                    }
                }
                return $matches[1] . $existed_classes . $matches[3] . $existed_attrs . '>';
            },
            $content
        );

        return $content;
    }
}

class ThemeShortcodesStyles
{
    public static function putStyleClassname($type, $style, $className, $mixinClass = '') {
        return ShortcodesStyles::putStyleClassname($type, $style, $className, $mixinClass);
    }
}

class ShortcodesStyles
{
    private static $_styleClassNames = array();
    private static $_styleMixinClasses = array();

    public static function putStyleClassname($type, $style, $className, $mixinClass = '') {
        $type = strtolower($type);
        self::$_styleClassNames[$type . ':' . $style] = $className;
        self::$_styleMixinClasses[$type . ':' . $style] = $mixinClass;
    }

    public static function getStyleClassname($type, $style) {
        $type = strtolower($type);
        if (isset(self::$_styleClassNames["$type:$style"])) {
            return self::$_styleClassNames["$type:$style"];
        }
        return '';
    }

    public static function getMixinClassname($type, $style) {
        $type = strtolower($type);
        if (isset(self::$_styleMixinClasses["$type:$style"])) {
            return self::$_styleMixinClasses["$type:$style"];
        }
        return '';
    }
}

class DesignerShortcodes
{
    public static $shortcodes = array();
    public static $filters = array();

    public static function getRegexp() {
        $tagnames = array_keys(DesignerShortcodes::$shortcodes);
        $tagnames = array_map('preg_quote', $tagnames);
        $extendedTagNames = array();
        foreach($tagnames as $name)
            $extendedTagNames[] =  $name . '_?\d*';
        $tagregexp = join('|', $extendedTagNames);
        return '\\[(\\[?)' . "($tagregexp)" . '(?![\\w-])([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)(?:(\\/)\\]|\\](?:([^\\[]*+(?:\\[(?!\\/\\2\\])[^\\[]*+)*+)\\[\\/\\2\\])?)(\\]?)';
    }

    public static function process($content) {
        if ('' == $content)
            return $content;
        $content = DesignerShortcodes::applyFilters($content);
        $content = DesignerShortcodes::buildShortCodes($content);
        return $content;
    }

    public static function applyFilters($content) {
        foreach(DesignerShortcodes::$filters as $filter => $func)
            $content = call_user_func($func, $content);
        return $content;
    }

    public static function buildShortCodes($content) {
        $shortcodes = DesignerShortcodes::$shortcodes;
        if ( false === strpos( $content, '[' ) ) {
            return $content;
        }

        if (empty($shortcodes) || !is_array($shortcodes))
            return $content;

        $pattern = DesignerShortcodes::getRegexp();
        $content = preg_replace_callback("/$pattern/s", 'DesignerShortcodes::replacer', $content);
        return $content;
    }

    public static function replacer($matches) {
        if ( $matches[1] == '[' && $matches[6] == ']' ) {
            return substr($matches[0], 1, -1);
        }
        $tag = $matches[2];
        $originalTag = preg_replace('/(_\d+$)/', '', $matches[2]);
        $attr = self::parseAttr($matches[3]);

        if (isset($matches[5])) {
            return $matches[1] . call_user_func( DesignerShortcodes::$shortcodes[$originalTag], $attr, $matches[5], $tag ) . $matches[6];
        } else {
            return $matches[1] . call_user_func( DesignerShortcodes::$shortcodes[$originalTag], $attr, null,  $tag ) . $matches[6];
        }
    }

    public static function parseAttr($text) {
        $atts = array();
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
            foreach ($match as $m) {
                if (!empty($m[1]))
                    $atts[strtolower($m[1])] = stripcslashes($m[2]);
                elseif (!empty($m[3]))
                    $atts[strtolower($m[3])] = stripcslashes($m[4]);
                elseif (!empty($m[5]))
                    $atts[strtolower($m[5])] = stripcslashes($m[6]);
                elseif (isset($m[7]) and strlen($m[7]))
                    $atts[] = stripcslashes($m[7]);
                elseif (isset($m[8]))
                    $atts[] = stripcslashes($m[8]);
            }
        } else {
            $atts = ltrim($text);
        }
        return $atts;
    }

    public static function getBool($value, $defaultValue = false) {
        if ($value === true || $value === '1' || $value === 'true' || $value === 'yes')
            return true;
        if ($value === false || $value === '0' || $value === 'false' || $value === 'no')
            return false;
        return $defaultValue;
    }

    public static function prepareAttr($attr = array()) {
        if (count($attr) == 0)
            return '';
        $result = '';
        foreach ($attr as $name => $value) {
            if (empty($name) || empty($value))
                continue;
            $result .= ' ' . strtolower($name) . '="' . $value . '"';
        }
        return $result;
    }

    public static function getShortCode($tag, $content = '', $atts = '') {
        if (!is_array($atts))
            $atts = array();

        $code = "[$tag";
        foreach($atts as $key => $value) {
            if (is_numeric($key)) {
                $code .= " $value";
            } else {
                $code .= " $key=\"$value\"";
            }
        }
        return "$code]$content" . "[/$tag]";
    }
}

function googlemap_styling($atts){
   extract(ShortcodesUtility::atts(array(
        'address' => '',
        'zoom' => '',
        'map_type' => '',
        'language' => '',
        'css' => 'height:300px;width:100%',
    ), $atts));

    $languages = array("eu", "ca", "hr", "cs", "da", "nl", "en", "fi", "fr", "de", "gl", "el", "hi", "id", "it", "ja", "no",
                        "nn", "pt", "rm", "ru", "sr", "sk", "sl", "es", "sv", "th", "tr", "uk", "vi");

    if ($address !== '') {
          $address = '&q=' . $address;
      }

    if ($zoom !== ''){
        $num = (int) $zoom;
        if ($num>0){
            $zoom = '&z=' . $num;
        }
        else{
            $zoom = '';
        }
    }

    if ($map_type !== ''){
        switch ($map_type) {
          case "road":
            $map_type = '&t=m';
            break;
          case "satelite":
            $map_type = '&t=k';
            break;
          default:
            $map_type = '';
        }
    }

    if ($language !== '' && in_array($language, $languages)){
        $language = '&hl=' . $language;
    }
    else{
        $language = '';
    }

    $divs = '<div style="' . $css . '"><div class="embed-responsive" style="height: 100%; width: 100%;">';
    $iframe = '<iframe class="embed-responsive-item" src="//maps.google.com/maps?output=embed' . $address . $zoom . $map_type . $language . '"></iframe>';
    $divEnd = '</div>';

    return $divs . $iframe . $divEnd . $divEnd;
}

DesignerShortcodes::$shortcodes['googlemap'] = 'googlemap_styling';

// [box css="" full_width="yes|no" content_width="yes|no"]content with shortcodes[/box]
function box_styling($atts, $content='') {
    $atts = ShortcodesUtility::atts(array(
        'css' => '',
        'content_width' => 'yes',
        'class_names' => ''
    ), $atts);

    $css = $atts['css'];
    $content_width = $atts['content_width'] === 'yes';
    $class_names = $atts['class_names'];

    $result = '<div';
    if ($class_names !== '') {
        $result .= ' class="' . $class_names . '"';
    }
    if ($css !== '') {
        $result .= ' style="' . $css . '"';
    }
    $result .= '>';
    if ($content_width) {
        $result .= '<div class="bd-container-inner">';
    }
    $result .= ShortcodesUtility::doShortcode($content);
    if ($content_width) {
        $result .= '</div>';
    }
    $result .= '</div>';
    return $result;
}
DesignerShortcodes::$shortcodes['box'] = 'box_styling';

// [[video link="https://www.youtube.com/watch?v=f20ym8X-9IU" autoplay="yes" loop="yes" title="no" lightBar="yes" style="width: 300px"][/video]
function video_styling($atts, $content='') {
    extract(ShortcodesUtility::atts(array(
        'link' => '/',
        'autoplay' => 'no',
        'loop' => 'no',
        'title' => 'yes',
        'light_control_bar' => 'no',
        'show_control_bar' => 'show',
        'css' => ''
    ), $atts));

    $isYouTube = strrpos($link, 'youtube');
    $isVimeo = strrpos($link, 'vimeo');

    if ($isYouTube !== false) {
        list(, $id) = explode('=', $link);
        list($id,) = explode('&', $id);
        $url = 'https://www.youtube.com/embed/' . $id . '?';

        if ($autoplay === 'yes')
            $url .= 'autoplay=1&';

        if ($title === 'no')
            $url .= 'showinfo=1&';

        if (light_control_bar === 'yes')
            $url .= 'theme=light&';

        if ($loop === 'yes')
            $url .= 'loop=1&playlist=' . $id . ' ';

        if ($show_control_bar === 'autohide')
            $url .= 'autohide=1&';
        else if ($show_control_bar === 'hide')
             $url .= 'controls=0&';

        $iframe = '<iframe src="' . $url . '"></iframe>';
    } else if ($isVimeo !== false) {
       $id = end(explode('/', $link));
       $url = 'https://player.vimeo.com/video/' . $id . '?';

       if ($autoplay === 'yes')
           $url .= 'autoplay=1&';

       if ($title === 'no')
           $url .= 'title=1&';

       if (light_control_bar === 'yes')
           $url .= 'color=ffffff&';

       if ($loop === 'yes')
           $url .= 'loop=1';

       $iframe = '<iframe src="' . $url . '"></iframe>';
    }

    return '<div class="embed-responsive embed-responsive-16by9" style="' . $css . '">' . $iframe . '</div>';
}
DesignerShortcodes::$shortcodes['video'] = 'video_styling';

/*
 [slider css="" wide_slides="yes|no" wide_carousel="yes|no" interval="3000"]
    [slide css="" image="http:// | id" link="" linktarget=""]any slide content here[/slide]
 [/slider]
*/

function existsCssProperty($property, $css) {
    $existsProperty = false;
    if ($css !== '') {
        $styles = explode(';', $css);
        foreach ($styles as $i => $style) {
            $parts = explode(':', $style);
            if ($property === trim($parts[0]) && count($parts) > 1) {
                $existsProperty = true;
            }
        }
    }
    return $existsProperty;
}

function themler_get_page_title() {
    return JFactory::getDocument()->title;
}

function themler_get_page_url() {
    return JURI::getInstance()->toString();
}

class ThemeColumns {
    /*
        [column width_lg="6" width="8" width_sm="12" width_xs="6"] Your Content Here [/column]
        [one_half last] 1/2 [/one_half]
        [one_third] 1/3 [/one_third]
        [two_third last] 2/3 [/two_third]
        [one_fourth]  1/4 [/one_fourth]
        [three_fourth] 3/4 [/three_fourth]
    */
    public static function row($atts, $content='', $tag='') {
        global $columnInRow;
        $columnInRow = true;
        $result = DesignerShortcodes::buildShortCodes($content);
        $columnInRow = false;
        return DesignerShortcodes::getShortCode($tag, $result, $atts);
    }
    public static function column($atts, $content = '', $tag = '') {
        global $columnInRow;
        if (isset($columnInRow) && $columnInRow)
            return DesignerShortcodes::getShortCode($tag, $content, $atts);

        extract(ShortcodesUtility::atts(array('last' => false), $atts));

        $new_atts = array();
        if (is_array($atts)) {
            foreach($atts as $key => $value) {
                if (is_numeric($key) && 'last' === $value)
                    $last = true;
                else
                    $new_atts[$key] = $value;
            }
        }

        $row_atts = '';
        $row_atts .= isset($atts['vertical_align']) ? 'vertical_align="' . $atts['vertical_align'] . '"' : '';
        $row_atts .= isset($atts['auto_height']) ? ' auto_height="' . $atts['auto_height'] . '"' : '';
        $row_atts .= isset($atts['collapse_spacing']) ? ' collapse_spacing="' . $atts['collapse_spacing'] . '"' : '';

        $content = DesignerShortcodes::buildShortCodes($content);
        return '<!--Column--><' . $row_atts . '>' . DesignerShortcodes::getShortCode('column', $content, $new_atts) . '<!--/Column' . ($last ? 'Last' : '') . '-->';
    }

    public static function one_half($atts, $content = '') {
        $atts['width'] = "12";
        return ThemeColumns::column($atts, $content);
    }

    public static function one_third($atts, $content = '') {
        $atts['width'] = "8";
        return ThemeColumns::column($atts, $content);
    }

    public static function two_third($atts, $content = '') {
        $atts['width'] = "16";
        return ThemeColumns::column($atts, $content);
    }

    public static function one_fourth($atts, $content = '') {
        $atts['width'] = "6";
        return ThemeColumns::column($atts, $content);
    }

    public static function three_fourth($atts, $content = '') {
        $atts['width'] = "18";
        return ThemeColumns::column($atts, $content);
    }

    public static function full_width($atts, $content = '') {
        $atts['width'] = "24";
        return ThemeColumns::column($atts, $content);
    }

    public static $row = false;
    public static function themecolumns_filter($content) {

        ThemeColumns::saveOriginalShortcodes(array(
            '#^column(_\d+)?$#' => 'ThemeColumns::column',
            '#^row(_\d+)?$#' => 'ThemeColumns::row',
            '#^columns(_\d+)?$#' => 'ThemeColumns::row',
        ));

        DesignerShortcodes::$shortcodes['column'] = 'ThemeColumns::column';
        DesignerShortcodes::$shortcodes['row'] = 'ThemeColumns::row';
        DesignerShortcodes::$shortcodes['one_half'] = 'ThemeColumns::one_half';
        DesignerShortcodes::$shortcodes['one_third'] = 'ThemeColumns::one_third';
        DesignerShortcodes::$shortcodes['two_third'] = 'ThemeColumns::two_third';
        DesignerShortcodes::$shortcodes['one_fourth'] = 'ThemeColumns::one_fourth';
        DesignerShortcodes::$shortcodes['three_fourth'] = 'ThemeColumns::three_fourth';
        DesignerShortcodes::$shortcodes['full_width'] = 'ThemeColumns::full_width';
        $content = DesignerShortcodes::buildShortCodes($content);

        ThemeColumns::restoreOriginalShortcodes();

        ThemeColumns::$row = false;
        $content = preg_replace('/(<!--\/Column)(?:Last){0,1}(-->)(?!.*<!--\/Column)/s', '$1Last$2', $content, 1);
        return  preg_replace_callback('/<!--Column--><([^>]*?)>(.*?)<!--\/Column(Last){0,1}-->/s','ThemeColumns::callback', $content);
    }

    public static function callback($matches)
    {
        $result = '';
        if (!ThemeColumns::$row) {
            $result .= '[row ' . $matches[1] . ']';
            ThemeColumns::$row = true;
        }
        $result .= $matches[2];
        if (isset($matches[3])) {
            $result .= '[/row]';
            ThemeColumns::$row = false;
        }
        return $result;
    }

    public static $themler_shortcodes_stack = array();
    public static $tmpShortcodes = array();

    public static function saveOriginalShortcodes($funcs)
    {
        ThemeColumns::$themler_shortcodes_stack[] = DesignerShortcodes::$shortcodes;
        DesignerShortcodes::$shortcodes = array();
        $last = ThemeColumns::$themler_shortcodes_stack[count(ThemeColumns::$themler_shortcodes_stack) - 1];
        foreach(array_merge($last, ThemeColumns::$tmpShortcodes) as $tag => $func) {
            foreach($funcs as $regex => $fn) {
                if (preg_match($regex, $tag)) {
                    DesignerShortcodes::$shortcodes[$tag] = $fn;
                    break;
                }
            }
        }
    }

    public static function restoreOriginalShortcodes()
    {
        DesignerShortcodes::$shortcodes = array_pop(ThemeColumns::$themler_shortcodes_stack);
    }

    public static function themecolumns_old_filter($content) {

        $add_shortcodes = array();
        foreach(DesignerShortcodes::$shortcodes as $tag => $func) {
            if (preg_match('#^row(_\d+)?$#', $tag))
                $add_shortcodes[str_replace('row', 'columns', $tag)] = $func;
            if (preg_match('#^columns(_\d+)?$#', $tag))
                $add_shortcodes[str_replace('columns', 'row', $tag)] = $func;
        }
        ThemeColumns::$tmpShortcodes = array_merge(DesignerShortcodes::$shortcodes, $add_shortcodes);

        ThemeColumns::saveOriginalShortcodes(array('#^row(_\d+)?$#' => 'ThemeColumns::old_row'));
        $content = DesignerShortcodes::buildShortCodes($content);
        ThemeColumns::restoreOriginalShortcodes();

        return $content;
    }

    public static $old_rows_stack;
    public static $old_row_data;

    public static function old_row($atts, $content = '', $tag = '') {

        ThemeColumns::$old_rows_stack[] = array();

        ThemeColumns::saveOriginalShortcodes(array('#^column(_\d+)?$#' => 'ThemeColumns::themler_old_column_shortcode_collect_width'));
        DesignerShortcodes::buildShortCodes($content);
        ThemeColumns::restoreOriginalShortcodes();

        ThemeColumns::$old_row_data = array_pop(ThemeColumns::$old_rows_stack);

        if (isset($GLOBALS['shortcodes_columns_24']) && $GLOBALS['shortcodes_columns_24'] == false) {
            $items = ThemeColumns::$old_row_data;
            foreach (array('width', 'width_sm', 'width_lg', 'width_xs') as $prop) {
                $sum = 0;
                $odds = array();
                $evens = array();
                $difference = 0;

                $len = count($items);

                for ($i = 0; $i < $len; $i++) {
                    if (!isset($items[$i][$prop])) {
                        continue;
                    }

                    $value = intval($items[$i][$prop]);

                    if ($value) {
                        if (!isset($max_value_index) || $value > intval($items[$max_value_index][$prop])) {
                            $max_value_index = $i;
                        }

                        if ($value % 2 === 0) {
                            $evens[$i] = $value;
                        } else {
                            $odds[$i] = $value;
                        }

                        $sum += intval($items[$i][$prop]);
                    }
                }

                if ($sum && isset($max_value_index)) {
                    for ($i = 0; $i < $len; $i++) {
                        if (!isset($items[$i][$prop])) {
                            continue;
                        }

                        $value = intval($items[$i][$prop]);

                        if (isset($odds[$i])) {
                            if ($odds[$i] === 1) {
                                $items[$i][$prop] = 1;
                                $difference -= 0.5;
                            } else {
                                $items[$i][$prop] = floor($value / 2);
                                $difference += 0.5;
                            }
                        } else if (isset($evens[$i])) {
                            $items[$i][$prop] = $value / 2;
                        }
                    }

                    if ($difference > 0 && intval($difference) == $difference) {
                        $items[$max_value_index][$prop] = intval($items[$max_value_index][$prop]) + $difference;
                    }
                }
            }
            ThemeColumns::$old_row_data = $items;
        }

        ThemeColumns::saveOriginalShortcodes(array('#^column(_\d+)?$#' => 'ThemeColumns::themler_old_column_shortcode_set_width'));
        $content = DesignerShortcodes::buildShortCodes($content);
        ThemeColumns::restoreOriginalShortcodes();

        return DesignerShortcodes::getShortCode(str_replace('row', 'columns', $tag), DesignerShortcodes::buildShortCodes($content), $atts);
    }

    public static function themler_old_column_shortcode_collect_width($atts, $content, $tag)
    {
        if (count(ThemeColumns::$old_rows_stack) > 0) {
            ThemeColumns::$old_rows_stack[count(ThemeColumns::$old_rows_stack) - 1][] = $atts;
        }
        return '';
    }

    public static function themler_old_column_shortcode_set_width($atts, $content, $tag)
    {
        $result = DesignerShortcodes::getShortCode($tag, $content, ThemeColumns::$old_row_data[0]);
        ThemeColumns::$old_row_data = array_splice(ThemeColumns::$old_row_data, 1);
        return $result;
    }
}

DesignerShortcodes::$filters[] = 'ThemeColumns::themecolumns_filter';
DesignerShortcodes::$filters[] = 'ThemeColumns::themecolumns_old_filter';


function themler_shortcodes_icon_state_style($id, $args) {
    $picture = empty($args['picture']) ? '' : $args['picture'];
    $icon = empty($args['icon']) ? '' : $args['icon'];
    $selector = empty($args['selector']) ? '' : $args['selector'];
    $atts = empty($args['atts']) ? array() : $args['atts'];
    $icon_prefix = empty($args['icon_prefix']) ? '' : $args['icon_prefix'];

    list($main_styles, ) = ShortcodesEffects::css($id, $atts, $icon_prefix, "{selector}:before", substr($selector, 1));
    $result = '';

    if ($picture) {
        $result .= "$selector:before {
            content: url($picture);
            font-size: 0 !important;
            line-height: 0 !important;
        }";
    } else {
        if ($icon === 'none') {
            $result .= "$selector:before {visibility: hidden;}";
        } else {
            $result .=
                "$selector {
                    text-decoration: inherit;
	                display: inline-block;
	                speak: none;
	            }

                $selector:before {
                    font-family: 'Billion Web Font';
	                font-style: normal;
	                font-weight: normal;
	                text-decoration: inherit;
	                text-align: center;
	                text-transform: none;
	                width: 1em;
                }";

            $result .= str_replace('{selector}', $selector, ShortcodesEffects::getIconStyle($icon));
            $result .= "$selector:before {width: auto; visibility: inherit;}";
        }
    }
    $result .= "$selector:before{display: inline-block;}";
    $result = '<style>' . $result . '</style>' . $main_styles;

    $font_size = ShortcodesEffects::css_prop($atts, 'font-size', '', $icon_prefix);
    $line_height_factor = ShortcodesEffects::css_prop($atts, 'line-height-factor', '', $icon_prefix);
    if (!$picture && $icon && $icon !== 'none' && $font_size && !$line_height_factor) {
        $result .= "<style>$selector:before{line-height: $font_size;}</style>";
    }

    $result .= "<style>$selector:before {
        vertical-align: middle;
        text-decoration: none;
    }</style>";

    return $result;
}

?>
<?php

// Affix
function themler_shortcode_affix($atts, $content='', $tag = '') {
    $atts = ShortcodesUtility::atts(array(
        'offset' => '',
        'fixatscreen' => 'top',
        'clipatcontrol' => 'top',
        'enable_lg' => 'yes',
        'enable_md' => 'yes',
        'enable_sm' => 'yes',
        'enable_xs' => 'no',
    ), $atts);

    $enable_lg = ShortcodesUtility::getBool($atts['enable_lg']);
    $enable_md = ShortcodesUtility::getBool($atts['enable_md']);
    $enable_sm = ShortcodesUtility::getBool($atts['enable_sm']);
    $enable_xs = ShortcodesUtility::getBool($atts['enable_xs']);
    $offset = ShortcodesUtility::escape($atts['offset']);
    $fixatscreen = ShortcodesUtility::escape($atts['fixatscreen']);
    $clipatcontrol = ShortcodesUtility::escape($atts['clipatcontrol']);

    $id = ShortcodesEffects::init('bd-affix', $atts, ShortcodesEffects::HTML_EFFECT);

    $data_attrs = "data-affix data-offset='$offset' data-fix-at-screen='$fixatscreen' data-clip-at-control='$clipatcontrol'" .
        ($enable_lg ? ' data-enable-lg' : '') .
        ($enable_md ? ' data-enable-md' : '') .
        ($enable_sm ? ' data-enable-sm' : '') .
        ($enable_xs ? ' data-enable-xs' : '')
        ;

    $content = ShortcodesUtility::doShortcode($content);
    list(, $additional_classes, $selector) = ShortcodesEffects::css($id, $atts, '', '.affix{selector}');

    $class = substr($selector, 1);
    list($style_tag_transition,) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, 'transition'), '', '{selector}', $class);
    list($style_tag_other,) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, '!transition,!left,!right,!top,!width'), '', '.affix{selector}', $class);
    list($style_tag_arrange,) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, 'left,right,top,width'), '', '.affix{selector}', $class);
    $style_tag_arrange = str_replace(';', ' !important;', $style_tag_arrange);

    return "<!--[$tag]-->" . $style_tag_other . $style_tag_arrange . $style_tag_transition .
                '<div ' . $data_attrs . ' class="' . $additional_classes . '">' .
                    '<!--{content}-->' .
                        $content .
                    '<!--{/content}-->' .
                '</div>' .
            "<!--[/$tag]-->";
}
ShortcodesUtility::addShortcode('affix', 'themler_shortcode_affix');
?>
<?php

// AlignContent
function themler_shortcode_align_content($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(
        'sheet_align' => false
    ), $atts);

    $sheetAlign = ShortcodesUtility::getBool($atts['sheet_align'], false);

    $id = ShortcodesEffects::init('bd-aligncontent', $atts, ShortcodesEffects::CSS_EFFECT);
    $content = ShortcodesUtility::doShortcode($content);

    $targetSelector = '{selector}';
    $targetSelectorInner =
        "${targetSelector}[data-aligncontent-size=\"sheet\"] > .bd-section-inner > .bd-section-align-wrapper," .
        "${targetSelector}[data-aligncontent-size=\"page\"]  > .bd-section-inner," .
        "$targetSelector > .bd-container-inner," .
        "$targetSelector > .bd-vertical-align-wrapper";

    list($style_tag, $additional_classes, $selector) = ShortcodesEffects::css(
        $id,
        ShortcodesEffects::filter($atts, 'margin,padding,width'),
        '',
        $targetSelectorInner
    );

    $targetControl = ShortcodesEffects::target_control($id);

    $content = ShortcodesEffects::addClassesAndAttrs(
        $content,
        $targetControl,
        array('bd-aligncontent', $additional_classes),
        array(
            'data-aligncontent-size' => $sheetAlign ? 'sheet' : 'page'
        )
    );

    return "<!--[$tag]-->$style_tag<!--{content}-->" . $content . "<!--{/content}--><!--[/$tag]-->";
}

ShortcodesUtility::addShortcode('align_content', 'themler_shortcode_align_content');
?>
<?php

// BoxAlign
function themler_shortcode_box_align($atts, $content = '', $tag) {
    $atts = ShortcodesUtility::atts(array(
        'type' => 'center'
    ), $atts);

    $type = ShortcodesUtility::escape($atts['type']);

    $id = ShortcodesEffects::init('bd-boxalign', $atts, true);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_css, $additional_classes, $selector) = ShortcodesEffects::css($id, $atts, '', '{selector}');

    $alignCss = array(
        'text-align: ' . ($type ? $type : 'left') . ' !important;'
    );

    $alignChildrenCss = array(
        'display: inline-block !important;',
        'text-align: left !important;'
    );

    ob_start();
    ?>

    <!--[<?php echo $tag ?>]-->
    <?php echo $style_css ?>
    <style>
        <?php if ($type): ?>
        <?php echo $selector ?>
        {
                <?php echo join("\n", $alignCss) ?>
        }
        <?php endif ?>

        <?php echo $selector ?> > *
        {
            <?php echo join("\n", $alignChildrenCss) ?>
        }
    </style>
    <div class="<?php echo $additional_classes ?>">
        <!--{content}-->
            <?php echo $content ?>
        <!--{/content}-->
    </div>
    <!--[/<?php echo $tag ?>]-->

    <?php
    return ob_get_clean();
}

ShortcodesUtility::addShortcode('box_align', 'themler_shortcode_box_align');
?>
<?php

// Animation
function themler_shortcode_animation($atts, $content = '', $tag) {
    $atts = ShortcodesUtility::atts(array(
        'name'           => 'bounce',
        'infinited'      => 'false',
        'event'          => 'hover',
        'duration'       => '1000ms',
        'delay'          => '0ms',
        '_display'       => ''
    ), $atts);

    $name = ShortcodesUtility::escape($atts['name']);
    $infinited = ShortcodesUtility::escape($atts['infinited']);
    $event = ShortcodesUtility::escape($atts['event']);
    $duration = ShortcodesUtility::escape($atts['duration']);
    $delay = ShortcodesUtility::escape($atts['delay']);
    $_display = ShortcodesUtility::escape($atts['_display']);

    $id = ShortcodesEffects::init('bd-animation', $atts, ShortcodesEffects::CSS_EFFECT);
    $content = ShortcodesUtility::doShortcode($content);
    $targetControl = ShortcodesEffects::target_control($id);
    list($style_tag, $additional_classes, $selector) = ShortcodesEffects::css($id, $atts);
    $content = ShortcodesEffects::addClassesAndAttrs(
        $content,
        $targetControl,
        array('animated', $additional_classes),
        array(
            'data-animation-name' => $name,
            'data-animation-event' => $event,
            'data-animation-duration' => $duration,
            'data-animation-delay' => $delay,
            'data-animation-infinited' => $infinited
        )
    );

    $infinited_css = $infinited === 'true' ?
        "-webkit-animation-iteration-count: infinite;\n" .
        "animation-iteration-count: infinite;"
        : '';
    $style_tag .= "<style>
        $selector.animated.$name {
            -webkit-animation-duration: $duration;
            animation-duration: $duration;
            -webkit-animation-delay: $delay;
            animation-delay: $delay;
            $infinited_css
        }
        </style>";

    ob_start();
?>
    <!--[<?php echo $tag ?>]-->
        <?php echo $style_tag ?>
        <!--{content}-->
            <?php echo $content ?>
        <!--{/content}-->
    <!--[/<?php echo $tag ?>]-->

    <?php
    return ob_get_clean();
}

ShortcodesUtility::addShortcode('animation', 'themler_shortcode_animation');
?>
<?php

// BackgroundWidth
function themler_shortcode_background_width($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(), $atts);

    ShortcodesEffects::init('bd-background-width', $atts, ShortcodesEffects::CSS_EFFECT);
    $content = ShortcodesUtility::doShortcode($content);

    return "<!--[$tag]--><!--{content}-->" . $content . "<!--{/content}--><!--[/$tag]-->";
}

ShortcodesUtility::addShortcode('background_width', 'themler_shortcode_background_width');
?>
<?php

// Balloon
function themler_shortcode_balloon($atts, $content = '', $tag) {
    $atts = ShortcodesUtility::atts(array(
        'align' => 'bottom',
        'size' => '20px',
        'position' => '50%'
    ), $atts);

    $align = $atts['align'];
    $size = $atts['size'];
    $position = $atts['position'];

    $id = ShortcodesEffects::init('bd-balloon', $atts, ShortcodesEffects::HTML_EFFECT);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_classes, $selector) = ShortcodesEffects::css($id, $atts);

    if ($align !== 'top' && $align !== 'right' && $align !== 'left' && $align !== 'bottom')
        $align = 'bottom';

    $inverse = array(
        'left' => 'right',
        'right' => 'left',
        'top' => 'bottom',
        'bottom' => 'top'
    );

    $_target = ShortcodesEffects::target_control($id);
    $_controlBorderWidth = ShortcodesEffects::css_prop($_target['css'], "border-$align-width");
    $_controlBorderColor = ShortcodesEffects::css_prop($_target['css'], "border-$align-color");
    $_controlBorderStyle = ShortcodesEffects::css_prop($_target['css'], "border-$align-style");

    $borderWidth = intval($_controlBorderWidth);
    $baseArrowSize = $_controlBorderWidth !== '' && $borderWidth ? ($borderWidth * 1.5) + intval($size) : 0;

    $_t = "$selector {position: relative;}";

    $_background_color = ShortcodesEffects::css_prop($_target['css'], "background-color");

    if ($_background_color === '') {
        $_t .= "$selector {background-color: #ddd;}";
        $_t .= "$selector:after {border-" . $inverse[$align] . "-color: #ddd !important;}";
    }

    $_t .= "
        $selector {
            position: relative;
        }
        $selector:after,
        $selector:before {
            border-color: transparent;
            border-style: solid;
            content: \" \";
            height: 0;
            position: absolute;
            pointer-events: none;
            width: 0;
            ".$inverse[$align] . " 100%;
        }";


    if ($_controlBorderStyle)
        $_t .= "$selector:before {border-style: $_controlBorderStyle;}";
    else
        $_t .= "$selector:before {border-style: solid;}";


    $_t .= "$selector:after {";
    $_t .= 'border-color: transparent;';

    if ($_background_color) {
        $_t .= "border-".$inverse[$align]."-color: $_background_color;";
    }

    $_t .= "border-width: $size;";

    if ($align === 'top' || $align === 'bottom') {
        $_t .= "left: $position;";
        $_t .= "margin-left: -$size;";
    }

    if ($align === 'right' || $align === 'left') {
        $_t .= "margin-top: -$size;";
        $_t .= "top: $position;";
    }
    $_t .= '}';

    if ($baseArrowSize > 0) {
        $_t .= "$selector:before {
            border-".$inverse[$align]."-color: $_controlBorderColor;
            border-width: {$baseArrowSize}px;
            ";

        if ($align === 'top' || $align === 'bottom') {
            $_t .= "left: $position;";
            $_t .= "margin-left: -{$baseArrowSize}px;";
        }
        if ($align === 'right' || $align === 'left') {
            $_t .= "margin-top: -{$baseArrowSize}px;";
            $_t .= "top: $position;";
        }
        $_t .= '}';
    }

    ob_start();
?>

    <!--[<?php echo $tag ?>]-->
        <style><?php echo $_t; ?></style>
        <?php echo $style_tag ?>
        <div class="<?php echo $additional_classes ?>">
            <!--{content}-->
                <?php echo $content ?>
            <!--{/content}-->
        </div>
    <!--[/<?php echo $tag ?>]-->

<?php
    return ob_get_clean();
}

ShortcodesUtility::addShortcode('balloon', 'themler_shortcode_balloon');
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Blockquotes', "", "bd-blockquotes", "bd-blockquotes-1-mixin");
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Button', 'default', 'btn-default');
ThemeShortcodesStyles::putStyleClassname('Button', 'primary', 'btn-primary');
ThemeShortcodesStyles::putStyleClassname('Button', 'success', 'btn-success');
ThemeShortcodesStyles::putStyleClassname('Button', 'info', 'btn-info');
ThemeShortcodesStyles::putStyleClassname('Button', 'warning', 'btn-warning');
ThemeShortcodesStyles::putStyleClassname('Button', 'danger', 'btn-danger');
ThemeShortcodesStyles::putStyleClassname('Button', 'link', 'btn-link');
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Image', 'rounded', 'img-rounded');
ThemeShortcodesStyles::putStyleClassname('Image', 'circle', 'img-circle');
ThemeShortcodesStyles::putStyleClassname('Image', 'thumbnail', 'img-thumbnail');
?>
<?php

// BootstrapProgressbars
function themler_shortcode_progress($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(
        'complete' => '50%',
        'show_label' => false,
        'striped' => false,
        'animated' => false
    ), $atts);

    $id = ShortcodesEffects::init('progress', $atts);
    list(, $additional_class, $selector) = ShortcodesEffects::css($id, $atts);
    $class = substr($selector, 1);

    list($tag1,) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, 'positioning,size'), '', '{selector}', $class);
    list($tag2,) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, 'background,color'), '', '{selector} .progress-bar', $class);

    $_animation = ($atts['striped'] ? 'progress-striped' : '') . ($atts['striped'] && $atts['animated'] ? ' active' : '');

    return  "<!--[$tag]-->" . $tag1 . $tag2 .
                '<div class="progress ' . $_animation . ' ' . $additional_class . '">' .
                    '<div class="progress-bar" role="progressbar" style="width: ' . $atts['complete'] . ';">' .
                        '<!--{content}-->' .
                            ($atts['show_label'] ? $atts['complete'] : '') .
                        '<!--{/content}-->' .
                    '</div>' .
                '</div>' .
            "<!--[/$tag]-->";
}

ShortcodesUtility::addShortcode('progress', 'themler_shortcode_progress');
?>
<?php

// BoxControl
function themler_shortcode_box_absolute($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(), $atts);

    $id = ShortcodesEffects::init('', $atts);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class) = ShortcodesEffects::css($id, $atts);

    return  "<!--[$tag]-->" . $style_tag .
                '<div class="' . $additional_class . '">' .
                    '<div class="bd-container-inner">' .
                        '<div class="bd-container-inner-wrapper">' .
                            '<!--{content}-->' .
                                $content .
                            '<!--{/content}-->' .
                        '</div>' .
                    '</div>' .
                '</div>' .
            "<!--[/$tag]-->";
}

ShortcodesUtility::addShortcode('box_absolute', 'themler_shortcode_box_absolute');
?>
<?php
ThemeShortcodesStyles::putStyleClassname('BulletList', "", "bd-bulletlist", "bd-bulletlist-1-mixin");
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Button', "button-16", "bd-button-16", "bd-button-16-mixin");
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Button', "button-13", "bd-button-13", "bd-button-13-mixin");
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Button', "button-12", "bd-button-12", "bd-button-12-mixin");
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Button', "button-17", "bd-button-17", "bd-button-17-mixin");
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Button', "button-15", "bd-button-15", "bd-button-15-mixin");
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Button', "button-14", "bd-button-14", "bd-button-14-mixin");
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Button', "", "bd-button", "bd-button-1-mixin");
?>
<?php

// LinkButton
function themler_shortcode_button($atts, $content = '', $tag = '', $parent = array()) {
    if ($parent) {
        $id = $parent['id'];
        $prefix = $parent['prefix'];
    } else {
        $atts = ShortcodesUtility::atts(array(
            'link' => '',
            'href' => '',
            'type' => 'default',
            'style' => '',
            'size' => '',
            'rel' => '',
            'title' => '',
            'screen_tip' => '',
            'target' => '',

            'icon' => '',
            'picture' => '',
            'icon_hovered' => '',
            'picture_hovered' => '',
        ), $atts, array('', 'icon_', 'icon_hovered_'));

        $id = ShortcodesEffects::init('', $atts);
        $prefix = '';
    }

    $size = strtolower($atts[$prefix . 'size']);
    $style = $atts[$prefix . 'style'];
    $type = $atts[$prefix . 'type'];
    $link = ShortcodesUtility::escape($atts['link']);
    if (!$link) {
        $link = ShortcodesUtility::escape($atts['href']);
    }
    $rel = ShortcodesUtility::escape($atts[$prefix . 'rel']);
    $title = ShortcodesUtility::escape($atts['title']);
    if (!$title) {
        $title = ShortcodesUtility::escape($atts['screen_tip']);
    }
    $target = ShortcodesUtility::escape($atts['target']);

    $icon_passive = ShortcodesUtility::escape($atts[$prefix . 'icon']);
    $picture_passive = ShortcodesUtility::escape($atts[$prefix . 'picture']);
    $icon_hovered = ShortcodesUtility::escape($atts[$prefix . 'icon_hovered']);
    $picture_hovered = ShortcodesUtility::escape($atts[$prefix . 'picture_hovered']);

    $link_content = $content;
    $sizes = array('large' => 'btn-lg', 'small' => 'btn-sm', 'xsmall' => 'btn-xs');

    $classes = array();

    if ($type === 'bootstrap') {
        $classes[] = 'btn';
        $classes[] = ShortcodesStyles::getStyleClassname('Button', $style ? $style : 'default');

        if (array_key_exists($size, $sizes)) {
            $classes[] = $sizes[$size];
        }
    } else {
        $classes[] = ShortcodesStyles::getStyleClassname('Button', $style);
    }

    list($style_tag, $additional_classes, $selector) = ShortcodesEffects::css($id, $atts, $prefix);
    $classes[] = $additional_classes;

    if ($icon_passive && $icon_passive !== 'none' || $picture_passive ||
        $icon_hovered && $icon_hovered !== 'none' || $picture_hovered) {

        $classes[] = 'bd-icon';
    }

    $style_tag .= themler_shortcodes_icon_state_style($id, array(
        'picture' => $picture_passive,
        'icon' => $icon_passive,
        'selector' => $selector,
        'atts' => $atts,
        'icon_prefix' => $prefix . 'icon_'
    ));
    $style_tag .= themler_shortcodes_icon_state_style($id, array(
        'picture' => $picture_hovered,
        'icon' => $icon_hovered,
        'selector' => "$selector:hover",
        'atts' => $atts,
        'icon_prefix' => $prefix . 'icon_hovered_'
    ));

    $html_atts = array();
    if ($rel) {
        $html_atts[] = 'rel="' . $rel . '"';
    }
    if ($title) {
        $html_atts[] = 'title="' . $title . '"';
    }
    if ($target) {
        $html_atts[] = 'target="' . $target . '"';
    }
    $html_atts[] = 'href="' . $link . '"';
    $html_atts[] = 'class="' . implode(' ', $classes) . ' bd-content-element"';
    $content = "<a " . implode(' ', $html_atts) . "><!--{content}-->\n$link_content\n<!--{/content}--></a>";

    return $parent ? array('html' => $content, 'css' => $style_tag) :
        '<!--[button]-->' .
            $style_tag .
            $content .
        '<!--[/button]-->';
}
ShortcodesUtility::addShortcode('button', 'themler_shortcode_button');
?>
<?php

// ContainerEffect
function themler_shortcode_container_effect($atts, $content, $tag) {
    $atts = ShortcodesUtility::atts(array(), $atts);

    $id = ShortcodesEffects::init('bd-containereffect', $atts, ShortcodesEffects::HTML_EFFECT);

    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_classes) = ShortcodesEffects::css($id, $atts, '', '{selector}');

    return "<!--[$tag]--> $style_tag" .
            '<div class="container ' . $additional_classes . '">' .
                '<!--{content}-->' .
                    $content .
                '<!--{/content}-->' .
            '</div>' .
        "<!--[/$tag]-->";
}

ShortcodesUtility::addShortcode('container_effect', 'themler_shortcode_container_effect');
?>
<?php

// ContainerInnerEffect
function themler_shortcode_container_inner_effect($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(), $atts);

    ShortcodesEffects::init('bd-page-width', $atts, ShortcodesEffects::CSS_EFFECT);
    $content = ShortcodesUtility::doShortcode($content);

    return "<!--[$tag]--><!--{content}-->" . $content . "<!--{/content}--><!--[/$tag]-->";
}

ShortcodesUtility::addShortcode('container_inner_effect', 'themler_shortcode_container_inner_effect');
?>
<?php

// CustomHtml
function themler_shortcode_html($atts, $content = '') {
    $atts = ShortcodesUtility::atts(ShortcodesEffects::tagsStylesAtts(array(
        'css_additional' => ''
    )), $atts);

    $id = ShortcodesEffects::init('', $atts);
    list($style_tag, $additional_class) = ShortcodesEffects::css($id, $atts);

    $content = ShortcodesUtility::doShortcode($content);
    return  '<!--[html]-->' .
                $style_tag .
                '<style>' . $atts['css_additional'] . '</style>' .
                '<div class="bd-tagstyles ' . $additional_class . '">' .
                    '<div class="bd-container-inner bd-content-element">' .
                        '<!--{content}-->' .
                            $content .
                        '<!--{/content}-->' .
                    '</div>' .
                '</div>' .
            '<!--[/html]-->';
}

ShortcodesUtility::addShortcode('html', 'themler_shortcode_html');
?>
<?php

// FlexColumn
function themler_shortcode_flex_column($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(
        'direction' => 'column',
        'responsive' => 'xs'
    ), $atts);

    $direction = ShortcodesUtility::escape($atts['direction']);
    $responsive = ShortcodesUtility::escape($atts['responsive']);

    $id = ShortcodesEffects::init('bd-flex-column', $atts, ShortcodesEffects::CSS_EFFECT);
    $content = ShortcodesUtility::doShortcode($content);
    ShortcodesEffects::css($id, $atts);

    $displayFlex = implode("\n", array(
        'display: -webkit-box;',
        'display: -webkit-flex;',
        'display: -ms-flexbox;',
        'display: flex;'
    ));

    $flexBasis0 = implode("\n", array(
        '-webkit-flex-basis: 0;',
        '-ms-flex-preferred-size: 0;',
        'flex-basis: 0;'
    ));

    $flexGrow1 = implode("\n", array(
        '-webkit-box-flex: 1;',
        '-webkit-flex-grow: 1;',
        '-ms-flex-positive: 1;',
        'flex-grow: 1;'
    ));

    $flexDirectionColumn = implode("\n", array(
        '-webkit-box-orient: vertical;',
        '-webkit-box-direction: normal;',
        '-webkit-flex-direction: column;',
        '-ms-flex-direction: column;',
        'flex-direction: column;'
    ));

    $flexWrap = implode("\n", array(
        '-webkit-flex-wrap: wrap;',
        '-ms-flex-wrap: wrap;',
        'flex-wrap: wrap;'
    ));

    $targetControl = ShortcodesEffects::target_control($id);

    $mediaStart = '';
    $mediaEnd = '';

    if ($responsive && $responsive !== 'none' && $responsive !== 'lg') {
        list($mediaStart, $mediaEnd) = ShortcodesEffects::$responsive_rules_min['_' . $responsive];
    }

    ob_start();
?>
    <!--[<?php echo $tag ?>]-->
        <style>
            <?php echo $targetControl['selector'] ?> {
                <?php echo $displayFlex ?>
                <?php echo $flexDirectionColumn ?>
            }

            <?php echo $targetControl['selector'] ?> > .bd-vertical-align-wrapper
            {
                <?php echo $displayFlex ?>
                <?php echo $flexWrap ?>

                <?php if ($direction === 'column'): ?>
                -webkit-box-orient: vertical;
                -webkit-box-direction: normal;
                <?php endif ?>
                -webkit-flex-direction: <?php echo $direction ?>;
                -ms-flex-direction: <?php echo $direction ?>;
                flex-direction: <?php echo $direction ?>;

                width: 100%;
            }

            <?php echo $targetControl['selector'] ?> > .bd-vertical-align-wrapper > *
            {
                <?php echo $flexGrow1 ?>
            }

            <?php echo $mediaStart ?>
                <?php echo $targetControl['selector'] ?> > .bd-vertical-align-wrapper > *
                {
                    <?php echo $flexBasis0 ?>
                }
            <?php echo $mediaEnd ?>
        </style>
        <!--{content}-->
            <?php echo $content ?>
        <!--{/content}-->
    <!--[/<?php echo $tag ?>]-->
<?php
    return ob_get_clean();
}
ShortcodesUtility::addShortcode('flex_column', 'themler_shortcode_flex_column');
?>
<?php

// GoogleMap
function themler_shortcode_googlemap($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(
        'image_style' => '',
        'address' => '',
        'src' => '//maps.google.com/maps?output=embed',
        'zoom' => '',
        'map_type' => '',
        'language' => ''
    ), $atts);

    $id = ShortcodesEffects::init('', $atts);
    list($style_tag, $additional_class, $selector) = ShortcodesEffects::css($id, $atts);

    $additional_image_class = ShortcodesStyles::getStyleClassname('Image', $atts['image_style']);

    $src_params = array();
    if ($atts['address'])
        $src_params[] = 'q=' . ShortcodesUtility::escape($atts['address']);
    if ($atts['zoom'])
        $src_params[] = 'z=' . ShortcodesUtility::escape($atts['zoom']);
    $src_params[] = 't=' . str_replace(array('road', 'satelite'), array('m', 'k'), ShortcodesUtility::escape($atts['map_type']));
    if ($atts['language'])
        $src_params[] = 'hl=' . ShortcodesUtility::escape($atts['language']);

    $src = ShortcodesUtility::escape($atts['src']) . '&' . implode('&', $src_params);

    ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
        <style>
            <?php echo $selector; ?> {
                height: 300px;
                width: 100%;
                display: block;
            }
        </style>
        <?php echo $style_tag; ?>
        <div class="<?php echo $additional_image_class . ' ' . $additional_class; ?>">
            <div class="embed-responsive" style="height: 100%; width: 100%;">
                <iframe class="embed-responsive-item" src="<?php echo $src; ?>">
                </iframe>
            </div>
        </div>
    <!--[/<?php echo $tag; ?>]-->
<?php
    return ob_get_clean();
}

ShortcodesUtility::addShortcode('googlemap', 'themler_shortcode_googlemap');
?>
<?php
// HoverBox
function themler_shortcode_hover_box($atts, $content, $tag) {
    $atts = ShortcodesUtility::atts(array(
        'active' => '0',
        'motion' => 'over',
        'direction' => 'left',
        'rotation' => '',

        'duration' => '500ms',
        'func' => 'ease',
        'perspective' => 300,

        'url' => '',
        'target' => '',
        'screen_tip' => ''
    ), $atts);

    $url = ShortcodesUtility::escape($atts['url']);
    $target = ShortcodesUtility::escape($atts['target']);
    $screen_tip = ShortcodesUtility::escape($atts['screen_tip']);
    $motion = ShortcodesUtility::escape($atts['motion']);
    $direction = ShortcodesUtility::escape($atts['direction']);
    $rotation = ShortcodesUtility::escape($atts['rotation']);

    $duration = ShortcodesUtility::escape($atts['duration']);
    $func = ShortcodesUtility::escape($atts['func']);
    $perspective = ShortcodesUtility::escape($atts['perspective']);

    global $hover_box_slides;
    if (empty($hover_box_slides)) {
        $hover_box_slides = array();
    }
    $stack_length = count($hover_box_slides);

    $id = ShortcodesEffects::init('', $atts);
    ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class, $selector) = ShortcodesEffects::css($id, $atts);
    $class = substr($selector, 1);

    if (count($hover_box_slides) !== $stack_length + 2) {
        return '';
    }
    $back_slide = $hover_box_slides[$stack_length];
    $over_slide = $hover_box_slides[$stack_length + 1];
    array_pop($hover_box_slides);
    array_pop($hover_box_slides);

    $effect = themler_shortcode_get_hover_box_selector($motion, $direction, $rotation);

    list($border_radius_styles, ) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, 'border-radius'), '', '{selector} .bd-backSlide > *, {selector} .bd-overSlide > *', $class);

    $additional_class .= " bd-$effect";

    $back_slide_atts = 'class="bd-backSlide"';
    $over_slide_atts = 'class="bd-overSlide"';
    if ($url) $over_slide_atts .= ' data-url="' . $url . '"';
    if ($target) $over_slide_atts .= ' data-target="' . $target . '"';
    if ($screen_tip) $over_slide_atts .= ' title="' . $screen_tip . '"';

    $back_slide = str_replace('{slider_attributes}', $back_slide_atts, $back_slide);
    $over_slide = str_replace('{slider_attributes}', $over_slide_atts, $over_slide);

    $override_styles = '';
    if ($motion === 'flip' || $motion === 'wobble') {
        // TODO: add -webkit, -moz, ect
        $override_styles .= "
            $selector > .bd-slidesWrapper > .bd-overSlide {
                transition-duration: $duration, $duration, 0ms;

                transition-delay: 0s, 0s, $duration;
            }
        ";
    } else {
        $override_styles .= "
            $selector > .bd-slidesWrapper > .bd-overSlide {
                transition-duration: $duration;
                -webkit-transition-duration: $duration;
            }
        ";
    }

    $override_styles .= "
        $selector,
        $selector > .bd-slidesWrapper {
            -webkit-perspective: $perspective;
            -moz-perspective: $perspective;
            perspective: $perspective;
        }
    ";

    if ($motion === 'slide') {
        $override_styles .= "
            $selector > .bd-slidesWrapper > .bd-backSlide {
                transition-duration: $duration;
                -webkit-transition-duration: $duration;

                transition-timing-function: $func;
                -webkit-transition-timing-function: $func;
            }
        ";
    }

    if ($motion !== 'flip' && $motion !== 'wobble') {
        $override_styles .= "
            $selector > .bd-slidesWrapper > .bd-overSlide {
                transition-timing-function: $func;
                -webkit-transition-timing-function: $func;
            }
        ";
    }



    ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
        <?php echo $style_tag . $border_radius_styles; ?>
        <?php if ($url) { echo "<style>$selector .bd-overSlide { cursor: pointer; }</style>"; } ?>
        <style><?php echo $override_styles; ?></style>
        <div class="<?php echo $additional_class; ?> bd-tagstyles">
            <div class="bd-slidesWrapper">
                <!--{content}-->
                    <?php echo $back_slide; ?>
                    <?php echo $over_slide; ?>
                <!--{/content}-->
            </div>
        </div>
    <!--[/<?php echo $tag; ?>]-->
<?php
    return ob_get_clean();
}
ShortcodesUtility::addShortcode('hover_box', 'themler_shortcode_hover_box');

function themler_shortcode_hover_box_slide($atts, $content, $tag) {
    $atts = ShortcodesUtility::atts(array(
    ), $atts);

    $id = ShortcodesEffects::init('', $atts);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class) = ShortcodesEffects::css($id, $atts);

    ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
        <?php echo $style_tag; ?>
        <div {slider_attributes}>
            <div class="<?php echo $additional_class; ?>">
                <!--{content}-->
                    <?php echo $content; ?>
                <!--{/content}-->
            </div>
        </div>
    <!--[/<?php echo $tag; ?>]-->
<?php

    global $hover_box_slides;
    if (empty($hover_box_slides)) {
        $hover_box_slides = array();
    }
    $hover_box_slides[] = ob_get_clean();
    return '';
}
ShortcodesUtility::addShortcode('hover_box_slide', 'themler_shortcode_hover_box_slide');

function themler_shortcode_get_hover_box_selector($motion, $direction, $rotation) {
    $selector = 'effect';

    switch ($motion) {
        case 'fade':
            $selector .= '-fade';
            return $selector;
        case 'over':
            $selector .= '-over';
            break;
        case 'slide':
            $selector .= '-slide';
            break;
        case 'flip':
            $selector .= '-flip';
            break;
        case 'wobble':
            $selector .= '-wobble';
            break;
        case 'zoom':
            $selector .= '-zoom';
            switch ($rotation) {
                case 'rotate':
                    $selector .= '-rotate';
                    return $selector;
                case 'rotateX':
                    $selector .= '-rotateX';
                    break;
                case 'rotateY':
                    $selector .= '-rotateY';
                    break;
            }
            return $selector;
    }

    switch ($direction) {
        case 'left':
            $selector .= '-left';
            break;
        case 'right':
            $selector .= '-right';
            break;
        case 'top':
            $selector .= '-top';
            break;
        case 'bottom':
            $selector .= '-bottom';
            break;
    }

    if ($motion === 'over' || $motion === 'slide') {
        switch ($direction) {
            case 'topleft':
                $selector .= '-topleft';
                break;
            case 'topright':
                $selector .= '-topright';
                break;
            case 'bottomleft':
                $selector .= '-bottomleft';
                break;
            case 'bottomright':
                $selector .= '-bottomright';
                break;
        }
    }
    return $selector;
}

?>
<?php
ShortcodesEffects::putIconStyles(<<<EOT
{
    "icon-booth": "{selector}:before { content: '\\\\ff'; }",
    "icon-youtube": "{selector}:before { content: '\\\\100'; }",
    "icon-random": "{selector}:before { content: '\\\\101'; }",
    "icon-cloud-upload": "{selector}:before { content: '\\\\102'; }",
    "icon-road": "{selector}:before { content: '\\\\103'; }",
    "icon-arrow-small-up": "{selector}:before { content: '\\\\104'; }",
    "icon-dropbox": "{selector}:before { content: '\\\\105'; }",
    "icon-sort": "{selector}:before { content: '\\\\106'; }",
    "icon-angle-small": "{selector}:before { content: '\\\\107'; }",
    "icon-bitcoin": "{selector}:before { content: '\\\\108'; }",
    "icon-delicious": "{selector}:before { content: '\\\\109'; }",
    "icon-stethoscope": "{selector}:before { content: '\\\\10a'; }",
    "icon-weibo": "{selector}:before { content: '\\\\10b'; }",
    "icon-volume-off": "{selector}:before { content: '\\\\10c'; }",
    "icon-earth": "{selector}:before { content: '\\\\10d'; }",
    "icon-node-square": "{selector}:before { content: '\\\\10e'; }",
    "icon-plane": "{selector}:before { content: '\\\\10f'; }",
    "icon-undo": "{selector}:before { content: '\\\\110'; }",
    "icon-question-circle": "{selector}:before { content: '\\\\111'; }",
    "icon-tablet": "{selector}:before { content: '\\\\112'; }",
    "icon-filter-alt": "{selector}:before { content: '\\\\113'; }",
    "icon-happy": "{selector}:before { content: '\\\\114'; }",
    "icon-dialer": "{selector}:before { content: '\\\\115'; }",
    "icon-bag": "{selector}:before { content: '\\\\116'; }",
    "icon-credit-card": "{selector}:before { content: '\\\\117'; }",
    "icon-image-alt": "{selector}:before { content: '\\\\118'; }",
    "icon-shopping-cart-simple": "{selector}:before { content: '\\\\119'; }",
    "icon-arrow-basic-right": "{selector}:before { content: '\\\\11a'; }",
    "icon-male": "{selector}:before { content: '\\\\11b'; }",
    "icon-cut": "{selector}:before { content: '\\\\11c'; }",
    "icon-unhappy": "{selector}:before { content: '\\\\11d'; }",
    "icon-circle-alt": "{selector}:before { content: '\\\\11e'; }",
    "icon-double-chevron-right": "{selector}:before { content: '\\\\11f'; }",
    "icon-star-alt": "{selector}:before { content: '\\\\120'; }",
    "icon-rhomb": "{selector}:before { content: '\\\\121'; }",
    "icon-thumbs-down": "{selector}:before { content: '\\\\122'; }",
    "icon-github-alt": "{selector}:before { content: '\\\\123'; }",
    "icon-text-width": "{selector}:before { content: '\\\\124'; }",
    "icon-bookmark-alt": "{selector}:before { content: '\\\\125'; }",
    "icon-list-details": "{selector}:before { content: '\\\\126'; }",
    "icon-bullhorn": "{selector}:before { content: '\\\\127'; }",
    "icon-ellipsis": "{selector}:before { content: '\\\\128'; }",
    "icon-map-marker": "{selector}:before { content: '\\\\129'; }",
    "icon-typeface": "{selector}:before { content: '\\\\12a'; }",
    "icon-help": "{selector}:before { content: '\\\\12b'; }",
    "icon-triangle-circle": "{selector}:before { content: '\\\\12c'; }",
    "icon-gbp": "{selector}:before { content: '\\\\12d'; }",
    "icon-arrow-small-left": "{selector}:before { content: '\\\\12e'; }",
    "icon-anchor": "{selector}:before { content: '\\\\12f'; }",
    "icon-align-justify": "{selector}:before { content: '\\\\130'; }",
    "icon-arrow-circle-alt-up": "{selector}:before { content: '\\\\131'; }",
    "icon-growth": "{selector}:before { content: '\\\\132'; }",
    "icon-round-small": "{selector}:before { content: '\\\\133'; }",
    "icon-triangle-circle-alt": "{selector}:before { content: '\\\\134'; }",
    "icon-eye-close": "{selector}:before { content: '\\\\135'; }",
    "icon-code": "{selector}:before { content: '\\\\136'; }",
    "icon-step-forward": "{selector}:before { content: '\\\\137'; }",
    "icon-music": "{selector}:before { content: '\\\\138'; }",
    "icon-lightbulb": "{selector}:before { content: '\\\\139'; }",
    "icon-arrows-horizontal": "{selector}:before { content: '\\\\13a'; }",
    "icon-sign-out": "{selector}:before { content: '\\\\13b'; }",
    "icon-sort-asc": "{selector}:before { content: '\\\\13c'; }",
    "icon-play-circle": "{selector}:before { content: '\\\\13d'; }",
    "icon-bookmark": "{selector}:before { content: '\\\\13e'; }",
    "icon-pencil": "{selector}:before { content: '\\\\13f'; }",
    "icon-won": "{selector}:before { content: '\\\\140'; }",
    "icon-zoom-out": "{selector}:before { content: '\\\\141'; }",
    "icon-user-alt": "{selector}:before { content: '\\\\142'; }",
    "icon-repeat": "{selector}:before { content: '\\\\143'; }",
    "icon-text-height": "{selector}:before { content: '\\\\144'; }",
    "icon-shopping-cart-wire": "{selector}:before { content: '\\\\145'; }",
    "icon-rubl": "{selector}:before { content: '\\\\146'; }",
    "icon-find-contact": "{selector}:before { content: '\\\\147'; }",
    "icon-upload-circle-alt": "{selector}:before { content: '\\\\148'; }",
    "icon-arrow-small-down": "{selector}:before { content: '\\\\149'; }",
    "icon-file": "{selector}:before { content: '\\\\14a'; }",
    "icon-building": "{selector}:before { content: '\\\\14b'; }",
    "icon-certificate": "{selector}:before { content: '\\\\14c'; }",
    "icon-double-chevron-up": "{selector}:before { content: '\\\\14d'; }",
    "icon-hand-up": "{selector}:before { content: '\\\\14e'; }",
    "icon-italic": "{selector}:before { content: '\\\\14f'; }",
    "icon-volume-up": "{selector}:before { content: '\\\\150'; }",
    "icon-quote-right": "{selector}:before { content: '\\\\151'; }",
    "icon-sort-numeric-desc": "{selector}:before { content: '\\\\152'; }",
    "icon-four-rhombs": "{selector}:before { content: '\\\\153'; }",
    "icon-brain": "{selector}:before { content: '\\\\154'; }",
    "icon-mark": "{selector}:before { content: '\\\\155'; }",
    "icon-flickr": "{selector}:before { content: '\\\\156'; }",
    "icon-envelope": "{selector}:before { content: '\\\\157'; }",
    "icon-indent-right": "{selector}:before { content: '\\\\158'; }",
    "icon-basket-simple": "{selector}:before { content: '\\\\159'; }",
    "icon-cloud": "{selector}:before { content: '\\\\15a'; }",
    "icon-check": "{selector}:before { content: '\\\\15b'; }",
    "icon-youtube-square": "{selector}:before { content: '\\\\15c'; }",
    "icon-envelope-alt": "{selector}:before { content: '\\\\15d'; }",
    "icon-bitbucket-alt": "{selector}:before { content: '\\\\15e'; }",
    "icon-round-small-alt": "{selector}:before { content: '\\\\15f'; }",
    "icon-adn": "{selector}:before { content: '\\\\160'; }",
    "icon-linkedin-square": "{selector}:before { content: '\\\\161'; }",
    "icon-expand": "{selector}:before { content: '\\\\162'; }",
    "icon-tumblr-square": "{selector}:before { content: '\\\\163'; }",
    "icon-angle-double": "{selector}:before { content: '\\\\164'; }",
    "icon-compress": "{selector}:before { content: '\\\\165'; }",
    "icon-plus-square-alt": "{selector}:before { content: '\\\\166'; }",
    "icon-camera": "{selector}:before { content: '\\\\167'; }",
    "icon-four-boxes": "{selector}:before { content: '\\\\168'; }",
    "icon-shopping-cart-buggy": "{selector}:before { content: '\\\\169'; }",
    "icon-arrow-square-left": "{selector}:before { content: '\\\\16a'; }",
    "icon-delete-circle-alt": "{selector}:before { content: '\\\\16b'; }",
    "icon-suitcase": "{selector}:before { content: '\\\\16c'; }",
    "icon-curve-bottom": "{selector}:before { content: '\\\\16d'; }",
    "icon-caret-up": "{selector}:before { content: '\\\\16e'; }",
    "icon-renren": "{selector}:before { content: '\\\\16f'; }",
    "icon-linkedin": "{selector}:before { content: '\\\\170'; }",
    "icon-asterisk": "{selector}:before { content: '\\\\171'; }",
    "icon-arrow-pointer-left": "{selector}:before { content: '\\\\172'; }",
    "icon-sort-numeric-asc": "{selector}:before { content: '\\\\173'; }",
    "icon-calendar-simple": "{selector}:before { content: '\\\\174'; }",
    "icon-home-alt": "{selector}:before { content: '\\\\175'; }",
    "icon-step-backward": "{selector}:before { content: '\\\\176'; }",
    "icon-rss": "{selector}:before { content: '\\\\177'; }",
    "icon-globe": "{selector}:before { content: '\\\\178'; }",
    "icon-paste": "{selector}:before { content: '\\\\179'; }",
    "icon-fire": "{selector}:before { content: '\\\\17a'; }",
    "icon-star-half": "{selector}:before { content: '\\\\17b'; }",
    "icon-renminbi": "{selector}:before { content: '\\\\17c'; }",
    "icon-dribbble": "{selector}:before { content: '\\\\17d'; }",
    "icon-google-plus-square": "{selector}:before { content: '\\\\17e'; }",
    "icon-plus-square": "{selector}:before { content: '\\\\17f'; }",
    "icon-yen": "{selector}:before { content: '\\\\180'; }",
    "icon-briefcase": "{selector}:before { content: '\\\\181'; }",
    "icon-shopping-cart-trolley": "{selector}:before { content: '\\\\182'; }",
    "icon-warning": "{selector}:before { content: '\\\\183'; }",
    "icon-moon": "{selector}:before { content: '\\\\184'; }",
    "icon-sort-alpha": "{selector}:before { content: '\\\\185'; }",
    "icon-arrow-long-down": "{selector}:before { content: '\\\\186'; }",
    "icon-globe-alt": "{selector}:before { content: '\\\\187'; }",
    "icon-thumbs-up": "{selector}:before { content: '\\\\188'; }",
    "icon-sandwich": "{selector}:before { content: '\\\\189'; }",
    "icon-arrow-basic-down": "{selector}:before { content: '\\\\18a'; }",
    "icon-double-chevron-down": "{selector}:before { content: '\\\\18b'; }",
    "icon-legal": "{selector}:before { content: '\\\\18c'; }",
    "icon-apple": "{selector}:before { content: '\\\\18d'; }",
    "icon-power": "{selector}:before { content: '\\\\18e'; }",
    "icon-time-alt": "{selector}:before { content: '\\\\18f'; }",
    "icon-list": "{selector}:before { content: '\\\\190'; }",
    "icon-h-sign": "{selector}:before { content: '\\\\191'; }",
    "icon-css": "{selector}3:before { content: '\\\\192'; }",
    "icon-copy": "{selector}:before { content: '\\\\193'; }",
    "icon-arrow-tall-up": "{selector}:before { content: '\\\\194'; }",
    "icon-hdd": "{selector}:before { content: '\\\\195'; }",
    "icon-font": "{selector}:before { content: '\\\\196'; }",
    "icon-heart-circle": "{selector}:before { content: '\\\\197'; }",
    "icon-glass": "{selector}:before { content: '\\\\198'; }",
    "icon-picasa": "{selector}:before { content: '\\\\199'; }",
    "icon-arrow-long-left": "{selector}:before { content: '\\\\19a'; }",
    "icon-fullscreen": "{selector}:before { content: '\\\\19b'; }",
    "icon-lemon": "{selector}:before { content: '\\\\19c'; }",
    "icon-arrow-long-right": "{selector}:before { content: '\\\\19d'; }",
    "icon-hand-right": "{selector}:before { content: '\\\\19e'; }",
    "icon-list-details-small": "{selector}:before { content: '\\\\19f'; }",
    "icon-cog": "{selector}:before { content: '\\\\1a0'; }",
    "icon-four-boxes-alt": "{selector}:before { content: '\\\\1a1'; }",
    "icon-properties": "{selector}:before { content: '\\\\1a2'; }",
    "icon-arrow-pointer-down": "{selector}:before { content: '\\\\1a3'; }",
    "icon-inbox": "{selector}:before { content: '\\\\1a4'; }",
    "icon-arrow-double-up": "{selector}:before { content: '\\\\1a5'; }",
    "icon-plane-takeoff": "{selector}:before { content: '\\\\1a6'; }",
    "icon-arrow-square-down": "{selector}:before { content: '\\\\1a7'; }",
    "icon-tick-circle-alt": "{selector}:before { content: '\\\\1a8'; }",
    "icon-node-circle": "{selector}:before { content: '\\\\1a9'; }",
    "icon-arrow-pointer-right": "{selector}:before { content: '\\\\1aa'; }",
    "icon-starlet": "{selector}:before { content: '\\\\1ab'; }",
    "icon-cogs": "{selector}:before { content: '\\\\1ac'; }",
    "icon-arrow-long-up": "{selector}:before { content: '\\\\1ad'; }",
    "icon-bug": "{selector}:before { content: '\\\\1ae'; }",
    "icon-upload": "{selector}:before { content: '\\\\1af'; }",
    "icon-xing": "{selector}:before { content: '\\\\1b0'; }",
    "icon-minus-square-alt": "{selector}:before { content: '\\\\1b1'; }",
    "icon-arrows": "{selector}:before { content: '\\\\1b2'; }",
    "icon-trash-can": "{selector}:before { content: '\\\\1b3'; }",
    "icon-pushpin": "{selector}:before { content: '\\\\1b4'; }",
    "icon-eye-open": "{selector}:before { content: '\\\\1b5'; }",
    "icon-caret-left": "{selector}:before { content: '\\\\1b6'; }",
    "icon-bitbucket": "{selector}:before { content: '\\\\1b7'; }",
    "icon-lines": "{selector}:before { content: '\\\\1b8'; }",
    "icon-magic": "{selector}:before { content: '\\\\1b9'; }",
    "icon-arrow-double-right": "{selector}:before { content: '\\\\1ba'; }",
    "icon-remove-sign": "{selector}:before { content: '\\\\1bb'; }",
    "icon-exclamation-sign": "{selector}:before { content: '\\\\1bc'; }",
    "icon-chevron-down": "{selector}:before { content: '\\\\1bd'; }",
    "icon-sort-alpha-asc": "{selector}:before { content: '\\\\1be'; }",
    "icon-comments-alt": "{selector}:before { content: '\\\\1bf'; }",
    "icon-terminal": "{selector}:before { content: '\\\\1c0'; }",
    "icon-box": "{selector}:before { content: '\\\\1c1'; }",
    "icon-lock": "{selector}:before { content: '\\\\1c2'; }",
    "icon-bolt": "{selector}:before { content: '\\\\1c3'; }",
    "icon-filter": "{selector}:before { content: '\\\\1c4'; }",
    "icon-folder-alt": "{selector}:before { content: '\\\\1c5'; }",
    "icon-backward": "{selector}:before { content: '\\\\1c6'; }",
    "icon-sort-amount-asc": "{selector}:before { content: '\\\\1c7'; }",
    "icon-tag": "{selector}:before { content: '\\\\1c8'; }",
    "icon-house": "{selector}:before { content: '\\\\1c9'; }",
    "icon-drop": "{selector}:before { content: '\\\\1ca'; }",
    "icon-arrow-thick-right": "{selector}:before { content: '\\\\1cb'; }",
    "icon-ambulance": "{selector}:before { content: '\\\\1cc'; }",
    "icon-chevron-right": "{selector}:before { content: '\\\\1cd'; }",
    "icon-sign-in": "{selector}:before { content: '\\\\1ce'; }",
    "icon-sort-amount-desc": "{selector}:before { content: '\\\\1cf'; }",
    "icon-search-circle": "{selector}:before { content: '\\\\1d0'; }",
    "icon-skype": "{selector}:before { content: '\\\\1d1'; }",
    "icon-fast-forward": "{selector}:before { content: '\\\\1d2'; }",
    "icon-maxcdn": "{selector}:before { content: '\\\\1d3'; }",
    "icon-book-open": "{selector}:before { content: '\\\\1d4'; }",
    "icon-vimeo": "{selector}:before { content: '\\\\1d5'; }",
    "icon-beaker": "{selector}:before { content: '\\\\1d6'; }",
    "icon-facebook-alt": "{selector}:before { content: '\\\\1d7'; }",
    "icon-arrow-thin-down": "{selector}:before { content: '\\\\1d8'; }",
    "icon-heartlet": "{selector}:before { content: '\\\\1d9'; }",
    "icon-shopping-cart-solid": "{selector}:before { content: '\\\\1da'; }",
    "icon-linux": "{selector}:before { content: '\\\\1db'; }",
    "icon-leaf": "{selector}:before { content: '\\\\1dc'; }",
    "icon-hand-down": "{selector}:before { content: '\\\\1dd'; }",
    "icon-pinterest": "{selector}:before { content: '\\\\1de'; }",
    "icon-barcode": "{selector}:before { content: '\\\\1df'; }",
    "icon-curve-top": "{selector}:before { content: '\\\\1e0'; }",
    "icon-euro": "{selector}:before { content: '\\\\1e1'; }",
    "icon-arrow-basic-left": "{selector}:before { content: '\\\\1e2'; }",
    "icon-group": "{selector}:before { content: '\\\\1e3'; }",
    "icon-tumblr": "{selector}:before { content: '\\\\1e4'; }",
    "icon-fighter": "{selector}:before { content: '\\\\1e5'; }",
    "icon-hand-left": "{selector}:before { content: '\\\\1e6'; }",
    "icon-stripes-thick": "{selector}:before { content: '\\\\1e7'; }",
    "icon-superscript": "{selector}:before { content: '\\\\1e8'; }",
    "icon-minus-square": "{selector}:before { content: '\\\\1e9'; }",
    "icon-ticklet-circle": "{selector}:before { content: '\\\\1ea'; }",
    "icon-xing-square": "{selector}:before { content: '\\\\1eb'; }",
    "icon-arrow-double-left": "{selector}:before { content: '\\\\1ec'; }",
    "icon-forward": "{selector}:before { content: '\\\\1ed'; }",
    "icon-arrow-thick-down": "{selector}:before { content: '\\\\1ee'; }",
    "icon-eject": "{selector}:before { content: '\\\\1ef'; }",
    "icon-reply": "{selector}:before { content: '\\\\1f0'; }",
    "icon-search": "{selector}:before { content: '\\\\1f1'; }",
    "icon-comment-alt": "{selector}:before { content: '\\\\1f2'; }",
    "icon-share": "{selector}:before { content: '\\\\1f3'; }",
    "icon-arrows-vertical": "{selector}:before { content: '\\\\1f4'; }",
    "icon-food": "{selector}:before { content: '\\\\1f5'; }",
    "icon-flag": "{selector}:before { content: '\\\\1f6'; }",
    "icon-female": "{selector}:before { content: '\\\\1f7'; }",
    "icon-tasks": "{selector}:before { content: '\\\\1f8'; }",
    "icon-quote-left": "{selector}:before { content: '\\\\1f9'; }",
    "icon-arrow-tall-left": "{selector}:before { content: '\\\\1fa'; }",
    "icon-minus-circle": "{selector}:before { content: '\\\\1fb'; }",
    "icon-box-alt": "{selector}:before { content: '\\\\1fc'; }",
    "icon-arrow-tall-down": "{selector}:before { content: '\\\\1fd'; }",
    "icon-indent-left": "{selector}:before { content: '\\\\1fe'; }",
    "icon-arrow-thick-left": "{selector}:before { content: '\\\\1ff'; }",
    "icon-list-adv": "{selector}:before { content: '\\\\200'; }",
    "icon-chevron-up": "{selector}:before { content: '\\\\201'; }",
    "icon-medkit": "{selector}:before { content: '\\\\202'; }",
    "icon-tags": "{selector}:before { content: '\\\\203'; }",
    "icon-coffee": "{selector}:before { content: '\\\\204'; }",
    "icon-ticklet": "{selector}:before { content: '\\\\205'; }",
    "icon-box-small": "{selector}:before { content: '\\\\206'; }",
    "icon-sign-blank": "{selector}:before { content: '\\\\207'; }",
    "icon-basket": "{selector}:before { content: '\\\\208'; }",
    "icon-search-thick": "{selector}:before { content: '\\\\209'; }",
    "icon-edit-alt": "{selector}:before { content: '\\\\20a'; }",
    "icon-comment": "{selector}:before { content: '\\\\20b'; }",
    "icon-hospital": "{selector}:before { content: '\\\\20c'; }",
    "icon-arrow-small-right": "{selector}:before { content: '\\\\20d'; }",
    "icon-grid-small": "{selector}:before { content: '\\\\20e'; }",
    "icon-circle-arrow-left": "{selector}:before { content: '\\\\20f'; }",
    "icon-yahoo": "{selector}:before { content: '\\\\210'; }",
    "icon-print": "{selector}:before { content: '\\\\211'; }",
    "icon-instagram": "{selector}:before { content: '\\\\212'; }",
    "icon-arrow-angle-up": "{selector}:before { content: '\\\\213'; }",
    "icon-leaf-thin": "{selector}:before { content: '\\\\214'; }",
    "icon-magnet": "{selector}:before { content: '\\\\215'; }",
    "icon-arrow-thin-up": "{selector}:before { content: '\\\\216'; }",
    "icon-retweet": "{selector}:before { content: '\\\\217'; }",
    "icon-search-glare": "{selector}:before { content: '\\\\218'; }",
    "icon-search-thin": "{selector}:before { content: '\\\\219'; }",
    "icon-heart-empty": "{selector}:before { content: '\\\\21a'; }",
    "icon-scales": "{selector}:before { content: '\\\\21b'; }",
    "icon-sort-alpha-desc": "{selector}:before { content: '\\\\21c'; }",
    "icon-align-right": "{selector}:before { content: '\\\\21d'; }",
    "icon-stripes": "{selector}:before { content: '\\\\21e'; }",
    "icon-arrow-pointer-up": "{selector}:before { content: '\\\\21f'; }",
    "icon-round": "{selector}:before { content: '\\\\220'; }",
    "icon-user-medical": "{selector}:before { content: '\\\\221'; }",
    "icon-arrow-thin-left": "{selector}:before { content: '\\\\222'; }",
    "icon-arrow-circle-alt-right": "{selector}:before { content: '\\\\223'; }",
    "icon-starlet-alt": "{selector}:before { content: '\\\\224'; }",
    "icon-bold": "{selector}:before { content: '\\\\225'; }",
    "icon-aperture": "{selector}:before { content: '\\\\226'; }",
    "icon-pointer-basic": "{selector}:before { content: '\\\\227'; }",
    "icon-folder": "{selector}:before { content: '\\\\228'; }",
    "icon-heart": "{selector}:before { content: '\\\\229'; }",
    "icon-cloud-download": "{selector}:before { content: '\\\\22a'; }",
    "icon-bar-chart": "{selector}:before { content: '\\\\22b'; }",
    "icon-mobile": "{selector}:before { content: '\\\\22c'; }",
    "icon-volume-down": "{selector}:before { content: '\\\\22d'; }",
    "icon-exchange": "{selector}:before { content: '\\\\22e'; }",
    "icon-folder-open": "{selector}:before { content: '\\\\22f'; }",
    "icon-phone-square": "{selector}:before { content: '\\\\230'; }",
    "icon-zoom-in": "{selector}:before { content: '\\\\231'; }",
    "icon-beer": "{selector}:before { content: '\\\\232'; }",
    "icon-trello-square": "{selector}:before { content: '\\\\233'; }",
    "icon-delete": "{selector}:before { content: '\\\\234'; }",
    "icon-image": "{selector}:before { content: '\\\\235'; }",
    "icon-edit": "{selector}:before { content: '\\\\236'; }",
    "icon-twitter-square": "{selector}:before { content: '\\\\237'; }",
    "icon-external-link": "{selector}:before { content: '\\\\238'; }",
    "icon-money": "{selector}:before { content: '\\\\239'; }",
    "icon-html": "{selector}5:before { content: '\\\\23a'; }",
    "icon-youtube-play": "{selector}:before { content: '\\\\23b'; }",
    "icon-play": "{selector}:before { content: '\\\\23c'; }",
    "icon-calendar": "{selector}:before { content: '\\\\23d'; }",
    "icon-video": "{selector}:before { content: '\\\\23e'; }",
    "icon-adjust": "{selector}:before { content: '\\\\23f'; }",
    "icon-plus-circle": "{selector}:before { content: '\\\\240'; }",
    "icon-strikethrough": "{selector}:before { content: '\\\\241'; }",
    "icon-bell": "{selector}:before { content: '\\\\242'; }",
    "icon-crop": "{selector}:before { content: '\\\\243'; }",
    "icon-restore": "{selector}:before { content: '\\\\244'; }",
    "icon-circle-arrow-up": "{selector}:before { content: '\\\\245'; }",
    "icon-twitter": "{selector}:before { content: '\\\\246'; }",
    "icon-sitemap": "{selector}:before { content: '\\\\247'; }",
    "icon-facebook-square": "{selector}:before { content: '\\\\248'; }",
    "icon-downturn": "{selector}:before { content: '\\\\249'; }",
    "icon-fancy-circle-alt": "{selector}:before { content: '\\\\24a'; }",
    "icon-arrow-square-right": "{selector}:before { content: '\\\\24b'; }",
    "icon-save": "{selector}:before { content: '\\\\24c'; }",
    "icon-share-alt": "{selector}:before { content: '\\\\24d'; }",
    "icon-arrow-thick-up": "{selector}:before { content: '\\\\24e'; }",
    "icon-plus": "{selector}:before { content: '\\\\24f'; }",
    "icon-arrows-alt": "{selector}:before { content: '\\\\250'; }",
    "icon-chevron-left": "{selector}:before { content: '\\\\251'; }",
    "icon-circle-arrow-right": "{selector}:before { content: '\\\\252'; }",
    "icon-arrow-double-down": "{selector}:before { content: '\\\\253'; }",
    "icon-film": "{selector}:before { content: '\\\\254'; }",
    "icon-pie-chart": "{selector}:before { content: '\\\\255'; }",
    "icon-github": "{selector}:before { content: '\\\\256'; }",
    "icon-calendar-day-alt": "{selector}:before { content: '\\\\257'; }",
    "icon-sort-numeric": "{selector}:before { content: '\\\\258'; }",
    "icon-align-center": "{selector}:before { content: '\\\\259'; }",
    "icon-caret-down": "{selector}:before { content: '\\\\25a'; }",
    "icon-round-alt": "{selector}:before { content: '\\\\25b'; }",
    "icon-user-business": "{selector}:before { content: '\\\\25c'; }",
    "icon-signal": "{selector}:before { content: '\\\\25d'; }",
    "icon-reply-all": "{selector}:before { content: '\\\\25e'; }",
    "icon-star": "{selector}:before { content: '\\\\25f'; }",
    "icon-book": "{selector}:before { content: '\\\\260'; }",
    "icon-triangle": "{selector}:before { content: '\\\\261'; }",
    "icon-arrow-angle-right": "{selector}:before { content: '\\\\262'; }",
    "icon-arrow-basic-up": "{selector}:before { content: '\\\\263'; }",
    "icon-caret-right": "{selector}:before { content: '\\\\264'; }",
    "icon-align-left": "{selector}:before { content: '\\\\265'; }",
    "icon-comments": "{selector}:before { content: '\\\\266'; }",
    "icon-vk": "{selector}:before { content: '\\\\267'; }",
    "icon-qrcode": "{selector}:before { content: '\\\\268'; }",
    "icon-arrow-tall-right": "{selector}:before { content: '\\\\269'; }",
    "icon-shopping-cart": "{selector}:before { content: '\\\\26a'; }",
    "icon-pause": "{selector}:before { content: '\\\\26b'; }",
    "icon-umbrella": "{selector}:before { content: '\\\\26c'; }",
    "icon-ban": "{selector}:before { content: '\\\\26d'; }",
    "icon-plane-alt": "{selector}:before { content: '\\\\26e'; }",
    "icon-ticklet-circle-alt": "{selector}:before { content: '\\\\26f'; }",
    "icon-arrow-angle-left": "{selector}:before { content: '\\\\270'; }",
    "icon-android": "{selector}:before { content: '\\\\271'; }",
    "icon-arrow-square-up": "{selector}:before { content: '\\\\272'; }",
    "icon-inr": "{selector}:before { content: '\\\\273'; }",
    "icon-label": "{selector}:before { content: '\\\\274'; }",
    "icon-spinner": "{selector}:before { content: '\\\\275'; }",
    "icon-headphones": "{selector}:before { content: '\\\\276'; }",
    "icon-arrow-fancy": "{selector}:before { content: '\\\\277'; }",
    "icon-sort-desc": "{selector}:before { content: '\\\\278'; }",
    "icon-tick-circle": "{selector}:before { content: '\\\\279'; }",
    "icon-info-sign": "{selector}:before { content: '\\\\27a'; }",
    "icon-screenshot": "{selector}:before { content: '\\\\27b'; }",
    "icon-briefcase-simple": "{selector}:before { content: '\\\\27c'; }",
    "icon-search-alt": "{selector}:before { content: '\\\\27d'; }",
    "icon-time": "{selector}:before { content: '\\\\27e'; }",
    "icon-grid": "{selector}:before { content: '\\\\27f'; }",
    "icon-user": "{selector}:before { content: '\\\\280'; }",
    "icon-facebook": "{selector}:before { content: '\\\\281'; }",
    "icon-google-plus": "{selector}:before { content: '\\\\282'; }",
    "icon-github-square": "{selector}:before { content: '\\\\283'; }",
    "icon-check-empty": "{selector}:before { content: '\\\\284'; }",
    "icon-circle": "{selector}:before { content: '\\\\285'; }",
    "icon-fast-backward": "{selector}:before { content: '\\\\286'; }",
    "icon-calendar-day": "{selector}:before { content: '\\\\287'; }",
    "icon-phone": "{selector}:before { content: '\\\\288'; }",
    "icon-pinterest-square": "{selector}:before { content: '\\\\289'; }",
    "icon-cup": "{selector}:before { content: '\\\\28a'; }",
    "icon-star-thin": "{selector}:before { content: '\\\\28b'; }",
    "icon-wrench": "{selector}:before { content: '\\\\28c'; }",
    "icon-truck": "{selector}:before { content: '\\\\28d'; }",
    "icon-product-view-mode": "{selector}:before { content: '\\\\28e'; }",
    "icon-circle-arrow-down": "{selector}:before { content: '\\\\28f'; }",
    "icon-arrow-circle-alt-left": "{selector}:before { content: '\\\\290'; }",
    "icon-stackexchange": "{selector}:before { content: '\\\\291'; }",
    "icon-ticklet-thick": "{selector}:before { content: '\\\\292'; }",
    "icon-arrow-thin-right": "{selector}:before { content: '\\\\293'; }",
    "icon-tick": "{selector}:before { content: '\\\\294'; }",
    "icon-box-small-alt": "{selector}:before { content: '\\\\295'; }",
    "icon-file-alt": "{selector}:before { content: '\\\\296'; }",
    "icon-minus": "{selector}:before { content: '\\\\297'; }",
    "icon-upload-circle": "{selector}:before { content: '\\\\298'; }",
    "icon-gift": "{selector}:before { content: '\\\\299'; }",
    "icon-globe-outline": "{selector}:before { content: '\\\\29a'; }",
    "icon-windows": "{selector}:before { content: '\\\\29b'; }",
    "icon-arrow-line": "{selector}:before { content: '\\\\29c'; }",
    "icon-flag-alt": "{selector}:before { content: '\\\\29d'; }",
    "icon-home": "{selector}:before { content: '\\\\29e'; }",
    "icon-arrow-circle-alt-down": "{selector}:before { content: '\\\\29f'; }",
    "icon-dollar": "{selector}:before { content: '\\\\2a0'; }",
    "icon-double-chevron-left": "{selector}:before { content: '\\\\2a1'; }",
    "icon-arrow-angle-down": "{selector}:before { content: '\\\\2a2'; }"
}
EOT
);
?>
<?php

// IconLink
function themler_shortcode_icon($atts, $content = '') {
    $atts = ShortcodesUtility::atts(array(
        'link' => '',
        'title' => '',
        'target' => '',

        'icon' => '',
        'picture' => '',
        'icon_hovered' => '',
        'picture_hovered' => '',
    ), $atts, array('', 'icon_', 'icon_hovered_'));

    $link = ShortcodesUtility::escape($atts['link']);
    $title = ShortcodesUtility::escape($atts['title']);
    $target = ShortcodesUtility::escape($atts['target']);
    $icon_passive = ShortcodesUtility::escape($atts['icon']);
    $picture_passive = ShortcodesUtility::escape($atts['picture']);
    $icon_hovered = ShortcodesUtility::escape($atts['icon_hovered']);
    $picture_hovered = ShortcodesUtility::escape($atts['picture_hovered']);


    $sid = ShortcodesEffects::init('', $atts);
    list($style_tag, $additional_classes, $selector) = ShortcodesEffects::css($sid, $atts);

    $classes = array();
    $classes[] = $additional_classes;
    $icon_class = '';
    if ($icon_passive && $icon_passive !== 'none' || $picture_passive ||
        $icon_hovered && $icon_hovered !== 'none' || $picture_hovered) {

        $icon_class = 'bd-icon';
    }

    $icon_passive_style_tag = themler_shortcodes_icon_state_style($sid, array(
        'picture' => $picture_passive,
        'icon' => $icon_passive,
        'selector' => $selector . ($link ? ' span' : ''),
        'atts' => $atts,
        'icon_prefix' => 'icon_'
    ));
    $icon_hovered_style_tag = themler_shortcodes_icon_state_style($sid, array(
        'picture' => $picture_hovered,
        'icon' => $icon_hovered,
        'selector' => $selector . ($link ? ' span' : '') . ':hover',
        'atts' => $atts,
        'icon_prefix' => 'icon_hovered_'
    ));

    if ($link) {
        $html_atts = array();
        if ($title)
            $html_atts[] = 'title="' . $title . '"';
        if ($target)
            $html_atts[] = 'target="' . $target . '"';
        $html_atts[] = 'href="' . $link . '"';
        $html_atts[] = 'class="' . ($icon_class ? 'bd-iconlink ' : '') . implode(' ', $classes) . '"';

        $content = '<a ' . implode(' ', $html_atts) . '>' .
            '<span class="' . $icon_class . '"><!--{content}--><!--{/content}--></span></a>';
    } else {
        $content = '<span class="' . $icon_class . ' ' . implode(' ', $classes) .
            '"><!--{content}--><!--{/content}--></span>';
    }

    return
        '<!--[icon]-->' .
            $style_tag . $icon_passive_style_tag . $icon_hovered_style_tag .
            $content .
        '<!--[/icon]-->';
}
ShortcodesUtility::addShortcode('icon', 'themler_shortcode_icon');
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Image', "imagestyles-17", "bd-imagestyles-17", "bd-imagestyles-17-mixin");
?>
<?php

// ImageLink
function themler_shortcode_image($atts, $content = '', $tag = '', $parent = array()) {
    if ($parent) {
        $id = $parent['id'];
        $prefix = $parent['prefix'];
    } else {
        $atts = ShortcodesUtility::atts(array(
            'image' => '',
            'href' => '',
            'link' => '',
            'target' => '',
            'screen_tip' => '',
            'alt' => '',
            'image_style' => ''
        ), $atts);
        $id = ShortcodesEffects::init('', $atts);
        $prefix = '';
    }

    $image = ShortcodesUtility::escape($atts[$prefix . 'image']);

    // w/o prefix
    $href = ShortcodesUtility::escape(empty($atts['href']) ? '' : $atts['href']);
    if (!$href) {
        $href = ShortcodesUtility::escape(empty($atts['link']) ? '' : $atts['link']);
    }
    $target = ShortcodesUtility::escape(empty($atts['target']) ? '' : $atts['target']);
    $screen_tip = ShortcodesUtility::escape(empty($atts['screen_tip']) ? '' : $atts['screen_tip']);
    $alt = ShortcodesUtility::escape(empty($atts['alt']) ? '' : $atts['alt']);

    $additionalClass = empty($parent['additionalClass']) ? '' : $parent['additionalClass'];
    $additionalImageAtts = empty($parent['additionalImageAtts']) ? '' : $parent['additionalImageAtts'];

    list($style_tag, $controlClass, $selector) = ShortcodesEffects::css($id, $atts, $prefix);

    $additional_image_style_class = ShortcodesStyles::getStyleClassname('Image', $atts[$prefix . 'image_style']);
    $class = substr($selector, 1);

    if ($image && $href) {
        list($style_image_tag1,) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, 'positioning,transform,margin,float'), $prefix, '{selector}', $class);
        list($style_image_tag2,) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, 'size'), $prefix, '{selector}', $class);
        list($style_image_tag3,) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, '!positioning,!transform,!size,!margin,!float'), $prefix, '{selector}', $class);

        $style_tag = "
            <style>
                $selector {
                    display: inline-block;
                }
            </style>
            $style_image_tag1
            $style_image_tag2

            <style>
                $selector img {
                    display: inline-block;
                    width: 100%;
                    height: 100%;
                }
            </style>
            $style_image_tag3
            ";
    }

    $a_atts = array();
    $img_atts = array();

    $img_atts[] = 'src="' . $image . '"';
    if ($alt)
        $img_atts[] = 'alt="' . $alt . '"';

    if ($href) {
        if ($target)
            $a_atts[] = 'target="' . $target . '"';
        if ($screen_tip)
            $a_atts[] = 'title="' . $screen_tip . '"';

        $a_atts[] = 'href="' . $href . '"';
        $a_atts[] = 'class="' . $controlClass . ' ' . $additionalClass . '"';
        $img_atts[] = 'class="' . $additional_image_style_class .'"';
        $img_atts[] = $additionalImageAtts;

        $content = '<a ' . implode(' ', $a_atts) . '>' .
                '<img ' . implode(' ', $img_atts) . '>' .
            '<!--{content}--><!--{/content}--></a>';
    } else {
        $img_atts[] = 'class="' . $controlClass . ' ' . $additionalClass . ' ' . $additional_image_style_class .'"';
        $content = '<img ' . implode(' ', $img_atts) . '><!--{content}--><!--{/content}-->';
    }

    return $parent ? array('html' => $content, 'css' => $style_tag) :
        "<!--[$tag]-->" .
            $style_tag .
            $content .
        "<!--[/$tag]-->";
}
ShortcodesUtility::addShortcode('image', 'themler_shortcode_image');
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Image', "", "bd-imagestyles", "bd-imagestyles-1-mixin");
?>
<?php

// ImageScaling
function themler_shortcode_image_scaling($atts, $content = '', $tag) {
    $atts = ShortcodesUtility::atts(array(), $atts);

    $id = ShortcodesEffects::init('bd-imagescaling', $atts, ShortcodesEffects::CSS_EFFECT);
    $content = ShortcodesUtility::doShortcode($content);

    list($style_css, $additional_classes, $selector) = ShortcodesEffects::css(
        $id, $atts, '',
        '{selector}.bd-imagescaling-img, {selector} .bd-imagescaling-img'
    );

    $target_control = ShortcodesEffects::target_control($id);

    $content = ShortcodesEffects::addClassesAndAttrs(
        $content,
        $target_control,
        array('bd-imagescaling', $additional_classes),
        array()
    );

    ob_start();
?>
    <!--[<?php echo $tag ?>]-->
        <?php echo $style_css; ?>
        <!--{content}-->
            <?php echo $content ?>
        <!--{/content}-->
    <!--[/<?php echo $tag ?>]-->
<?php
    return ob_get_clean();
}

ShortcodesUtility::addShortcode('image_scaling', 'themler_shortcode_image_scaling');
?>
<?php
ThemeShortcodesStyles::putStyleClassname('inputs', "", "bd-bootstrapinput form-control", "bd-bootstrapinput-1-mixin");
?>
<?php

// LayoutBox
function themler_shortcode_layoutbox($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(), $atts);

    $id = ShortcodesEffects::init('', $atts);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class) = ShortcodesEffects::css($id, $atts);

    return "<!--[$tag]-->" . $style_tag .
                '<div class="clearfix ' . $additional_class . '">' .
                    '<div class="bd-container-inner">' .
                        '<!--{content}-->' .
                            $content .
                        '<!--{/content}-->' .
                    '</div>' .
                '</div>' .
            "<!--[/$tag]-->";
}

ShortcodesUtility::addShortcode('layoutbox', 'themler_shortcode_layoutbox');
?>
<?php

// LayoutColumn
function themler_shortcode_column($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(
        'width_lg' => '',
        'width' => '',
        'width_sm' => '',
        'width_xs' => ''
    ), $atts);

    $col_classes = array();
    if (intval($atts['width_lg']) > 0) {
        $col_classes[] = 'col-lg-' . $atts['width_lg'];
    }
    if (intval($atts['width']) > 0) {
        $col_classes[] = 'col-md-' . $atts['width'];
    }
    if (intval($atts['width_sm']) > 0) {
        $col_classes[] = 'col-sm-' . $atts['width_sm'];
    }
    if (intval($atts['width_xs']) > 0) {
        $col_classes[] = 'col-xs-' . $atts['width_xs'];
    }
    $col_classes = array_merge($col_classes, ShortcodesEffects::hidden_classes($atts));

    $id = ShortcodesEffects::init('bd-layoutcolumn', $atts);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class, $selector) = ShortcodesEffects::css(
        $id, ShortcodesEffects::filter($atts, '!order')
    );

    $order_class = preg_replace('/[^\d]+(\d+)/', 'bd-columnwrapper-$1', $selector);
    $order_tag = ShortcodesEffects::print_all_css(
        ShortcodesEffects::filter($atts, 'order'), 'css', '.' . $order_class
    );
    if (trim($order_tag)) {
        $order_tag = '<style>' . $order_tag . '</style>';
    }
    $col_classes[] = $order_class;

    $style_tag = $style_tag . $order_tag;

    ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
        <div class="<?php echo implode(' ', $col_classes); ?>">
            <div class="bd-column <?php echo $additional_class ?>">
                <div class="bd-vertical-align-wrapper">
                    <!--{content}-->
                        <?php echo $content; ?>
                    <!--{/content}-->
                </div>
            </div>
        </div>
    <!--[/<?php echo $tag; ?>]-->
<?php
    return ShortcodesUtility::addResult(ob_get_clean(), $style_tag);
}

ShortcodesUtility::addShortcode('column', 'themler_shortcode_column');
?>
<?php

// LayoutContainer
function themler_shortcode_columns($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(
        'collapse_spacing' => '0',
        'auto_height' => '',
        'vertical_align' => 'top',
        'spacing_css' => ''
    ), $atts);

    $id = ShortcodesEffects::init('bd-layoutcontainer', $atts);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class, $selector) = ShortcodesEffects::css($id, $atts);

    $row_classes = array('bd-columns');
    $classes = array($additional_class);

    if (ShortcodesUtility::getBool($atts['collapse_spacing'])) {
        $row_classes[] = 'bd-collapsed-gutter';
    } else {
        $rowAtts = ShortcodesEffects::filter($atts, 'margin,height', 'spacing_');
        $colAtts = ShortcodesEffects::filter($atts, 'padding', 'spacing_');

        $rowStyle = ShortcodesEffects::print_all_css(
            $rowAtts,
            'spacing_css',
            $selector . ' > .bd-container-inner > .container-fluid > .row'
        );

        $colStyle = ShortcodesEffects::print_all_css(
            $colAtts,
            'spacing_css',
            $selector . ' > .bd-container-inner > .container-fluid > .row > div'
        );

        $containerStyle = ShortcodesEffects::print_all_css(
            array('spacing_css' => 'display:none;'),
            'spacing_css',
            $selector . '  > .bd-container-inner > .container-fluid:after'
        );

        $style_tag .= '<style>' . $rowStyle . $colStyle . $containerStyle . '</style>';
    }

    if (ShortcodesUtility::getBool($atts['auto_height'])) {
        $row_classes[] = 'bd-columns-flex';

        if($atts['vertical_align']) {
            $row_classes[] = 'bd-columns-align-' . $atts['vertical_align'];
        }

        $heightStyle = ShortcodesEffects::print_all_css(
            array('css' => 'height:100%;'),
            'css',
            $selector . ' > .bd-container-inner > .container-fluid, ' .
            $selector . ' > .bd-container-inner > .container-fluid > .row'
        );

        $style_tag .= '<style>' . $heightStyle . '</style>';
    }

    list($content, $inner_styles) = ShortcodesUtility::processResult($content);
    ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
        <?php echo $style_tag; ?>
        <?php echo $inner_styles; ?>
        <div class="<?php echo implode(' ', $classes); ?> <?php echo implode(' ', $row_classes); ?>">
            <div class="bd-container-inner">
                <div class="container-fluid">
                    <div class="row">
                        <!--{content}-->
                            <?php echo $content; ?>
                        <!--{/content}-->
                    </div>
                </div>
            </div>
        </div>
    <!--[/<?php echo $tag; ?>]-->
<?php
    return ob_get_clean();
}

ShortcodesUtility::addShortcode('row', 'themler_shortcode_columns');
ShortcodesUtility::addShortcode('columns', 'themler_shortcode_columns');
?>
<?php
ThemeShortcodesStyles::putStyleClassname('OrderedList', "", "bd-orderedlist", "bd-orderedlist-1-mixin");
?>
<?php

// Parallax Background
function themler_shortcode_parallax_background($atts, $content='', $tag) {
    $atts = ShortcodesUtility::atts(array(), $atts);

    $id = ShortcodesEffects::init('bd-parallax-bg', $atts, ShortcodesEffects::HTML_EFFECT);
    $content = ShortcodesUtility::doShortcode($content);

    list($style_css, $additional_classes, $selector) = ShortcodesEffects::css($id, $atts);

    $target_control = ShortcodesEffects::target_control($id);
    $target_selector =  $target_control['selector'];

    $style_css .= "<style>.bd-parallax-bg-effect > $target_selector {
            background-color: transparent;
            z-index: 0;
        }</style>";

    ob_start();
?>
    <!--[<?php echo $tag ?>]-->
        <?php echo $style_css; ?>
        <div class="<?php echo $additional_classes ?> bd-parallax-bg-effect" data-control-selector="<?php echo $target_selector ?>">
            <!--{content}-->
                <?php echo $content ?>
            <!--{/content}-->
        </div>
    <!--[/<?php echo $tag ?>]-->
<?php
    return ob_get_clean();
}

ShortcodesUtility::addShortcode('parallax_background', 'themler_shortcode_parallax_background');
?>
<?php

// Ribbon
function themler_shortcode_ribbon($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(
        'end_size' => '',
        'ribbon_fold_color' => '',
        'ribbon_end_color' => '',
        'stitching' => true,
        'stitching_color' => '',
        'shadow' => false
    ), $atts, array('', 'container_'));

    $id = ShortcodesEffects::init('', $atts);

    $ribbon_fold_color = ShortcodesUtility::escape($atts['ribbon_fold_color']);
    $ribbon_end_color = ShortcodesUtility::escape($atts['ribbon_end_color']);
    $stitches_size = ShortcodesUtility::getBool($atts['stitching']) ? 1 : 0;
    $stitching_color = ShortcodesUtility::escape($atts['stitching_color']);
    $end_size = $atts['end_size'] ? ShortcodesUtility::escape($atts['end_size']) : '0px';

    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class, $selector) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, '!background'));
    list($background_style_tag, ) = ShortcodesEffects::css($id, ShortcodesEffects::filter($atts, 'background'), '', "$selector .ribbon-inner");

    list($container_style_tag, $container_additional_class, $container_selector) = ShortcodesEffects::css($id, $atts, 'container_');

    $classes = array($additional_class, 'bd-ribbon', 'bd-ribbon-core');
    if (ShortcodesUtility::getBool($atts['shadow']))
        $classes[] = 'ribbon-shadow';

    $style_tag = "
        $style_tag
        <style>
            $selector {
                font-size: $end_size !important;
            }

            $selector .ribbon-inner:before,
            $selector .ribbon-inner:after {
                border: 1.5em solid $ribbon_end_color;
            }

            $selector .ribbon-inner .ribbon-content:before,
            $selector .ribbon-inner .ribbon-content:after {
                border-color: $ribbon_fold_color transparent transparent transparent;
            }

            $selector .ribbon-inner .ribbon-stitches-top {
                border-top: {$stitches_size}px dashed $stitching_color;
                top: 2px;
            }

            $selector .ribbon-inner .ribbon-stitches-bottom {
                border-top: {$stitches_size}px dashed $stitching_color;
                bottom: 2px;
            }

            $selector .ribbon-inner:before {
                border-left-color: transparent;
                border-right-width: 1.5em;
            }

            $selector .ribbon-inner:after {
                border-left-width: 1.5em;
                border-right-color: transparent;
            }
        </style>";

    return "<!--[$tag]-->" . $style_tag . $background_style_tag . $container_style_tag .
                '<div class="'.implode(' ', $classes).'">' .
                    '<div class="ribbon-inner">' .
                        '<div class="ribbon-stitches-top"></div>' .
                        '<strong class="ribbon-content">' .
                            '<div class="' . substr($container_selector, 1) . ' bd-content-element">' .
                                '<!--{content}-->' .
                                    $content .
                                '<!--{/content}-->' .
                             '</div>' .
                        '</strong>' .
                        '<div class="ribbon-stitches-bottom"></div>' .
                    '</div>' .
                '</div>' .
            "<!--[/$tag]-->";
}

ShortcodesUtility::addShortcode('ribbon', 'themler_shortcode_ribbon');
?>
<?php

// Section
function themler_shortcode_section($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(ShortcodesEffects::tagsStylesAtts(array(
        'title' => '',
        'id' => '',
	)), $atts);

    $sect_id = $atts['id'];
    $title = $atts['title'];

    $id = ShortcodesEffects::init('bd-section', $atts);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class) = ShortcodesEffects::css($id, $atts);

	ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
		<?php echo $style_tag; ?>
		<section<?php if ($sect_id) echo " id=$sect_id"; ?> class="<?php echo $additional_class; ?> bd-tagstyles" data-section-title="<?php echo $title; ?>">
			<div class="bd-section-inner">
				<div class="bd-section-align-wrapper">
					<!--{content}-->
						<?php echo $content; ?>
					<!--{/content}-->
				</div>
			</div>
		</section>
	<!--[/<?php echo $tag; ?>]-->
<?php
	return ob_get_clean();
}

ShortcodesUtility::addShortcode('section', 'themler_shortcode_section');
?>
<?php

// Separator
function themler_shortcode_separator($atts, $imageHtml = '', $tag) {
    $atts = ShortcodesUtility::atts(
        array(
            'content_type' => 'none',
            'text' => '',
            'text_tag' => 'span',
            'align' => 'center',
            'content_align' => 'center',

            'href' => '',
            'target' => '',
            'screen_tip' => '',
            'alt' => '',

            'image' => '',
            'image_style' => '',

            'button_type' => 'default',
            'button_style' => '',
            'button_size' => '',
            'button_icon' => '',
            'button_picture' => '',
            'button_icon_hovered' => '',
            'button_picture_hovered' => ''
        ),
        $atts,
        array('', 'image_', 'content_', 'line_', 'button_', 'button_icon_', 'button_icon_hovered_')
    );

    // hack with prefix
    $atts['image_image'] = $atts['image'];
    $atts['image_image_style'] = $atts['image_style'];

    $id = ShortcodesEffects::init('bd-separator', $atts, true);
    list($controlStyles, $controlClasses, $controlSelector) = ShortcodesEffects::css($id, $atts);

    $controlClasses .= ' bd-separator-' . $atts['align'];
    $controlClasses .= ' bd-separator-content-' . $atts['content_align'];

    $contentHtml = '';
    $innerContentStyles = '';
    switch ($atts['content_type']) {
        case 'text':
            $contentHtml = "<${atts['text_tag']} class=\"bd-content-element\">" . $atts['text'] . "</${atts['text_tag']}>";
            break;
        case 'image':
            $contentResult = themler_shortcode_image($atts, '', '', array(
                'id' => $id,
                'prefix' => 'image_'
            ));
            $contentHtml = $contentResult['html'];
            $innerContentStyles = $contentResult['css'];
            break;
        case 'button':
            $contentResult = themler_shortcode_button($atts, $atts['text'], '', array(
                'id' => $id,
                'prefix' => 'button_'
            ));
            $contentHtml = $contentResult['html'];
            $innerContentStyles = $contentResult['css'];
            break;
    }

    $contentHtml = preg_replace('/<!--[\s\S]*?-->/', '', $contentHtml);

    list($contentStyles, $contentClass, ) = ShortcodesEffects::css($id, $atts, 'content_');
    list($lineStyles, ) = ShortcodesEffects::css(
        $id, ShortcodesEffects::filter($atts, '!width', 'line_'), 'line_',
        '{selector} .bd-separator-inner:before, {selector} .bd-separator-inner:after',
        str_replace('.', '', $controlSelector)
    );
    list($lineWidthStyles, ) = ShortcodesEffects::css(
        $id, ShortcodesEffects::filter($atts, 'width', 'line_'), 'line_',
        '{selector} .bd-separator-inner',
        str_replace('.', '', $controlSelector)
    );

    ob_start();
    ?>

    <!--[<?php echo $tag ?>]-->
    <?php echo $controlStyles ?>
    <?php if ($atts['content_type'] !== 'none'): ?>
        <?php echo $innerContentStyles ?>
        <?php echo $contentStyles ?>
    <?php endif ?>
    <?php echo $lineStyles ?>
    <?php echo $lineWidthStyles ?>

    <div class="<?php echo $controlClasses ?> clearfix">
        <div class="bd-container-inner">
            <div class="bd-separator-inner">
                <?php if ($atts['content_type'] !== 'none'): ?>
                    <div class="<?php echo $contentClass ?> bd-separator-content">
                        <?php echo $contentHtml ?>
                    </div>
                <?php endif ?>
                <!--{content}--><!--{/content}-->
            </div>
        </div>
    </div>
    <!--[/<?php echo $tag ?>]-->

    <?php
    return ob_get_clean();
}

ShortcodesUtility::addShortcode('separator', 'themler_shortcode_separator');
?>
<?php

// Slide
function themler_shortcode_slide($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(
        'link' => '',
        'linktarget' => '',
        'title' => ''
    ), $atts);

    $link = ShortcodesUtility::escape($atts['link']);
    $linktarget = ShortcodesUtility::escape($atts['linktarget']);
    $title = ShortcodesUtility::escape($atts['title']);

    $id = ShortcodesEffects::init('', $atts);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class) = ShortcodesEffects::css($id, $atts);

    $additional_class .= ' bd-slide item';

    global $theme_slides_count;
    if (!is_array($theme_slides_count) || !count($theme_slides_count)) {
        $theme_slides_count = array(0);
    }

    if ($theme_slides_count[count($theme_slides_count) - 1] === 0) {
        $additional_class .= ' active';
    }
    $theme_slides_count[count($theme_slides_count) - 1]++;

    ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
        <div class="<?php echo $additional_class; ?>"
             <?php if ($link) echo 'data-url="' . $link . '"'; ?>
             <?php if ($linktarget) echo 'data-target="' . $linktarget . '"'; ?>
             <?php if ($title) echo 'data-title="' . $title . '"'; ?>>
            <div class="bd-container-inner">
                <div class="bd-container-inner-wrapper">
                    <!--{content}-->
                        <?php echo $content; ?>
                    <!--{/content}-->
                </div>
            </div>
        </div>
    <!--[/<?php echo $tag; ?>]-->
<?php
    return ShortcodesUtility::addResult(ob_get_clean(), $style_tag);
}
ShortcodesUtility::addShortcode('slide', 'themler_shortcode_slide');
?>
<?php

// Slider
function themler_shortcode_slider($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(
        // navigator (carousel):
        'interval' => 3000,
        'pause_on_hover' => 'yes',
        'navigator_wrap' => 'yes',
        'ride_on_start' => 'yes',
        'vertical_items' => 'no',
        'vertical_carousel' => 'no',
        'animation' => 'left',
        'navigator_style' => '',

        // indicators:
        'indicators_style' => '',

        // slider:
        'show_navigator' => 'yes',
        'show_indicators' => 'no',
        'indicators_wide' => 'no',
        'navigator_wide' => 'no',
        'slides_wide' => 'no',
    ), $atts, array('', 'indicators_', 'navigator_'));

    $vertical_items = ShortcodesUtility::getBool($atts['vertical_items']);
    $vertical_carousel = ShortcodesUtility::getBool($atts['vertical_carousel']);
    $show_indicators = ShortcodesUtility::getBool($atts['show_indicators']);
    $show_navigator = ShortcodesUtility::getBool($atts['show_navigator']);
    $indicators_wide = ShortcodesUtility::getBool($atts['indicators_wide']);
    $navigator_wide = ShortcodesUtility::getBool($atts['navigator_wide']);
    $slides_wide = ShortcodesUtility::getBool($atts['slides_wide']);
    $interval = $atts['interval'];
    $pause_on_hover = ShortcodesUtility::getBool($atts['pause_on_hover']) ? 'hover' : '';
    $navigator_wrap = ShortcodesUtility::getBool($atts['navigator_wrap']) ? 'true' : 'false';
    $ride_on_start = ShortcodesUtility::getBool($atts['ride_on_start']) ? 'true' : 'false';
    $animation = ShortcodesUtility::escape($atts['animation']);

    $id = uniqid('carousel-');

    global $theme_slides_count;
    if (!is_array($theme_slides_count))
        $theme_slides_count = array();
    $theme_slides_count[] = 0;

    $sid = ShortcodesEffects::init('', $atts);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class, $selector) = ShortcodesEffects::css($sid, $atts);
    $class = substr($selector, 1);

    list($navigator_style_tag, ) = ShortcodesEffects::css($sid, $atts, 'navigator_', "$selector .carousel-inner > .item");
    list($indicators_style_tag, $indicators_additional_class, $indicators_selector) = ShortcodesEffects::css($sid, $atts, 'indicators_');
    $navigator_class = ShortcodesStyles::getStyleClassname('Carousel', $atts['navigator_style']);
    $indicators_class = ShortcodesStyles::getStyleClassname('Indicators', $atts['indicators_style']);

    $slides_count = $theme_slides_count[count($theme_slides_count) - 1];
    $content = '<!--{content}-->' . $content . '<!--{/content}-->';
    $indicators = '<div class="bd-slider-indicators ' . $indicators_additional_class . '"><ol class="' . $indicators_class . '">';
    for($i = 0; $i < $slides_count; $i++) {
        $indicators .= '<li>'
            .'<a class="' . (0 === $i ? ' active' : '') . '" href="#" data-target="#' . $id . '" data-slide-to="' . $i . '"></a>'
            .'</li> '; // note: indicators are space-separated
    }
    $indicators .= '</ol></div>';
    $navigator = <<<EOL
<div class="bd-left-button">
    <a class="$navigator_class">
        <span class="bd-icon"></span>
    </a>
</div>
<div class="bd-right-button">
    <a class="$navigator_class">
        <span class="bd-icon"></span>
    </a>
</div>
EOL;
    $result = '';
    if ($indicators_wide && $show_indicators) {
        $result .= $indicators;
    }

    if (!$slides_wide) {
        $result .= '<div class="bd-container-inner">';
    }

    if (!$indicators_wide && $show_indicators) {
        $result .= $indicators;
    }

    list($content, $inner_styles) = ShortcodesUtility::processResult($content);
    $result .= '<div class="bd-slides carousel-inner">' . $content . '</div>';

    if (!$navigator_wide && $show_navigator) {
        $result .= $navigator;
    }

    if (!$slides_wide) {
        $result .= '</div>';
    }

    if ($navigator_wide && $show_navigator){
        $result .= $navigator;
    }

    $_indicators_atts = ShortcodesEffects::filter($atts, 'vertical-align', 'indicators_');
    $indicators_style_tag .= "<style>$indicators_selector:before{" . $_indicators_atts['indicators_css'] . "}</style>";

    if ($animation) {
        $additional_class .= " bd-carousel-$animation";
    }

    list($border_radius_style, ) = ShortcodesEffects::css($sid, ShortcodesEffects::filter($atts, 'border-radius'), '',
        '{selector} [class*="bd-slide"], {selector} .carousel-inner', $class);

    $style_tag = $style_tag . "\n" . $border_radius_style;

    if ($vertical_items) {
        $additional_class .= ' bd-vertical-items';

        if ($vertical_carousel) {
            $additional_class .= ' bd-vertical-arrows';
        }
    }

    if (($duration = ShortcodesEffects::css_prop($atts, 'transition-duration', '', 'navigator_')) &&
            preg_match('/(\d+)(\w*)$/', $duration, $matches)) {

        $durationValue = (int) $matches[1];
        $durationMultiplier = $matches[2] === 's' ? 1000 : 1;

        $interval = $durationValue * $durationMultiplier + (int) $interval;
    }

    ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
        <?php echo $style_tag . ((!$indicators_wide && $show_indicators) ? $indicators_style_tag : '') . $navigator_style_tag; ?>
        <?php echo $inner_styles; ?>
        <div id="<?php echo $id; ?>" class="bd-slider <?php echo $additional_class; ?> carousel slide">
            <?php echo $result; ?>
            <script type="text/javascript">
                if ('undefined' !== typeof initSlider) {
                    initSlider(
                        '#<?php echo $id; ?>',
                        {
                            leftButtonSelector: 'bd-left-button',
                            rightButtonSelector: 'bd-right-button',
                            navigatorSelector: '<?php echo ($navigator_class ? '.' . $navigator_class : ''); ?>',
                            indicatorsSelector: '<?php echo ($indicators_class ? '.' . $indicators_class : ''); ?>',
                            carouselInterval: <?php echo $interval; ?>,
                            carouselPause: '<?php echo $pause_on_hover; ?>',
                            carouselWrap: <?php echo $navigator_wrap; ?>,
                            carouselRideOnStart: <?php echo $ride_on_start; ?>
                        }
                    );
                }
            </script>
        </div>
    <!--[/<?php echo $tag; ?>]-->
<?php
    return ob_get_clean();
}
ShortcodesUtility::addShortcode('slider', 'themler_shortcode_slider');
?>
<?php

// SocialIcon
function themler_shortcode_social_icon($atts, $content, $tag) {
    $atts = ShortcodesUtility::atts(array(
        'type' => '',
        'permalink_url' => '',
        'share_title' => '',
        'share_image_url' => '',
        'share_type' => 'static',

        'picture' => '',
        'icon' => '',
    ), $atts, array('', 'icon_'));

    $permalink_url = ShortcodesUtility::escape($atts['permalink_url']);
    $share_title = ShortcodesUtility::escape($atts['share_title']);
    $share_image_url = ShortcodesUtility::escape($atts['share_image_url']);
    $share_type = ShortcodesUtility::escape($atts['share_type']);
    $picture = ShortcodesUtility::escape($atts['picture']);
    $icon = ShortcodesUtility::escape($atts['icon']);
    $type = ShortcodesUtility::escape($atts['type']);

    if ($share_type === 'dynamic' && function_exists('themler_get_page_url')) {
        $permalink_url = themler_get_page_url();
        $share_title = themler_get_page_title();
    }

    $permalink_url = urlencode($permalink_url);
    $share_title = urlencode($share_title);
    $share_image_url = urlencode($share_image_url);

    $url_patterns = array(
        'fb' => '//www.facebook.com/sharer.php?u={permalinkURL}',
        'tw' => '//twitter.com/share?url={permalinkURL}&amp;text={shareTitle}',
        'gl' => '//plus.google.com/share?url={permalinkURL}',
        'pi' => '//pinterest.com/pin/create/button/?url={permalinkURL}&amp;media={shareImageUrl}&amp;description={shareTitle}',
        'li' => '//linkedin.com/shareArticle?title={shareTitle}&amp;mini=true&amp;url={permalinkURL}',
        'in' => '//instagram.com/{permalinkURL}',
        'dr' => '//dribbble.com/{permalinkURL}',
        'tm' => '//{permalinkURL}.tumblr.com',
        'fl' => '//flickr.com/photos/{permalinkURL}',
        'be' => '//behance.net/{permalinkURL}'
    );

    $url_pattern = isset($url_patterns[$type]) ? $url_patterns[$type] : '';
    $url = str_replace(array('{permalinkURL}', '{shareTitle}', '{shareImageUrl}'), array($permalink_url, $share_title, $share_image_url), $url_pattern);

    $id = ShortcodesEffects::init('', $atts);
    list($style_tag, $additional_classes, $selector) = ShortcodesEffects::css($id, $atts);
    $additional_classes .= ' bd-socialicon';

    $style_tag .= "
        <style>$selector {
            float: left;
        }</style>";

    $style_tag .= themler_shortcodes_icon_state_style($id, array(
        'picture' => $picture,
        'icon' => $icon,
        'selector' => "$selector span:first-child",
        'atts' => $atts,
        'icon_prefix' => 'icon_'
    ));

    ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
        <?php echo $style_tag; ?>
        <a target="_blank" class="<?php echo $additional_classes; ?>" href= "<?php echo $url; ?>">
            <span class="bd-icon"></span><span><!--{content}--><?php echo $content; ?><!--{/content}--></span>
        </a>
    <!--[/<?php echo $tag; ?>]-->
<?php
    return ob_get_clean();
}
ShortcodesUtility::addShortcode('social_icon', 'themler_shortcode_social_icon');

// SocialIcons
function themler_shortcode_social_icons($atts, $content, $tag) {
    $atts = ShortcodesUtility::atts(array(

    ), $atts, array('', 'passive_', 'hovered_'));

    $id = ShortcodesEffects::init('', $atts);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_class, $selector) = ShortcodesEffects::css($id, $atts);

    list($passive_style,) = ShortcodesEffects::css($id, $atts, 'passive_', "$selector .bd-socialicon");
    if (!ShortcodesEffects::css_prop($atts, 'background-image', '', 'passive_') && ShortcodesEffects::css_prop($atts, 'background-color', '', 'passive_')) {
        $passive_style .= "<style>$selector .bd-socialicon {background-image: none;}</style>";
    }

    list($hovered_style,) = ShortcodesEffects::css($id, $atts, 'hovered_', "$selector .bd-socialicon:hover");
    if (!ShortcodesEffects::css_prop($atts, 'background-image', '', 'hovered_') && ShortcodesEffects::css_prop($atts, 'background-color', '', 'hovered_')) {
        $hovered_style .= "<style>$selector .bd-socialicon:hover {background-image: none;}</style>";
    }

    ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
        <?php echo $style_tag . $passive_style . $hovered_style; ?>
        <div class="<?php echo $additional_class; ?>">
            <!--{content}-->
                <?php echo $content; ?>
            <!--{/content}-->
        </div>
    <!--[/<?php echo $tag; ?>]-->
<?php
    return ob_get_clean();
}
ShortcodesUtility::addShortcode('social_icons', 'themler_shortcode_social_icons');
?>
<?php

// Spacer
function themler_shortcode_spacer($atts, $content = '', $tag) {
    $atts = ShortcodesUtility::atts(array(), $atts);

    $id = ShortcodesEffects::init('bd-spacer', $atts, true);
    list($style_css, $additional_classes, ) = ShortcodesEffects::css($id, $atts);

    ob_start();
    ?>

    <!--[<?php echo $tag ?>]-->
    <?php echo $style_css ?>
    <div class="<?php echo $additional_classes ?> clearfix">
        <!--{content}--><!--{/content}-->
    </div>
    <!--[/<?php echo $tag ?>]-->

    <?php
    return ob_get_clean();
}

ShortcodesUtility::addShortcode('spacer', 'themler_shortcode_spacer');
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Table', "", "bd-table", "bd-table-1-mixin");
?>
<?php
// TextBlock
function themler_shortcode_text_block($atts, $content='') {
    $atts = ShortcodesUtility::atts(array(
        'tag' => 'div'
    ), $atts);

    $tag = $atts['tag'];

    $id = ShortcodesEffects::init('', $atts);
    $content = ShortcodesUtility::doShortcode($content);

    list($style_tag, $additional_class) = ShortcodesEffects::css($id, $atts);

    $additional_class .= ' bd-content-element';
    ob_start();
?>
    <!--[text]-->
        <?php echo $style_tag; ?>
        <<?php echo $tag; ?> class="<?php echo $additional_class; ?>">
            <!--{content}-->
                <?php echo $content; ?>
            <!--{/content}-->
        </<?php echo $tag; ?>>
    <!--[/text]-->
<?php
    return ob_get_clean();
}
ShortcodesUtility::addShortcode('text', 'themler_shortcode_text_block');
?>
<?php
// TextGroup
function themler_shortcode_text_group($atts, $content='') {
    $atts = ShortcodesUtility::atts(array(
        'image' => '',
        'image_link' => '',
        'image_position' => 'left',
        'header' => '',
        'header_tag' => 'h4',
        'responsive' => 'xs',

        'style' => '',

        'image_style' => '',
        // deprecated attributes:
        'image_width' => '',
        'image_height' => '',

        'header_css' => ''
    ), $atts, array('', 'image_'));

    // hack with prefix
    $atts['image_image'] = $atts['image'];
    $atts['image_image_style'] = $atts['image_style'];
    $atts['link'] = $atts['image_link'];

    $header_tag = $atts['header_tag'];
    $responsive = $atts['responsive'];
    $header = $atts['header'];
    $image = $atts['image_image'];
    $image_link = $atts['image_link'];
    $image_position = strtolower($atts['image_position']);

    $image_positions = array('left' => 'pull-left', 'right' => 'pull-right', 'top' => 'top', 'bottom' => 'bottom', 'middle' => 'middle');
    $headers = array('h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3', 'h4' => 'h4', 'h5' => 'h5', 'h6' => 'h6');
    $header_tag = array_key_exists(strtolower($header_tag), $headers) ? $headers[strtolower($header_tag)] : 'h4';

    $id = ShortcodesEffects::init('', $atts);
    $content = ShortcodesUtility::doShortcode($content, true, true);
    list($style_tag, $additional_class) = ShortcodesEffects::css($id, $atts);

    $imageHtml = '';
    $imageStyles = '';
    if ($image !== '') {
        $additionalClass = array();
        $additionalImageAtts = array();

        if ($image_position === 'left' || $image_position === 'right') {
            $additionalClass[] = $image_positions[$image_position];
        }
        if (!$image_link) {
            $additionalClass[] = 'media-object';
        }
        if ($responsive && $responsive !== 'none') {
            $additionalClass[] = 'bd-media-' . $responsive;
        }
        if ($atts['image_width']) {
            $additionalImageAtts[] = 'width="' . $atts['image_width'] . '"';
        }
        if ($atts['image_height']) {
            $additionalImageAtts[] = 'height="' . $atts['image_height'] . '"';
        }

        $imageResult = themler_shortcode_image($atts, '', '', array(
            'id' => $id,
            'prefix' => 'image_',
            'additionalClass' => join(' ', $additionalClass),
            'additionalImageAtts' => join(' ', $additionalImageAtts)
        ));
        $imageHtml = $imageResult['html'];
        $imageHtml = preg_replace('/<!--[\s\S]*?-->/', '', $imageHtml);
        $imageStyles = $imageResult['css'];
    }

    $additional_class .= ' ' . ShortcodesStyles::getStyleClassname('Block', $atts['style']);

    ob_start();
?>
    <!--[text_group]-->
        <?php echo $style_tag . ($image !== '' ? $imageStyles : '') ?>
        <div class="<?php echo $additional_class ?>">
            <div class="bd-container-inner">
                <div class="media">
                    <?php if ($image_position === 'top' || $image_position === 'left' || $image_position === 'right') echo $imageHtml ?>
                    <div class="media-body">
                        <?php if ($header_tag && $header): ?>
                            <<?php echo $header_tag; ?><?php if ($atts['header_css']) echo '  style="'.ShortcodesUtility::escape($atts['header_css']).'"'; ?> class="media-heading bd-blockheader bd-tagstyles bd-content-element"><?php echo $header; ?></<?php echo $header_tag; ?>>
                        <?php endif; ?>
                        <?php if ($image_position === 'middle') echo $imageHtml ?>
                        <div class="bd-blockcontent bd-tagstyles bd-content-element">
                            <!--{content}-->
                                <?php echo $content; ?>
                            <!--{/content}-->
                        </div>
                    </div>
                    <?php if ($image_position === 'bottom') echo $imageHtml ?>
                </div>
            </div>
        </div>
    <!--[/text_group]-->
<?php
    return ob_get_clean();
}
ShortcodesUtility::addShortcode('text_group', 'themler_shortcode_text_group');
?>
<?php

// TextureOverlay
function themler_shortcode_overlay($atts, $content = '', $tag = '') {
    $atts = ShortcodesUtility::atts(array(), $atts);

    $id = ShortcodesEffects::init('bd-textureoverlay', $atts, ShortcodesEffects::CSS_EFFECT);
    $content = ShortcodesUtility::doShortcode($content);
    list($style_tag, $additional_classes, $selector) = ShortcodesEffects::css(
        $id,
        ShortcodesEffects::filter($atts, 'background,opacity,border-radius'),
        '',
        '{selector}:before'
    );

    $targetControl = ShortcodesEffects::target_control($id);

    $content = ShortcodesEffects::addClassesAndAttrs(
        $content,
        $targetControl,
        array('bd-textureoverlay', $additional_classes),
        array()
    );

    return "<!--[$tag]-->$style_tag<!--{content}-->" . $content . "<!--{/content}--><!--[/$tag]-->";
}

ShortcodesUtility::addShortcode('overlay', 'themler_shortcode_overlay');
?>
<?php

// Video
function themler_shortcode_video($atts, $content = '', $tag = '') {
    $default_types = function_exists('wp_get_video_extensions') ? wp_get_video_extensions() : array();
    $default_types[] = 'link'; // deprecated attribute
    $defaults_atts = array(
        'src' => '',
        'poster'   => '',
        'loop' => false,
        'autoplay' => false,
        'preload'  => 'metadata',
		'width'    => 640,
		'height'   => 360,

        'image_style' => '',
        'title' => true,
        'control_bar' => false,
        'show_control_bar' => '',
    );

	foreach ( $default_types as $type ) {
		$defaults_atts[$type] = '';
	}
    $atts = ShortcodesUtility::atts($defaults_atts, $atts);

    $src = '';
    if (!empty($atts['src'])) {
        $src = ShortcodesUtility::escape($atts['src']);

    } else {
        foreach ($default_types as $type) {
            if (!empty($atts[$type])) {
                $src = ShortcodesUtility::escape($atts[$type]);
            }
	    }
    }

    $width = intval($atts['width']);
    $height = intval($atts['height']);

    $autoplay = ShortcodesUtility::getBool($atts['autoplay']);
    $loop = ShortcodesUtility::getBool($atts['loop']);
    $title = ShortcodesUtility::getBool($atts['title']);
    $control_bar = ShortcodesUtility::getBool($atts['control_bar']);
    $show_control_bar = ShortcodesUtility::escape($atts['show_control_bar']);

    $id = ShortcodesEffects::init('', $atts);
    list($style_tag, $additional_class, $selector) = ShortcodesEffects::css($id, $atts);

    $additional_image_class = ShortcodesStyles::getStyleClassname('Image', $atts['image_style']);

    if (strpos($src, 'youtube.com/watch') !== false) {
        $tmp = explode('&', $src);
        list(, $video_id) = explode('=', isset($tmp[0]) ? $tmp[0] : '');
    } else {
        $video_id = end(explode('/', $src));
    }

    if (preg_match('/[&,?]t=((\d+h\d+m\d+s)|(\d+h\d+m)|(\d+h\d+s)|(\d+m\d+s)|(\d+[h,m,s]))$/', $src)){
        $h = $m = $s = $secCounter = 0;

        if (preg_match('/\d+h/', $src, $matches)){
            $h = intval($matches[0]);
        }

        if (preg_match('/\d+m/', $src, $matches)){
            $m = intval($matches[0]);
        }

        if (preg_match('/\d+s/', $src, $matches)){
            $s = intval($matches[0]);
        }

        $secCounter = 60 * 60 * $h + 60 * $m + $s;

        if (!is_nan($secCounter)){
            $timeStart = $secCounter;
        }
    }
    else {
        $timeStart = '';
    }

    $iframe_atts = array();

    $iframe_atts[] = 'data-autoplay="' . ($autoplay ? 'true' : 'false') . '"';
    $iframe_atts[] = 'class="embed-responsive-item"';
    $iframe_atts[] = 'frameborder="0"';
    $iframe_atts[] = 'allowfullscreen';

    $src_params = array();
    $src_params[] = 'loop=' . ($loop ? 1 : 0);

    if (strpos($src, 'youtube') !== false || strpos($src, 'youtu.be') !== false) {

        $src_params[] = 'playlist=' . ($loop ? $video_id : 'null');
        $src_params[] = 'showinfo=' . ($title ? 1 : 0);
        $src_params[] = 'theme=' . ($control_bar ? 'light' : 'dark');
        $src_params[] = 'autohide=' . ($show_control_bar === 'autohide' ? 1 : 0);
        $src_params[] = 'controls=' . ($show_control_bar === 'hide' ? 0 : 1);
        $src_params[] = 'start='. $timeStart;

        $src = 'https://www.youtube.com/embed/' . $video_id . '?' . implode('&', $src_params);
    } else if (strpos($src, 'vimeo') !== false) {
        $iframe_atts[] = 'webkitallowfullscreen';
        $iframe_atts[] = 'mozallowfullscreen';

        $src_params[] = 'title=' . ($title ? 1 : 0);
        $src_params[] = 'color=' . ($control_bar ? 'ffffff' : '00adef');

        $src = 'https://player.vimeo.com/video/' . $video_id . '?' . implode('&', $src_params);
    }
    $iframe_atts[] = 'src="' . $src . '"';

    ob_start();
?>
    <!--[<?php echo $tag; ?>]-->
        <style><?php echo $selector; ?> {
            max-width: <?php echo $width; ?>px;
            max-height: <?php echo $height; ?>px;
            display: block;
        }</style>
        <?php echo $style_tag; ?>
        <?php if (!empty($atts['css']['display'])): ?>
            <?php echo $selector ?> {
                display: <?php echo $atts['css']['display']; ?>
            }
        <?php endif ?>
        <div class="<?php echo $additional_image_class . ' ' . $additional_class; ?>">
            <div class="embed-responsive embed-responsive-16by9">
                <iframe <?php echo implode(' ', $iframe_atts); ?>>
                    <!--{content}--><!--{/content}-->
                </iframe>
            </div>' .
        </div>
    <!--[/<?php echo $tag; ?>]-->
<?php
    return ob_get_clean();
}

ShortcodesUtility::addShortcode('video', 'themler_shortcode_video');
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Block', "", "bd-block", "bd-block-1-mixin");
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Carousel', "", "bd-carousel", "bd-carousel-13-mixin");
?>
<?php
ThemeShortcodesStyles::putStyleClassname('Indicators', "", "bd-indicators", "bd-indicators-16-mixin");
?>