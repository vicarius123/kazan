<?php
function hmenu_1() {
    $view = JFactory::getDocument()->view;
    $modulesContains = $view->containsModules('hmenu');
    $isPreview  = $GLOBALS['theme_settings']['is_preview'];
    if (isset($GLOBALS['isModuleContentExists']) && false == $GLOBALS['isModuleContentExists'])
        $GLOBALS['isModuleContentExists'] = $view->containsModules('hmenu') ? true : false;
    ?>
    <?php if ($isPreview || $modulesContains) : ?>
        
        <nav class="data-control-id-755 bd-hmenu-1" data-responsive-menu="true" data-responsive-levels="">
            <?php if ($view->containsModules('hmenu')) : ?>
            
                <div class="data-control-id-490147 bd-responsivemenu-11 collapse-button">
    <div class="bd-container-inner">
        <div class="data-control-id-857987 bd-menuitem-10">
            <a  data-toggle="collapse"
                data-target=".bd-hmenu-1 .collapse-button + .navbar-collapse"
                href="#" onclick="return false;">
                    <span>Menu</span>
            </a>
        </div>
    </div>
</div>
                <div class="navbar-collapse collapse">
            <?php echo $view->position('hmenu', '', '1', 'hmenu'); ?>
            
                </div>
            <?php else: ?>
                Please add a menu module in the 'hmenu' position
            <?php endif; ?>
        </nav>
        
    <?php endif; ?>
<?php
}