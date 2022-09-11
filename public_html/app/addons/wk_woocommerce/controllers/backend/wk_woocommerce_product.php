<?php

use Tygh\Registry;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'import_products') {
        
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            $account_id = $_REQUEST['account_id'];
            $account_data = fn_get_wk_woocommerce_account_data($account_id);
            $status = (!empty($_REQUEST['woocommerce_product_status'])?$_REQUEST['woocommerce_product_status']:'any');
            if ($account_data) {
                if (isset($_REQUEST['woocommerce_product_id']) && !empty($_REQUEST['woocommerce_product_id'])) {
                    $result = fn_import_products_from_woocommerce($account_data, $_REQUEST['woocommerce_product_id']);
                } else {
                    $result = fn_import_products_from_woocommerce($account_data, array(), $status);
                }
            }
            $suffix = 'manage&account_id='.$account_id;
        }
        return array(CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce_product.'.$suffix);
    }
}

if ($mode == 'manage') {
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        $merchant_data = fn_get_wk_woocommerce_account_data($_REQUEST['account_id']);
        if(empty($merchant_data)){
            return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage'];
        }

        Registry::get('view')->assign('account_id', $_REQUEST['account_id']);
        list($product_list,$search) = fn_get_woocommerce_products_list($_REQUEST['account_id'], $_REQUEST);
        Registry::get('view')->assign('product_list', $product_list);
        Registry::get('view')->assign('search', $search);
    } else {
        return array(CONTROLLER_STATUS_DENIED, 'wk_woocommerce_product.manage');
    }
}