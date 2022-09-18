<?php
$schema['controllers']['wk_woocommerce'] = array (
    'permissions' => true,
);
$schema['controllers']['wk_woocommerce_product'] = array (
    'permissions' => true,
);

$schema['controllers']['wk_woocommerce_order'] = array (
    'permissions' => true,
);

$schema['controllers']['tools']['modes']['update_status']['param_permissions']['table']['wk_woocommerce_store'] = true;

return $schema;