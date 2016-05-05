<?php function article_7($data) {
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
        
        <article class="data-control-id-1258714 bd-article-7"<?php echo $attr; ?><?php echo $postIdClass; ?>>
            <div class="data-control-id-1258775 bd-postcontent-7 bd-tagstyles bd-custom-blockquotes bd-custom-bulletlist bd-custom-orderedlist bd-custom-table " itemprop="articleBody">
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
        </article>
        <div class="bd-container-inner"><?php if (isset($data['pager'])) : ?>
<div class="data-control-id-1258713 bd-pager-7">
    <ul class="data-control-id-1258712 bd-pagination pager">
        <?php if (preg_match('/<li[^>]*previous[^>]*>([\S\s]*?)<\/li>/', $data['pager'], $prevMatches)) : ?>
        <li class="data-control-id-1258848 bd-paginationitem-1"><?php echo funcContentRoutesCorrector($prevMatches[1]); ?></li>
        <?php endif; ?>
        <?php if (preg_match('/<li[^>]*next[^>]*>([\S\s]*?)<\/li>/', $data['pager'], $nextMatches)) : ?>
        <li class="data-control-id-1258848 bd-paginationitem-1"><?php echo funcContentRoutesCorrector($nextMatches[1]); ?></li>
        <?php endif; ?>
    </ul>
</div>
<?php endif; ?></div>
        
<?php
    return ob_get_clean();
}