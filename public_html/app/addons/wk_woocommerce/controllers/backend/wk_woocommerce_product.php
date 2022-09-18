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
                if (isset($_REQUEST['map_ids']) && !empty($_REQUEST['map_ids'])) {
                    $result = fn_import_products_from_woocommerce($account_data, $_REQUEST['map_ids']);
                }
                else{
                    $asdata = array($_REQUEST['product_id']=>$_REQUEST['product_id']);
                    $result = fn_import_products_from_woocommerce($account_data, $asdata);
                }
            }
            $suffix = 'manage&account_id='.$account_id;
            // fn_print_r($suffix);
        }
        return array(CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce_product.'.$suffix);
    }
}






if ($mode == 'list_collection_products') {
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        $account_id = $_REQUEST['account_id'];
        $account_data = fn_get_wk_woocommerce_account_data($account_id);
        $status = (!empty($_REQUEST['woocommerce_product_status'])?$_REQUEST['woocommerce_product_status']:'any');
        $product_arr = [];
        $params = array(
            'page' => 1,
            'per_page' => 20,
            'status' => $status
        );
        if (defined('AJAX_REQUEST')) {
            if(isset($_REQUEST['items_per_page']) && !empty($_REQUEST['items_per_page'])){
                if($_REQUEST['items_per_page'] <= 100){
                    $params['per_page'] = $_REQUEST['items_per_page'];
                }
                else{
                $params['per_page'] = 100;
                }
            }
        }
        $params123 = [];
        $params123['page'] = 1;
        if (defined('AJAX_REQUEST')) {
        if(isset($_REQUEST['page']) && !empty($_REQUEST['page'])){
            $params['page'] = $_REQUEST['page'];
            $params123['page'] = $_REQUEST['page'];
            // fn_print_r($_REQUEST);
        }
        }


        $data = wk_woocommerce_api_call($account_data, 'GET', 'products/', $params);

        if(isset($_REQUEST['items_per_page']) && !empty($_REQUEST['items_per_page'])){
            $params123['items_per_page'] =$_REQUEST['items_per_page'];

        }else{
            $params123['items_per_page'] =20;

        }
        if(isset($data['header']['X-WP-Total']) && !empty($data['header']['X-WP-Total'])){
            $params123['total_items'] = $data['header']['X-WP-Total']; 
        }
        else{
            $params123['total_items'] = 10;
        }

        $_REQUEST['redirect_url'] = '';
        $cscartProductIds = $search = array();

        $product_list123 = fn_get_woocommerce_products_list_for_pagination_data($_REQUEST['account_id']);
        if(isset($data['header'])){
        unset($data['header']);
        }
        Tygh::$app['view']->assign('product_arr', $data);
        Tygh::$app['view']->assign('account_id', $account_id);
        Tygh::$app['view']->assign('search', $params123);
        Tygh::$app['view']->assign('allready_in_product', $product_list123);
        fn_set_notification('N', 'Notice', __('finish_import_product'));
    } else {
        return [CONTROLLER_STATUS_OK, 'wk_woocommerce_product.manage'];
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