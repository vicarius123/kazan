<?php function article_2($data) {
    ob_start();
    $attr = '';
    if (isset($data['article_id'])) {
        $attr = ' id="' . $data['postcontent_editor_id'] . '"';
    }
    $postIdClass = '';
    if (isset($data['post_id_class'])) {
        $postIdClass = ' data-post-value="data-post-id-' . $data['post_id_class'] . '"';
    }
    ?>
        
        <article class=" bd-article-2"<?php echo $attr; ?><?php echo $postIdClass; ?>>
            <h2 class=" bd-postheader-2"  itemprop="name">
    <?php if (isset($data['header-text']) && strlen($data['header-text'])) : ?>
        <?php if (isset($data['header-link']) && strlen($data['header-link'])) : ?>
            <a <?php echo funcBuildRoute($data['header-link'], 'href'); ?>>
                <?php echo $data['header-text'];?>
            </a>
        <?php else: ?>
            <?php echo $data['header-text']; ?>
        <?php endif; ?>
    <?php endif; ?>
</h2>
	
		<div class=" bd-layoutbox-4 clearfix">
    <div class="bd-container-inner">
        <?php if (isset($data['data-image'])) : ?>
<?php
    $image = $data['data-image'];
    $caption = $image['caption'];
    ?>
<div class=" bd-postimage-1">
    
    <?php if (isset($image['link']) && $image['link'] !== '') : ?>
    <a href="<?php echo $image['link']; ?>">
        <?php endif; ?>
        <img src="<?php echo $image['image']; ?>" alt="<?php echo $image['alt']; ?>" class=" bd-imagestyles-6" itemprop="image"/>
        <?php if (isset($image['link']) && $image['link'] !== '') : ?>
    </a>
    <?php endif; ?>
    
    <?php if ($caption): ?>
    <div class=" bd-container-50 bd-tagstyles ">
        <?php echo $caption; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
	
		<div class=" bd-postcontent-2 bd-tagstyles bd-custom-blockquotes bd-custom-bulletlist bd-custom-orderedlist bd-custom-table " itemprop="articleBody">
    <?php
        $attr = '';
        if (isset($data['postcontent_editor_id'])) {
            $attr = ' data-editable-id="' . $data['postcontent_editor_id'] . '"';
        }
    ?>
    <div class="bd-container-inner"<?php echo $attr; ?>>
        <?php if (isset($data['content']) && strlen($data['content'])) : ?>
            <?php
                $content = funcPostprocessPostContent($data['content']);
                echo funcContentRoutesCorrector($content);
            ?>
        <?php endif; ?>
    </div>
</div>
	
		<?php if (isset($data['readmore-link']) && isset($data['readmore-text']) ) : ?>
<a class="bd-postreadmore-1 bd-button " href="<?php echo $data['readmore-link'] ?>" >
    <?php echo $data['readmore-text'] ?></a>
<?php endif; ?>
    </div>
</div>
        </article>
        <div class="bd-container-inner"><?php if (isset($data['pager'])) : ?>
<div class=" bd-pager-2">
    <ul class=" bd-pagination pager">
        <?php if (preg_match('/<li[^>]*previous[^>]*>([\S\s]*?)<\/li>/', $data['pager'], $prevMatches)) : ?>
        <li class=" bd-paginationitem-1"><?php echo funcContentRoutesCorrector($prevMatches[1]); ?></li>
        <?php endif; ?>
        <?php if (preg_match('/<li[^>]*next[^>]*>([\S\s]*?)<\/li>/', $data['pager'], $nextMatches)) : ?>
        <li class=" bd-paginationitem-1"><?php echo funcContentRoutesCorrector($nextMatches[1]); ?></li>
        <?php endif; ?>
    </ul>
</div>
<?php endif; ?></div>
        
<?php
    return ob_get_clean();
}