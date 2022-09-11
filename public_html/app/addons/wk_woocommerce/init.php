<?php

if (!defined('BOOTSTRAP')) {
    die('Access Denied');
}
fn_register_hooks(
    'delete_product_post',
    'variation_group_create_products_by_combinations_item',
    'delete_order',
    'create_order'
);