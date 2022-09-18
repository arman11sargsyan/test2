<?php

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

fn_register_hooks(
    'create_order',
    'variation_group_create_products_by_combinations_item',
    'delete_product_post',
    'order_placement_routines',
    'delete_order'
);
