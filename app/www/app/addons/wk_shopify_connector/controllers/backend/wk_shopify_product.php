<?php

use Tygh\Addons\ShopifyConnector\Api\WkShopify;
use Tygh\Addons\ShopifyConnector\Api\ShopifyClient;
use Tygh\Registry;


if (!defined('BOOTSTRAP')) {
    die('Access Denied');
}

$wkShopify = new WkShopify();

if ($mode == 'manage') {
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        list($products, $params) = $wkShopify->fetchShopifyImportedProduct($_REQUEST['account_id']);

        // $collection_list = $wkShopify->getCollectionsToImport($_REQUEST['account_id'],$_REQUEST);
        Tygh::$app['view']->assign('product_list', $products);
        // Tygh::$app['view']->assign('collection_list', $collection_list);
        Tygh::$app['view']->assign('search1', $params);

        /* collection code */
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            $id = $_REQUEST['account_id'];
            list($smart_collection_list, $param1) = $wkShopify->getSmartCollectionsToImport($_REQUEST['account_id'], $_REQUEST);
            list($custom_collection_list, $param2) = $wkShopify->getCustomCollectionsToImport($_REQUEST['account_id'], $_REQUEST);
            Tygh::$app['view']->assign('custom_collection_list', $custom_collection_list);
            Tygh::$app['view']->assign('smart_collection_list', $smart_collection_list);
            Tygh::$app['view']->assign('search2', $param1);
            Tygh::$app['view']->assign('search3', $param2);

            $tabs = array(
                'smart_'.$id => array(
                    'title' => __('smart_'),
                    'js' => true,
                ),
                'custom_'.$id => array(
                    'title' => __('custom_'),
                    'js' => true,
                ),
            );

            Registry::set('navigation.tabs', $tabs);
        }
        /* collection code */
        // return[CONTROLLER_STATUS_OK, wk/*  */
    } else {
        return [CONTROLLER_STATUS_OK, 'wk_shopify.manage'];
    }
}
// if($mode == 'collection')
// {
//     if(isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
//         $id = $_REQUEST['account_id'];
//         list($smart_collection_list, $param1) = $wkShopify->getSmartCollectionsToImport($_REQUEST['account_id'], $_REQUEST);
//         list($custom_collection_list, $param2) = $wkShopify->getCustomCollectionsToImport($_REQUEST['account_id'], $_REQUEST);
//         Tygh::$app['view']->assign('custom_collection_list', $custom_collection_list);
//         Tygh::$app['view']->assign('smart_collection_list', $smart_collection_list);
//         Tygh::$app['view']->assign('search2', $param1);
//         Tygh::$app['view']->assign('search3', $param2);

//         $tabs = array(
//             'smart_' . $id => array(
//                 'title' => __('smart_'),
//                 'js' => true,
//             ),
//             'custom_' . $id => array(
//                 'title' => __('custom_'),
//                 'js' => true,
//             )
//         );

//         Registry::set('navigation.tabs', $tabs);
//     } else {
//         return[CONTROLLER_STATUS_OK , 'wk_shopify.manage'];
//     }
// }
if ($mode == 'm_map_sync') {
    $mapped_count = 0;
    $account_id = $_REQUEST['account_id'];
    $collection_id = $_REQUEST['collection_id'];
    if (!empty($_REQUEST['map_ids'])) {
        foreach ($_REQUEST['map_ids'] as $collection_id => $product_arr) {
            $total_product = count($product_arr);
            fn_set_progress('echo', __('total_product_for_collection_id', array('[product_count]' => $total_product, '[collection_id]' => $collection_id)));
            foreach ($product_arr as $key => $product_id) {
                list($product_id, $shopify_product, $has_option) = $wkShopify->importShopifyProduct($product_id, $account_id, $collection_id);
                if (isset($product_id) && !empty($product_id)) {
                    ++$mapped_count;
                    fn_set_progress('parts', $total_product);
                    fn_set_progress('echo', __('total_mapped_product_for_collection_id', array('[product_count]' => $total_product, '[collection_id]' => $collection_id, '[mapped_count]' => $mapped_count)));
                }
                if ($has_option) {
                    // fn_create_variation_of_shopify_product($product_id, $shopify_product);
                }
            }
            fn_set_notification('N', __('notice'), __('products_successfully_sync'));
        }
    }

    $_REQUEST['dispatch'] = 'wk_shopify_product.manage&account_id='.$account_id;
    // return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify_product.manage&account_id=' . $account_id];

    Tygh::$app['ajax']->assign('force_redirection', fn_url('wk_shopify_product.manage&account_id='.$account_id));
    Tygh::$app['ajax']->assign('non_ajax_notifications', true);
    exit;
}
if ($mode == 'map_sync') {
    $account_id = $_REQUEST['account_id'];
    $dispatch = $_REQUEST['dispatch'];
    list($product_id, $shopify_product, $has_option) = $wkShopify->importShopifyProduct($_REQUEST['product_id'], $_REQUEST['account_id'], $_REQUEST['collection_id']);
    if ($has_option) {
        // fn_create_variation_of_shopify_product($product_id, $shopify_product);
    }
    $_REQUEST['dispatch'] = 'wk_shopify_product.manage&account_id='.$account_id;
    // fn_redirect('wk_shopify_product.manage&account_id='.$account_id.'&pro='.$product_id);
    return [CONTROLLER_STATUS_OK, 'wk_shopify_product.manage&account_id='.$account_id.'&pro='.$product_id];
}
if ($mode == 'list_collection_products') {
    if (!empty($_REQUEST['account_id'])) {
        $product_arr = [];
        $_REQUEST['redirect_url'] = '';
        $cscartProductIds = $search = array();
        if (!empty($_REQUEST['colection_id'])) {
            list($product_arr[$_REQUEST['colection_id']], $search) = $wkShopify->getCollectByCollectionId($_REQUEST['account_id'], $_REQUEST['colection_id'], $_REQUEST);
        } elseif (!empty($_REQUEST['smart_collection_ids'])) {
            $collectionId = $_REQUEST['smart_collection_ids'];
            // foreach ($_REQUEST['smart_collection_ids'] as $key => $collectionId) {
            list($product_arr[$collectionId], $search, $cscartProductIds) = $wkShopify->getCollectByCollectionId($_REQUEST['account_id'], $collectionId, $_REQUEST);
            // array_push($product_arr, $product_list);
            // }
        } /* elseif (isset($_REQUEST['custom_collection_ids']) && !empty($_REQUEST['custom_collection_ids'])) {

        foreach ($_REQUEST['custom_collection_ids'] as $key => $collectionId) {
        $product_arr[$collectionId] = $wkShopify->getCollectByCollectionId($_REQUEST['account_id'], $collectionId, $_REQUEST);
        // array_push($product_arr, $product_list);
        }
        } */
        // $products_list = call_user_func_array("array_merge", $product_arr);
        Tygh::$app['view']->assign('cscartProductIds', $cscartProductIds);
        Tygh::$app['view']->assign('product_arr', $product_arr);
        Tygh::$app['view']->assign('search', $search);
        fn_set_notification('N', 'Notice', __('finish_import_product'));
    } else {
        return [CONTROLLER_STATUS_OK, 'wk_shopify_product.manage'];
    }
}
// }
// if($_SERVER['REQUEST_METHOD'] == 'POST') {

if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
    Tygh::$app['view']->assign('account_id', $_REQUEST['account_id']);
}
if ($mode == 'all_sync') {
    if (isset($_REQUEST['account_id'])) {
        if (!empty($_REQUEST['colection_id'])) {
            $collectionId = $_REQUEST['collection_id'];
        } elseif (!empty($_REQUEST['smart_collection_ids'])) {
            $collectionId = $_REQUEST['smart_collection_ids'];
        }
        if(!empty($collectionId)){
        $credentials = $wkShopify->getCredentialById($_REQUEST['account_id']);
        $wkshopify = new ShopifyClient($credentials['domain_name'], $credentials['api_secret'], $credentials['api_key'], $credentials['api_secret']);

        // $ssssproduct_list = $wkshopify->call('GET', '/admin/satatusist.json');

        // fn_print_die($ssssproduct_list);
        // $tt_count_data = $wkshopify->call('GET', '/admin/products/count.json');
        $product_list = $wkshopify->call('GET', '/admin/products.json?fields=id,updated_at,title,created_at&collection_id='.$collectionId);
        // fn_print_die($product_list);
        
        // fn_print_die($product_list,$tt_count_data,$_REQUEST);
        $shopifyProductIds = array_column($product_list, 'id');
        foreach ($shopifyProductIds as $key => $value) {
            if ($value) {
                $wkShopify->updateProductData($value, Tygh::$app['session']['auth']);
            }
        }
        }else{
                
            fn_set_notification('N', __('message'), __('select_anid'));

        }
    }

    return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify_product.manage&account_id='.$_REQUEST['account_id']];

    // fn_print_r($shopifyProductIds);
}