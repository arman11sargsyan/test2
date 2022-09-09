<?php
unset($schema['central']['settings']['items']['files']);
unset($schema['central']['vendors']['items']['vendor_accounting']);
unset($schema['central']['settings']['items']['sync_data']);
unset($schema['central']['marketing']);

$schema['central']['products']['items']['product_bundles.product_bundles'] = [
    'attrs' => [
        'class' => 'is-addon'
    ],
    'href' => 'product_bundles.manage',
    'position' => 300,
];

$schema['central']['settings']['items']['vendor_accounting'] = [
    'href'       => 'companies.balance',
    'position'   => 220,
];
return $schema;