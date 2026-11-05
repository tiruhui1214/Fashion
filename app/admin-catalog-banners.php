<?php
$targets = catalog_banner_targets();
$scope = (string)($_GET['target'] ?? 'all');
if (!isset($targets[$scope])) $scope = 'all';
$banner = catalog_banner($scope);
$options = [];
foreach ($targets as $key => $target) $options[$key] = $target['label'];
$previewParams = [];
if ($banner['department']) $previewParams['department'] = $banner['department'];
if ($banner['category_id']) $previewParams['category'] = $banner['category_id'];
?>
<p class="admin-description">These short landscape banners appear above product filters. They are display-only: no shopping button and no destination link. Homepage department-card images remain separate.</p>
<form method="get" class="inline-form">
    <input type="hidden" name="page" value="admin"><input type="hidden" name="section" value="catalog">
    <?php aselect('target', 'Choose a department or category page', $options, $scope); ?>
    <button class="btn btn-outline">Load banner</button>
</form>
<div class="admin-split">
    <section class="admin-card">
        <h2><?=e($targets[$scope]['label'])?></h2>
        <div class="catalog-banner catalog-banner-preview" style="--banner-text:<?=e($banner['text_color'])?>">
            <img src="<?=e(safe_image($banner['image']))?>" alt="Current banner preview">
            <div class="catalog-banner-shade"></div>
            <div class="catalog-banner-copy"><p class="eyebrow">THE COLLECTION</p><h3><?=e($banner['heading'])?></h3><p><?=e($banner['subtitle'])?></p></div>
        </div>
        <p>Recommended desktop image: 1920 × 600. Optional mobile image: 900 × 600. Keep important subjects away from the edges because images are cropped to fit.</p>
        <?php if ($banner['category_id']): ?><p>Until this category has its own image, it uses its department's banner. Saving this form creates independent settings for this category.</p><?php endif; ?>
        <a class="text-link" href="<?=e(url('catalog', $previewParams))?>">View category page →</a>
    </section>
    <section class="admin-card">
        <h2>Edit display banner</h2>
        <form method="post" enctype="multipart/form-data" class="form-grid">
            <?php admin_hidden('admin_catalog_banner_save','catalog'); ?>
            <input type="hidden" name="target" value="<?=e($scope)?>">
            <?php
            afield('title','Heading',$banner['heading']);
            afield('subtitle','Supporting text',$banner['subtitle']);
            afield('image','Desktop banner · JPG / PNG / WebP · max 5 MB','','file',false);
            afield('mobile_image','Optional mobile banner','','file',false);
            acheck('clear_mobile','Use desktop image on mobile',false);
            afield('text_color','Text colour',$banner['text_color'],'color');
            ?>
            <small>Leave image fields empty to retain current images. This banner has no clickable area or link.</small>
            <button class="btn">Save category banner</button>
        </form>
        <form method="post" class="banner-reset" data-confirm="Reset this page banner? The default or department banner will be used. Uploaded files will be retained.">
            <?php admin_hidden('admin_catalog_banner_reset','catalog'); ?>
            <input type="hidden" name="target" value="<?=e($scope)?>">
            <button class="text-button">Reset to default / department banner</button>
        </form>
    </section>
</div>
