<?php

$schema['controllers']['wk_shopify_product'] = array(
    'permissions'=> array('GET'=> 'view_wk_shopify_connector', 'POST'=> 'manage_wk_shopify_connector'),
);

$schema['controllers']['wk_shopify'] = array(
    'permissions'=> array('GET'=> 'view_wk_shopify_connector', 'POST'=> 'manage_wk_shopify_connector'),
);

 $schema['controllers']['tools']['modes']['update_status']['param_permissions']['table']['wk_shopify_store'] = 'manage_wk_shopify_connector';

return $schema;
