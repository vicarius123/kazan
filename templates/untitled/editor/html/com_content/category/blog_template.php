<!--COMPONENT common -->
<?php
$view = new DesignerContent($this, $this->params);

$leadArticles = $this->lead_items;
$countLeadArticles = count($this->lead_items);
$introArticles = $this->intro_items;
$countIntroArticles = count($this->intro_items);

$sampleItems = sampleArticlesForPreview($componentCurrentTemplate);
$countSampleItems = count($sampleItems);

$startNav = (int)JRequest::getVar('start');
if ($startNav != 0 && isset($sampleItems[$startNav])) {
    $sampleItems = array_slice($sampleItems, $startNav);
}

if ($countSampleItems > 0) {
    foreach($leadArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $leadArticles = array_slice($sampleItems, 0, $countLeadArticles);

    $sampleItems = array_slice($sampleItems, $countLeadArticles);

    foreach($introArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $introArticles = array_slice($sampleItems, 0, $countIntroArticles);
}


$this->articleTemplate = 'article_2';

ob_start();
?>

<div class="data-control-id-3035 bd-blog <?php echo $this->pageclass_sfx; ?>" itemscope itemtype="http://schema.org/Blog">
<?php
$categoryInfo = '';
if ($this->params->get('show_category_title', 1) || strlen($this->params->get('page_subheading'))) {
    $categoryInfo = $this->escape($this->params->get('page_subheading'));
    if ($this->params->get('show_category_title') && strlen($this->category->title))
        $categoryInfo .= '<span class="subheading-category">' . $this->category->title . '</span>';
}
if ($this->params->def('show_description', 1) || $this->params->def('show_description_image', 1)) {
    if ($this->params->get('show_description_image') && $this->category->getParams()->get('image'))
        $categoryInfo .= '<img src="' . $this->category->getParams()->get('image') . '" alt="" />';
    if ($this->params->get('show_description') && $this->category->description)
        $categoryInfo .= JHtml::_('content.prepare', $this->category->description, '', 'com_content.category');
}
$pageHeading = '';
if ('' !== $categoryInfo) {
    echo renderTemplateFromIncludes('article_2', array(array('header-text' => $view->pageHeading, 'content' => $categoryInfo)));
} else {
    $pageHeading = $view->pageHeading;
}
$pagination_list = array();
if (($this->params->def('show_pagination', 1) == 1 || $this->params->get('show_pagination') == 2)
    && $this->pagination->get('pages.total') > 1)
{
    $GLOBALS['theme_settings']['active_paginator'] = 'specific';
    $pagination_list = $this->pagination->getPagesLinks();
}
?>

<?php if ($pageHeading) : ?>
    <h2 class="data-control-id-2913 bd-container-15 bd-tagstyles"><?php echo $pageHeading; ?></h2>
<?php endif; ?>

 <?php
    $itemClass = 'separated-item-30 col-md-12 ';
    $mergedModes = '' . '1' . '' . '';
    $str = 'col-lg-1 col-md-1 col-sm-1 col-xs-1';
?>
<?php $leadingcount=0 ; ?>
<?php if (!empty($this->lead_items)) : ?>
<div class="data-control-id-2881 bd-grid-5">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $leadingItemClass = preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . 12, $str);
    ?>
    <?php foreach ($leadArticles as &$item) : ?>
        <div class="<?php echo $leadingItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
            <div class="bd-griditem-30">
            <?php
                $this->item = &$item;
                echo $this->loadTemplate('item');
            ?>
            </div>
        </div>
        <?php $leadingcount++; ?>
    <?php endforeach; ?>
        </div>
   </div>
</div>
<?php endif; ?>
<?php
    $introcount = (count($this->intro_items));
    $counter = 0;
?>
<?php if (!empty($this->intro_items)) : ?>
<div class="data-control-id-2881 bd-grid-5">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $introItemClass = $itemClass;
        if ('' === $mergedModes)
            $introItemClass = $introItemClass . preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . round(12 / min(12, max(1, $this->columns))), $str);
    ?>
    <?php foreach ($introArticles as $key => &$item) : ?>
    <?php
        $rowcount = ((int) $key % (int) $this->columns) + 1;
        $row = $counter / $this->columns ;
    ?>
    <?php $counter++; ?>
    <div class="<?php echo $introItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
        <div class="bd-griditem-30">
    <?php
        $this->item = &$item;
        echo $this->loadTemplate('item');
    ?>
        </div>
    </div>
    <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php  if (!empty($this->children[$this->category->id])&& $this->maxLevel != 0) : ?>
    <div class="cat-children">
    <?php
        echo renderTemplateFromIncludes('article_2', array(array('header-text' => JTEXT::_('JGLOBAL_SUBCATEGORIES'),
            'content' => $this->loadTemplate('children'))));
    ?>
    </div>
<?php endif; ?>
<?php if (count($pagination_list) > 0) : ?>
    <div class="data-control-id-2919 bd-blogpagination-1">
        <?php
            echo renderTemplateFromIncludes('pagination_list_render_1', array($pagination_list));
        ?>
    </div>
<?php endif; ?>
</div>

<?php
echo ob_get_clean();
?>
<!--COMPONENT common /-->
<!--COMPONENT blog_2 -->
<?php
$view = new DesignerContent($this, $this->params);

$leadArticles = $this->lead_items;
$countLeadArticles = count($this->lead_items);
$introArticles = $this->intro_items;
$countIntroArticles = count($this->intro_items);

$sampleItems = sampleArticlesForPreview($componentCurrentTemplate);
$countSampleItems = count($sampleItems);

$startNav = (int)JRequest::getVar('start');
if ($startNav != 0 && isset($sampleItems[$startNav])) {
    $sampleItems = array_slice($sampleItems, $startNav);
}

if ($countSampleItems > 0) {
    foreach($leadArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $leadArticles = array_slice($sampleItems, 0, $countLeadArticles);

    $sampleItems = array_slice($sampleItems, $countLeadArticles);

    foreach($introArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $introArticles = array_slice($sampleItems, 0, $countIntroArticles);
}


$this->articleTemplate = 'article_7';

ob_start();
?>

<div class="data-control-id-1258743 bd-blog-2 <?php echo $this->pageclass_sfx; ?>" itemscope itemtype="http://schema.org/Blog">
<?php
$categoryInfo = '';
if ($this->params->get('show_category_title', 1) || strlen($this->params->get('page_subheading'))) {
    $categoryInfo = $this->escape($this->params->get('page_subheading'));
    if ($this->params->get('show_category_title') && strlen($this->category->title))
        $categoryInfo .= '<span class="subheading-category">' . $this->category->title . '</span>';
}
if ($this->params->def('show_description', 1) || $this->params->def('show_description_image', 1)) {
    if ($this->params->get('show_description_image') && $this->category->getParams()->get('image'))
        $categoryInfo .= '<img src="' . $this->category->getParams()->get('image') . '" alt="" />';
    if ($this->params->get('show_description') && $this->category->description)
        $categoryInfo .= JHtml::_('content.prepare', $this->category->description, '', 'com_content.category');
}
$pageHeading = '';
if ('' !== $categoryInfo) {
    echo renderTemplateFromIncludes('article_7', array(array('header-text' => $view->pageHeading, 'content' => $categoryInfo)));
} else {
    $pageHeading = $view->pageHeading;
}
$pagination_list = array();
if (($this->params->def('show_pagination', 1) == 1 || $this->params->get('show_pagination') == 2)
    && $this->pagination->get('pages.total') > 1)
{
    $GLOBALS['theme_settings']['active_paginator'] = 'specific';
    $pagination_list = $this->pagination->getPagesLinks();
}
?>

<?php if ($pageHeading) : ?>
    <h2 class="data-control-id-1258708 bd-container-4 bd-tagstyles"><?php echo $pageHeading; ?></h2>
<?php endif; ?>

 <?php
    $itemClass = 'separated-item-7 col-md-12 ';
    $mergedModes = '' . '1' . '' . '';
    $str = 'col-lg-1 col-md-1 col-sm-1 col-xs-1';
?>
<?php $leadingcount=0 ; ?>
<?php if (!empty($this->lead_items)) : ?>
<div class="data-control-id-1258698 bd-grid-1">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $leadingItemClass = preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . 12, $str);
    ?>
    <?php foreach ($leadArticles as &$item) : ?>
        <div class="<?php echo $leadingItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
            <div class="bd-griditem-7">
            <?php
                $this->item = &$item;
                echo $this->loadTemplate('item');
            ?>
            </div>
        </div>
        <?php $leadingcount++; ?>
    <?php endforeach; ?>
        </div>
   </div>
</div>
<?php endif; ?>
<?php
    $introcount = (count($this->intro_items));
    $counter = 0;
?>
<?php if (!empty($this->intro_items)) : ?>
<div class="data-control-id-1258698 bd-grid-1">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $introItemClass = $itemClass;
        if ('' === $mergedModes)
            $introItemClass = $introItemClass . preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . round(12 / min(12, max(1, $this->columns))), $str);
    ?>
    <?php foreach ($introArticles as $key => &$item) : ?>
    <?php
        $rowcount = ((int) $key % (int) $this->columns) + 1;
        $row = $counter / $this->columns ;
    ?>
    <?php $counter++; ?>
    <div class="<?php echo $introItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
        <div class="bd-griditem-7">
    <?php
        $this->item = &$item;
        echo $this->loadTemplate('item');
    ?>
        </div>
    </div>
    <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php  if (!empty($this->children[$this->category->id])&& $this->maxLevel != 0) : ?>
    <div class="cat-children">
    <?php
        echo renderTemplateFromIncludes('article_7', array(array('header-text' => JTEXT::_('JGLOBAL_SUBCATEGORIES'),
            'content' => $this->loadTemplate('children'))));
    ?>
    </div>
<?php endif; ?>
<?php if (count($pagination_list) > 0) : ?>
    <div class="data-control-id-1258710 bd-blogpagination-6">
        <?php
            echo renderTemplateFromIncludes('pagination_list_render_6', array($pagination_list));
        ?>
    </div>
<?php endif; ?>
</div>

<?php
echo ob_get_clean();
?>
<!--COMPONENT blog_2 /-->
<!--COMPONENT blog_5 -->
<?php
$view = new DesignerContent($this, $this->params);

$leadArticles = $this->lead_items;
$countLeadArticles = count($this->lead_items);
$introArticles = $this->intro_items;
$countIntroArticles = count($this->intro_items);

$sampleItems = sampleArticlesForPreview($componentCurrentTemplate);
$countSampleItems = count($sampleItems);

$startNav = (int)JRequest::getVar('start');
if ($startNav != 0 && isset($sampleItems[$startNav])) {
    $sampleItems = array_slice($sampleItems, $startNav);
}

if ($countSampleItems > 0) {
    foreach($leadArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $leadArticles = array_slice($sampleItems, 0, $countLeadArticles);

    $sampleItems = array_slice($sampleItems, $countLeadArticles);

    foreach($introArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $introArticles = array_slice($sampleItems, 0, $countIntroArticles);
}


$this->articleTemplate = 'article_4';

ob_start();
?>

<div class="data-control-id-3353 bd-blog-5 <?php echo $this->pageclass_sfx; ?>" itemscope itemtype="http://schema.org/Blog">
<?php
$categoryInfo = '';
if ($this->params->get('show_category_title', 1) || strlen($this->params->get('page_subheading'))) {
    $categoryInfo = $this->escape($this->params->get('page_subheading'));
    if ($this->params->get('show_category_title') && strlen($this->category->title))
        $categoryInfo .= '<span class="subheading-category">' . $this->category->title . '</span>';
}
if ($this->params->def('show_description', 1) || $this->params->def('show_description_image', 1)) {
    if ($this->params->get('show_description_image') && $this->category->getParams()->get('image'))
        $categoryInfo .= '<img src="' . $this->category->getParams()->get('image') . '" alt="" />';
    if ($this->params->get('show_description') && $this->category->description)
        $categoryInfo .= JHtml::_('content.prepare', $this->category->description, '', 'com_content.category');
}
$pageHeading = '';
if ('' !== $categoryInfo) {
    echo renderTemplateFromIncludes('article_4', array(array('header-text' => $view->pageHeading, 'content' => $categoryInfo)));
} else {
    $pageHeading = $view->pageHeading;
}
$pagination_list = array();
if (($this->params->def('show_pagination', 1) == 1 || $this->params->get('show_pagination') == 2)
    && $this->pagination->get('pages.total') > 1)
{
    $GLOBALS['theme_settings']['active_paginator'] = 'specific';
    $pagination_list = $this->pagination->getPagesLinks();
}
?>

<?php if ($pageHeading) : ?>
    <h2 class="data-control-id-3231 bd-container-21 bd-tagstyles"><?php echo $pageHeading; ?></h2>
<?php endif; ?>

 <?php
    $itemClass = 'separated-item-46 col-md-12 ';
    $mergedModes = '' . '1' . '' . '';
    $str = 'col-lg-1 col-md-1 col-sm-1 col-xs-1';
?>
<?php $leadingcount=0 ; ?>
<?php if (!empty($this->lead_items)) : ?>
<div class="data-control-id-3199 bd-grid-7">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $leadingItemClass = preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . 12, $str);
    ?>
    <?php foreach ($leadArticles as &$item) : ?>
        <div class="<?php echo $leadingItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
            <div class="bd-griditem-46">
            <?php
                $this->item = &$item;
                echo $this->loadTemplate('item');
            ?>
            </div>
        </div>
        <?php $leadingcount++; ?>
    <?php endforeach; ?>
        </div>
   </div>
</div>
<?php endif; ?>
<?php
    $introcount = (count($this->intro_items));
    $counter = 0;
?>
<?php if (!empty($this->intro_items)) : ?>
<div class="data-control-id-3199 bd-grid-7">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $introItemClass = $itemClass;
        if ('' === $mergedModes)
            $introItemClass = $introItemClass . preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . round(12 / min(12, max(1, $this->columns))), $str);
    ?>
    <?php foreach ($introArticles as $key => &$item) : ?>
    <?php
        $rowcount = ((int) $key % (int) $this->columns) + 1;
        $row = $counter / $this->columns ;
    ?>
    <?php $counter++; ?>
    <div class="<?php echo $introItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
        <div class="bd-griditem-46">
    <?php
        $this->item = &$item;
        echo $this->loadTemplate('item');
    ?>
        </div>
    </div>
    <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php  if (!empty($this->children[$this->category->id])&& $this->maxLevel != 0) : ?>
    <div class="cat-children">
    <?php
        echo renderTemplateFromIncludes('article_4', array(array('header-text' => JTEXT::_('JGLOBAL_SUBCATEGORIES'),
            'content' => $this->loadTemplate('children'))));
    ?>
    </div>
<?php endif; ?>
<?php if (count($pagination_list) > 0) : ?>
    <div class="data-control-id-3237 bd-blogpagination-3">
        <?php
            echo renderTemplateFromIncludes('pagination_list_render_3', array($pagination_list));
        ?>
    </div>
<?php endif; ?>
</div>

<?php
echo ob_get_clean();
?>
<!--COMPONENT blog_5 /-->
<!--COMPONENT blog_3 -->
<?php
$view = new DesignerContent($this, $this->params);

$leadArticles = $this->lead_items;
$countLeadArticles = count($this->lead_items);
$introArticles = $this->intro_items;
$countIntroArticles = count($this->intro_items);

$sampleItems = sampleArticlesForPreview($componentCurrentTemplate);
$countSampleItems = count($sampleItems);

$startNav = (int)JRequest::getVar('start');
if ($startNav != 0 && isset($sampleItems[$startNav])) {
    $sampleItems = array_slice($sampleItems, $startNav);
}

if ($countSampleItems > 0) {
    foreach($leadArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $leadArticles = array_slice($sampleItems, 0, $countLeadArticles);

    $sampleItems = array_slice($sampleItems, $countLeadArticles);

    foreach($introArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $introArticles = array_slice($sampleItems, 0, $countIntroArticles);
}


$this->articleTemplate = 'article_3';

ob_start();
?>

<div class="data-control-id-3194 bd-blog-3 <?php echo $this->pageclass_sfx; ?>" itemscope itemtype="http://schema.org/Blog">
<?php
$categoryInfo = '';
if ($this->params->get('show_category_title', 1) || strlen($this->params->get('page_subheading'))) {
    $categoryInfo = $this->escape($this->params->get('page_subheading'));
    if ($this->params->get('show_category_title') && strlen($this->category->title))
        $categoryInfo .= '<span class="subheading-category">' . $this->category->title . '</span>';
}
if ($this->params->def('show_description', 1) || $this->params->def('show_description_image', 1)) {
    if ($this->params->get('show_description_image') && $this->category->getParams()->get('image'))
        $categoryInfo .= '<img src="' . $this->category->getParams()->get('image') . '" alt="" />';
    if ($this->params->get('show_description') && $this->category->description)
        $categoryInfo .= JHtml::_('content.prepare', $this->category->description, '', 'com_content.category');
}
$pageHeading = '';
if ('' !== $categoryInfo) {
    echo renderTemplateFromIncludes('article_3', array(array('header-text' => $view->pageHeading, 'content' => $categoryInfo)));
} else {
    $pageHeading = $view->pageHeading;
}
$pagination_list = array();
if (($this->params->def('show_pagination', 1) == 1 || $this->params->get('show_pagination') == 2)
    && $this->pagination->get('pages.total') > 1)
{
    $GLOBALS['theme_settings']['active_paginator'] = 'specific';
    $pagination_list = $this->pagination->getPagesLinks();
}
?>

<?php if ($pageHeading) : ?>
    <h2 class="data-control-id-3072 bd-container-18 bd-tagstyles"><?php echo $pageHeading; ?></h2>
<?php endif; ?>

 <?php
    $itemClass = 'separated-item-38 col-md-12 ';
    $mergedModes = '' . '1' . '' . '';
    $str = 'col-lg-1 col-md-1 col-sm-1 col-xs-1';
?>
<?php $leadingcount=0 ; ?>
<?php if (!empty($this->lead_items)) : ?>
<div class="data-control-id-3040 bd-grid-6">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $leadingItemClass = preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . 12, $str);
    ?>
    <?php foreach ($leadArticles as &$item) : ?>
        <div class="<?php echo $leadingItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
            <div class="bd-griditem-38">
            <?php
                $this->item = &$item;
                echo $this->loadTemplate('item');
            ?>
            </div>
        </div>
        <?php $leadingcount++; ?>
    <?php endforeach; ?>
        </div>
   </div>
</div>
<?php endif; ?>
<?php
    $introcount = (count($this->intro_items));
    $counter = 0;
?>
<?php if (!empty($this->intro_items)) : ?>
<div class="data-control-id-3040 bd-grid-6">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $introItemClass = $itemClass;
        if ('' === $mergedModes)
            $introItemClass = $introItemClass . preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . round(12 / min(12, max(1, $this->columns))), $str);
    ?>
    <?php foreach ($introArticles as $key => &$item) : ?>
    <?php
        $rowcount = ((int) $key % (int) $this->columns) + 1;
        $row = $counter / $this->columns ;
    ?>
    <?php $counter++; ?>
    <div class="<?php echo $introItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
        <div class="bd-griditem-38">
    <?php
        $this->item = &$item;
        echo $this->loadTemplate('item');
    ?>
        </div>
    </div>
    <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php  if (!empty($this->children[$this->category->id])&& $this->maxLevel != 0) : ?>
    <div class="cat-children">
    <?php
        echo renderTemplateFromIncludes('article_3', array(array('header-text' => JTEXT::_('JGLOBAL_SUBCATEGORIES'),
            'content' => $this->loadTemplate('children'))));
    ?>
    </div>
<?php endif; ?>
<?php if (count($pagination_list) > 0) : ?>
    <div class="data-control-id-3078 bd-blogpagination-2">
        <?php
            echo renderTemplateFromIncludes('pagination_list_render_2', array($pagination_list));
        ?>
    </div>
<?php endif; ?>
</div>

<?php
echo ob_get_clean();
?>
<!--COMPONENT blog_3 /-->
<!--COMPONENT blog_7 -->
<?php
$view = new DesignerContent($this, $this->params);

$leadArticles = $this->lead_items;
$countLeadArticles = count($this->lead_items);
$introArticles = $this->intro_items;
$countIntroArticles = count($this->intro_items);

$sampleItems = sampleArticlesForPreview($componentCurrentTemplate);
$countSampleItems = count($sampleItems);

$startNav = (int)JRequest::getVar('start');
if ($startNav != 0 && isset($sampleItems[$startNav])) {
    $sampleItems = array_slice($sampleItems, $startNav);
}

if ($countSampleItems > 0) {
    foreach($leadArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $leadArticles = array_slice($sampleItems, 0, $countLeadArticles);

    $sampleItems = array_slice($sampleItems, $countLeadArticles);

    foreach($introArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $introArticles = array_slice($sampleItems, 0, $countIntroArticles);
}


$this->articleTemplate = 'article_5';

ob_start();
?>

<div class="data-control-id-1522 bd-blog-7 <?php echo $this->pageclass_sfx; ?>" itemscope itemtype="http://schema.org/Blog">
<?php
$categoryInfo = '';
if ($this->params->get('show_category_title', 1) || strlen($this->params->get('page_subheading'))) {
    $categoryInfo = $this->escape($this->params->get('page_subheading'));
    if ($this->params->get('show_category_title') && strlen($this->category->title))
        $categoryInfo .= '<span class="subheading-category">' . $this->category->title . '</span>';
}
if ($this->params->def('show_description', 1) || $this->params->def('show_description_image', 1)) {
    if ($this->params->get('show_description_image') && $this->category->getParams()->get('image'))
        $categoryInfo .= '<img src="' . $this->category->getParams()->get('image') . '" alt="" />';
    if ($this->params->get('show_description') && $this->category->description)
        $categoryInfo .= JHtml::_('content.prepare', $this->category->description, '', 'com_content.category');
}
$pageHeading = '';
if ('' !== $categoryInfo) {
    echo renderTemplateFromIncludes('article_5', array(array('header-text' => $view->pageHeading, 'content' => $categoryInfo)));
} else {
    $pageHeading = $view->pageHeading;
}
$pagination_list = array();
if (($this->params->def('show_pagination', 1) == 1 || $this->params->get('show_pagination') == 2)
    && $this->pagination->get('pages.total') > 1)
{
    $GLOBALS['theme_settings']['active_paginator'] = 'specific';
    $pagination_list = $this->pagination->getPagesLinks();
}
?>

<?php if ($pageHeading) : ?>
    <h2 class="data-control-id-1400 bd-container-24 bd-tagstyles"><?php echo $pageHeading; ?></h2>
<?php endif; ?>

 <?php
    $itemClass = 'separated-item-12 col-md-12 ';
    $mergedModes = '' . '1' . '' . '';
    $str = 'col-lg-1 col-md-1 col-sm-1 col-xs-1';
?>
<?php $leadingcount=0 ; ?>
<?php if (!empty($this->lead_items)) : ?>
<div class="data-control-id-1368 bd-grid-8">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $leadingItemClass = preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . 12, $str);
    ?>
    <?php foreach ($leadArticles as &$item) : ?>
        <div class="<?php echo $leadingItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
            <div class="bd-griditem-12">
            <?php
                $this->item = &$item;
                echo $this->loadTemplate('item');
            ?>
            </div>
        </div>
        <?php $leadingcount++; ?>
    <?php endforeach; ?>
        </div>
   </div>
</div>
<?php endif; ?>
<?php
    $introcount = (count($this->intro_items));
    $counter = 0;
?>
<?php if (!empty($this->intro_items)) : ?>
<div class="data-control-id-1368 bd-grid-8">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $introItemClass = $itemClass;
        if ('' === $mergedModes)
            $introItemClass = $introItemClass . preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . round(12 / min(12, max(1, $this->columns))), $str);
    ?>
    <?php foreach ($introArticles as $key => &$item) : ?>
    <?php
        $rowcount = ((int) $key % (int) $this->columns) + 1;
        $row = $counter / $this->columns ;
    ?>
    <?php $counter++; ?>
    <div class="<?php echo $introItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
        <div class="bd-griditem-12">
    <?php
        $this->item = &$item;
        echo $this->loadTemplate('item');
    ?>
        </div>
    </div>
    <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php  if (!empty($this->children[$this->category->id])&& $this->maxLevel != 0) : ?>
    <div class="cat-children">
    <?php
        echo renderTemplateFromIncludes('article_5', array(array('header-text' => JTEXT::_('JGLOBAL_SUBCATEGORIES'),
            'content' => $this->loadTemplate('children'))));
    ?>
    </div>
<?php endif; ?>
<?php if (count($pagination_list) > 0) : ?>
    <div class="data-control-id-1406 bd-blogpagination-4">
        <?php
            echo renderTemplateFromIncludes('pagination_list_render_4', array($pagination_list));
        ?>
    </div>
<?php endif; ?>
</div>

<?php
echo ob_get_clean();
?>
<!--COMPONENT blog_7 /-->
<!--COMPONENT blog_8 -->
<?php
$view = new DesignerContent($this, $this->params);

$leadArticles = $this->lead_items;
$countLeadArticles = count($this->lead_items);
$introArticles = $this->intro_items;
$countIntroArticles = count($this->intro_items);

$sampleItems = sampleArticlesForPreview($componentCurrentTemplate);
$countSampleItems = count($sampleItems);

$startNav = (int)JRequest::getVar('start');
if ($startNav != 0 && isset($sampleItems[$startNav])) {
    $sampleItems = array_slice($sampleItems, $startNav);
}

if ($countSampleItems > 0) {
    foreach($leadArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $leadArticles = array_slice($sampleItems, 0, $countLeadArticles);

    $sampleItems = array_slice($sampleItems, $countLeadArticles);

    foreach($introArticles as $i => $item) {
        $sampleItems[] = $item;
    }
    $introArticles = array_slice($sampleItems, 0, $countIntroArticles);
}


$this->articleTemplate = 'article_6';

ob_start();
?>

<div class="data-control-id-1773 bd-blog-8 <?php echo $this->pageclass_sfx; ?>" itemscope itemtype="http://schema.org/Blog">
<?php
$categoryInfo = '';
if ($this->params->get('show_category_title', 1) || strlen($this->params->get('page_subheading'))) {
    $categoryInfo = $this->escape($this->params->get('page_subheading'));
    if ($this->params->get('show_category_title') && strlen($this->category->title))
        $categoryInfo .= '<span class="subheading-category">' . $this->category->title . '</span>';
}
if ($this->params->def('show_description', 1) || $this->params->def('show_description_image', 1)) {
    if ($this->params->get('show_description_image') && $this->category->getParams()->get('image'))
        $categoryInfo .= '<img src="' . $this->category->getParams()->get('image') . '" alt="" />';
    if ($this->params->get('show_description') && $this->category->description)
        $categoryInfo .= JHtml::_('content.prepare', $this->category->description, '', 'com_content.category');
}
$pageHeading = '';
if ('' !== $categoryInfo) {
    echo renderTemplateFromIncludes('article_6', array(array('header-text' => $view->pageHeading, 'content' => $categoryInfo)));
} else {
    $pageHeading = $view->pageHeading;
}
$pagination_list = array();
if (($this->params->def('show_pagination', 1) == 1 || $this->params->get('show_pagination') == 2)
    && $this->pagination->get('pages.total') > 1)
{
    $GLOBALS['theme_settings']['active_paginator'] = 'specific';
    $pagination_list = $this->pagination->getPagesLinks();
}
?>

<?php if ($pageHeading) : ?>
    <h2 class="data-control-id-1651 bd-container-27 bd-tagstyles"><?php echo $pageHeading; ?></h2>
<?php endif; ?>

 <?php
    $itemClass = 'separated-item-23 col-md-12 ';
    $mergedModes = '' . '1' . '' . '';
    $str = 'col-lg-1 col-md-1 col-sm-1 col-xs-1';
?>
<?php $leadingcount=0 ; ?>
<?php if (!empty($this->lead_items)) : ?>
<div class="data-control-id-1619 bd-grid-9">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $leadingItemClass = preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . 12, $str);
    ?>
    <?php foreach ($leadArticles as &$item) : ?>
        <div class="<?php echo $leadingItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
            <div class="bd-griditem-23">
            <?php
                $this->item = &$item;
                echo $this->loadTemplate('item');
            ?>
            </div>
        </div>
        <?php $leadingcount++; ?>
    <?php endforeach; ?>
        </div>
   </div>
</div>
<?php endif; ?>
<?php
    $introcount = (count($this->intro_items));
    $counter = 0;
?>
<?php if (!empty($this->intro_items)) : ?>
<div class="data-control-id-1619 bd-grid-9">
  <div class="container-fluid">
    <div class="separated-grid row">
    <?php
        $introItemClass = $itemClass;
        if ('' === $mergedModes)
            $introItemClass = $introItemClass . preg_replace('/col-(lg|md|sm|xs)-\d+/', 'col-$1-' . round(12 / min(12, max(1, $this->columns))), $str);
    ?>
    <?php foreach ($introArticles as $key => &$item) : ?>
    <?php
        $rowcount = ((int) $key % (int) $this->columns) + 1;
        $row = $counter / $this->columns ;
    ?>
    <?php $counter++; ?>
    <div class="<?php echo $introItemClass; ?>" itemprop="blogPost" itemscope itemtype="http://schema.org/BlogPosting">
        <div class="bd-griditem-23">
    <?php
        $this->item = &$item;
        echo $this->loadTemplate('item');
    ?>
        </div>
    </div>
    <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php  if (!empty($this->children[$this->category->id])&& $this->maxLevel != 0) : ?>
    <div class="cat-children">
    <?php
        echo renderTemplateFromIncludes('article_6', array(array('header-text' => JTEXT::_('JGLOBAL_SUBCATEGORIES'),
            'content' => $this->loadTemplate('children'))));
    ?>
    </div>
<?php endif; ?>
<?php if (count($pagination_list) > 0) : ?>
    <div class="data-control-id-1657 bd-blogpagination-5">
        <?php
            echo renderTemplateFromIncludes('pagination_list_render_5', array($pagination_list));
        ?>
    </div>
<?php endif; ?>
</div>

<?php
echo ob_get_clean();
?>
<!--COMPONENT blog_8 /-->