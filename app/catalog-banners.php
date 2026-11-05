<?php
/** Display-only catalogue banners. Settings storage keeps existing installations compatible. */
function catalog_banner_targets(): array {
    $targets = [
        'all' => ['label' => 'All products / New In', 'department' => '', 'category_id' => 0, 'title' => 'The collection'],
        'department_women' => ['label' => 'Women department', 'department' => 'Women', 'category_id' => 0, 'title' => 'Women'],
        'department_men' => ['label' => 'Men department', 'department' => 'Men', 'category_id' => 0, 'title' => 'Men'],
        'department_kids' => ['label' => 'Kids department', 'department' => 'Kids', 'category_id' => 0, 'title' => 'Kids'],
    ];
    foreach (all('SELECT id,department,name FROM categories ORDER BY department,name') as $category) {
        $targets['category_' . $category['id']] = [
            'label' => $category['department'] . ' / ' . $category['name'],
            'department' => $category['department'],
            'category_id' => (int)$category['id'],
            'title' => $category['name'],
        ];
    }
    return $targets;
}
function catalog_banner(string $scope): array {
    $targets = catalog_banner_targets();
    $target = $targets[$scope] ?? $targets['all'];
    if (!isset($targets[$scope])) $scope = 'all';
    $department = strtolower($target['department'] ?: 'women');
    $taglines = [
        'women' => 'An effortless wardrobe, in your own way.',
        'men' => 'Considered essentials. A modern point of view.',
        'kids' => 'Little adventures. Everyday possibilities.',
    ];
    $departmentScope = $target['category_id'] ? 'department_' . $department : $scope;
    $fallbackImage = setting('catalog_' . $departmentScope . '_image', 'assets/catalog-' . $department . '.svg');
    $fallbackMobile = $target['category_id'] ? setting('catalog_' . $departmentScope . '_mobile_image', '') : '';
    $fallbackColor = $target['category_id'] ? setting('catalog_' . $departmentScope . '_text_color', '#FFFFFF') : '#FFFFFF';
    return $target + [
        'scope' => $scope,
        'image' => setting('catalog_' . $scope . '_image', $fallbackImage),
        'mobile_image' => setting('catalog_' . $scope . '_mobile_image', $fallbackMobile),
        'heading' => setting('catalog_' . $scope . '_title', $target['title']),
        'subtitle' => setting('catalog_' . $scope . '_subtitle', $taglines[$department]),
        'text_color' => setting('catalog_' . $scope . '_text_color', $fallbackColor),
    ];
}
function catalog_banner_keys(string $scope): array {
    return array_map(fn($field) => 'catalog_' . $scope . '_' . $field, ['image','mobile_image','title','subtitle','text_color']);
}
