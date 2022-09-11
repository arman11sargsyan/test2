<?php

use Tygh\Registry;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'import_orders') {
        
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            $account_id = $_REQUEST['account_id'];
            $account_data = fn_get_wk_woocommerce_account_data($account_id);
            $status = (!empty($_REQUEST['woocommerce_product_status'])?$_REQUEST['woocommerce_product_status']:'any');
            
            if ($account_data) {
                $result = fn_import_orders_from_woocommerce($account_data, $_REQUEST);
                if (!empty($result)) {
                    fn_set_notification("N", __("notice"), count($result) .'&nbsp;'.__("order_imported_on_store"), 'S');
                } else {
                    fn_set_notification("N", __("notice"), __("no_new_orders_to_import"), 'S');
                    
                }
            }
            $suffix = 'manage&account_id='.$account_id;
            return array(CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce_order.'.$suffix);
        }
        return array(CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage');
    }
}

if ($mode == 'manage') {
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        $merchant_data = fn_get_wk_woocommerce_account_data($_REQUEST['account_id']);
        if(empty($merchant_data)){
            return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage'];
        }
        Registry::get('view')->assign('account_id', $_REQUEST['account_id']);
        list($orders,$search) = fn_get_woocommerce_orders_list($_REQUEST['account_id'], $_REQUEST);
        Registry::get('view')->assign('orders', $orders);
        Registry::get('view')->assign('search', $search);
    } else {
        return array(CONTROLLER_STATUS_DENIED, 'wk_woocommerce.manage');
    }
}

if ($mode == 'list_orders') {
    $merchant_data = fn_get_wk_woocommerce_account_data($_REQUEST['account_id']);
        if(empty($merchant_data)){
            return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage'];
        }
     if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {

        $account_data = fn_get_wk_woocommerce_account_data($_REQUEST['account_id']);
        list($orders, $search) = wk_list_woocommerce_orders($account_data, $_REQUEST);
        Registry::get('view')->assign('account_id', $_REQUEST['account_id']);        
        Tygh::$app['view']->assign('orders', $orders);
        Tygh::$app['view']->assign('search', $search);

    } else {
        return [CONTROLLER_STATUS_DENIED, 'wk_woocommerce_order.list_orders'];
    }
}