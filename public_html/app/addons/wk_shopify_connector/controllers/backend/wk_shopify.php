<?php

use Tygh\Addons\ShopifyConnector\Api\WkShopify;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access Denied');
}

$wkShopify = new WkShopify();
if ($mode == 'manage') {
    $params = array();

    $params = array_merge($params, $_REQUEST);
    list($shops, $params) = $wkShopify->getShopList($params);
    Tygh::$app['view']->assign('merchants', $shops);
    Tygh::$app['view']->assign('search', $params);
}

if ($mode == 'add' || $mode == 'update') {
    Registry::set(
        'navigation.tabs', array(
            'shopify_general' => array(
                'title' => __('general'),
                'js' => true,
            ),
            'order_settings' => array(
                'title' => __('order_settings'),
                'js' => true,
            ),
            'product_settings' => array(
                'title' => __('product_settings'),
                'js' => true,
            ),
            'webhook_setting' => array(
                'title' => __('webhook_setting'),
                'js' => true,
            ),
        ));

    if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
        $data = $wkShopify->getShopDetailById($_REQUEST['id']);
        // $currencies = Registry::get('currencies');
        Tygh::$app['view']->assign('merchant_data', $data);
        // Tygh::$app['view']->assign('currencies', $currencies);
    }
    $payment_arr = fn_get_payments();
    $shipping_arr = fn_get_shippings(true);
    Tygh::$app['view']->assign('payment_arr', $payment_arr);
    Tygh::$app['view']->assign('shipping_arr', $shipping_arr);
}

if ($mode == 'order_manage') {
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        list($orders, $shopifyOrders, $search) = $wkShopify->fetchOrdersByShop($_REQUEST['account_id']);

        Tygh::$app['view']->assign('orders', $orders);
        Tygh::$app['view']->assign('search', $search);
        Tygh::$app['view']->assign('shopifyOrders', $shopifyOrders);
    }
}

if ($mode == 'delete') {
    if (isset($_REQUEST['del_pro']) && $_REQUEST['del_pro']) {
        $shops = $wkShopify->deleteShop($_REQUEST['id'], 1);
    } else {
        $shops = $wkShopify->deleteShop($_REQUEST['id']);
    }

    return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.manage'];
}

if ($mode == 'category_map') {
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        $categoryMapped = $wkShopify->getMappedCategory($_REQUEST['account_id']);
        $account_shopify_categories = $wkShopify->getCollections($_REQUEST['account_id']);
        Tygh::$app['view']->assign('categories_mapped', $categoryMapped);
        Tygh::$app['view']->assign('shopify_collection', $account_shopify_categories);
        Tygh::$app['view']->assign('id', $_REQUEST['account_id']);
    }
}
/* delete category map */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'authenticate') {
        $credentials = $_REQUEST['merchant_data'];
        if (isset($credentials['domain_name']) && !empty($credentials['domain_name']) && isset($credentials['shopify_shared_secret_key']) && isset($credentials['shopify_api_key']) && !empty($credentials['shopify_shared_secret_key']) && !empty($credentials['shopify_api_key'])) {
            $wkShopify->authenticate($credentials);

            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.manage'];
        }
    }
    if ($mode == 'category_map' && isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        
    if(empty( $_REQUEST['category_name'] )){
        fn_set_notification('N', 'notice', __('please_select_the_category'));
    }else{
        $res = $wkShopify->categoryMap($_REQUEST);
    }
        // if($res)
        return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.category_map&account_id='.$_REQUEST['account_id']];
    }
    if ($mode == 'update') {
        // fn_print_die($_REQUEST);
        if ($_REQUEST['id']) {
            $merchantData = $_REQUEST['merchant_data'];
            if (isset($merchantData) && !empty($merchantData)) {
                $data = [
                    'default_cscart_category_id' => $merchantData['default_cscart_category_id'],
                    'shopify_currency_code' => $merchantData['shopify_currency_code'],
                    'default_payment' => $merchantData['default_payment'],
                    'default_shipping' => $merchantData['default_shipping'],
                    'order_close_status' => $merchantData['order_close_status'],
                    'order_cancel_status' => $merchantData['order_cancel_status'],
                    'wk_data_for_variaton_one_or_not' => $merchantData['shopify_variaton_one_or_not'],
                    'wk_shopify_draft_product_import' => $merchantData['wk_data_for_shopify_draft_product'],

                ];
                db_query('UPDATE ?:wk_shopify_store SET ?u WHERE shop_id = ?i', $data, $_REQUEST['id']);
            }

            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.manage'];
        }
    }
    if ($mode == 'delete_category_map') {
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id']) && isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
            db_query('DELETE FROM ?:wk_shopify_category_map WHERE account_id = ?i AND id = ?i', $_REQUEST['account_id'], $_REQUEST['id']);

            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.category_map&account_id='.$_REQUEST['account_id']];
        } else {
            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.manage'];
        }
    }
    if ($mode == 'deletehook') {
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
            $res = $wkShopify->deleteHooks($_REQUEST['id']);

            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.update&id='.$_REQUEST['id']];
        } else {
            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.manage'];
        }
    }

    if ($mode == 'registerhook') {
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
            $res = $wkShopify->registerHooks($_REQUEST['id']);

            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.update&id='.$_REQUEST['id']];
        } else {
            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.manage'];
        }
    }
    if ($mode == 'm_import_orders') {
        unset(Tygh::$app['session']['cart']);
        if (!empty($_REQUEST['account_id']) && !empty($_REQUEST['map_ids'])) {
            $account_id = $_REQUEST['account_id'];
            $order_arr = $_REQUEST['map_ids'];
            $total_order = count($order_arr);
            foreach ($order_arr as $key => $orderId) {
                unset(Tygh::$app['session']['cart']);
                $order_id = @fn_wk_shopify_create_cscart_order($account_id, $orderId);
                if ($orderId && $order_id) {
                    $data = array(
                        'account_id' => $account_id,
                        'order_id' => $order_id,
                        'shopify_order_id' => $orderId,
                    );
                    db_query('INSERT INTO ?:wk_shopify_order_map ?e', $data);
                }
            }
            fn_set_notification('N', 'notice', __('order_place'));

            return array(CONTROLLER_STATUS_REDIRECT, 'wk_shopify.order_manage&account_id='.$account_id);
        } else {
            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.manage'];
        }
    }
    if ($mode == 'import_order') {
        unset(Tygh::$app['session']['cart']);
        if (!empty($_REQUEST['account_id']) && !empty($_REQUEST['order_id'])) {
            $shop_id = $_REQUEST['account_id'];
            if (!empty($_REQUEST['order_id'])) {
                $order_id = fn_wk_shopify_create_cscart_order($shop_id, $_REQUEST['order_id']);
                if(!empty($order_id)) {
                    $data = array(
                        'account_id' => $shop_id,
                        'order_id' => $order_id,
                        'shopify_order_id' => $_REQUEST['order_id'],
                    );
                    db_query('INSERT INTO ?:wk_shopify_order_map ?e', $data);
                    fn_set_notification('N', 'notice', __('order_place'));
                }

            }
           

            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.order_manage&account_id='.$shop_id];
        } else {
            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.manage'];
        }
    }
}

if ($mode == 'import_order1') {
    unset(Tygh::$app['session']['cart']);
    if (!empty($_REQUEST['account_id']) && !empty($_REQUEST['order_id'])) {
        $shop_id = $_REQUEST['account_id'];
        if (!empty($_REQUEST['order_id'])) {
            $order_id = fn_wk_shopify_create_cscart_order($shop_id, $_REQUEST['order_id']);
            if(!empty($order_id)) {
                $data = array(
                    'account_id' => $shop_id,
                    'order_id' => $order_id,
                    'shopify_order_id' => $_REQUEST['order_id'],
                );
                db_query('INSERT INTO ?:wk_shopify_order_map ?e', $data);
                fn_set_notification('N', 'notice', __('order_place'));
            }

        }
       

        return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.order_manage&account_id='.$shop_id];
    } else {
        return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.manage'];
    }
}

if ($mode == 'shipping_manage' || $mode == 'shipping_map' || $mode == 'mapshipping') {
    if (!empty($_REQUEST['account_id'])) {
        $shopify_shipping_zone = $wkShopify->fetchShippingZone($_REQUEST);
        $shipping_data = fn_shopify_filter_shipping_zone_data($shopify_shipping_zone);
        Tygh::$app['view']->assign('shipping_zones', $shipping_data);
        if ($mode == 'shipping_map') {
            $destinations = fn_get_destinations(DESCR_SL);
            Tygh::$app['view']->assign('destinations', $destinations);
            Tygh::$app['view']->assign('zone_id', $_REQUEST['zone_id']);
            Tygh::$app['view']->assign('countries', $shipping_data[$_REQUEST['zone_id']]['country']);
        }
        if ($mode == 'mapshipping') {
            $account_id = $_REQUEST['account_id'];
            $destinations = fn_get_destinations(DESCR_SL);
            fn_shopify_map_shipping($shipping_data[$_REQUEST['zone_id']], $_REQUEST, $destinations);
            $_REQUEST['dispatch'] = 'wk_shopify.shipping_manage?account_id='.$account_id;

            return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.shipping_manage?account_id='.$account_id];
        }
    }
}
if ($mode == 'list_shopify_orders') {
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        $params = $_REQUEST;

        list($orders, $search) = $wkShopify->listOrders($_REQUEST);
        $synced_orders = db_get_fields('SELECT shopify_order_id FROM ?:wk_shopify_order_map WHERE account_id = ?i', $_REQUEST['account_id']);
        Tygh::$app['view']->assign('orders', $orders);
        Tygh::$app['view']->assign('search', $search);
        Tygh::$app['view']->assign('synced_orders', $synced_orders);
    } else {
        return [CONTROLLER_STATUS_REDIRECT, 'wk_shopify.manage'];
    }
}

if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
    Tygh::$app['view']->assign('account_id', $_REQUEST['account_id']);
}
if ($mode == 'order_export') {
    $order_id = $_REQUEST['order_id'];
    $res = fn_wk_shopify_export_order($order_id);
    if ($res == 'true') {
        fn_set_notification('N', __('message'), 'Exported_successfully');

        return [CONTROLLER_STATUS_REDIRECT, 'orders.details&order_id='.$order_id];
    }else{
        if($res == 'done'){
            $order_sync_data_in_cscart_to_get_shopify_id = db_get_row('SELECT * FROM ?:wk_shopify_order_map WHERE order_id = ?i', $order_id);
            fn_set_notification('N', __('message'), __('order_already_exported').$order_sync_data_in_cscart_to_get_shopify_id['shopify_order_id']);
            return [CONTROLLER_STATUS_REDIRECT, 'orders.details&order_id='.$order_id];
        }
        else{
        fn_set_notification('N', __('message'), __('not_shopify_products'));

        return [CONTROLLER_STATUS_REDIRECT, 'orders.details&order_id='.$order_id];
        }
    }
}