<?php

use Tygh\Registry;
use Tygh\Settings;
use Tygh\Enum\ProductFeatures;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $suffix = '';
    if ($mode == 'authenticate') {
        $auth_url = '';
        if(isset($_REQUEST['merchant_data']) && !empty($_REQUEST['merchant_data'])) {
            $company_id =  Registry::get('runtime.company_id')?Registry::get('runtime.company_id'):$_REQUEST['merchant_data']['company_id'];
            $store_url =  $_REQUEST['merchant_data']['store_url'];
            $app_name =  $_REQUEST['merchant_data']['app_name'];
            
            $data = array(
                'store_url'  => $store_url,
                'app_name'   => $app_name,
                'company_id' => $company_id,
                'timestamp'  => TIME
            );
            if(!empty($_REQUEST['merchant_data']['shop_id'])){
                $shop_id = $_REQUEST['merchant_data']['shop_id'];
            }
            else
            {
                $shop_id_exists = db_get_field("SELECT `shop_id` FROM ?:wk_woocommerce_store WHERE store_url = ?s AND company_id = ?i",$store_url,$company_id);
                if($shop_id_exists)
                {
                    fn_set_notification("N", __("notice"), __("account_already_exist_for_vendor"), 'S');
                    return array(CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.add');
                }
                else
                {
                    $shop_id = db_query("INSERT INTO ?:wk_woocommerce_store ?e", $data);
                }
            }
            // $shop_id = '4';
            if ($shop_id) {
                $endpoint = '/wc-auth/v1/authorize';
                $params = [
                    'app_name' => $app_name,
                    'scope' => 'read_write',
                    'user_id' => $shop_id,
                    'return_url' => fn_url('wk_woocommerce.update?id='.$shop_id),
                    'callback_url' => fn_url('wk_woocommerce.webhookauth', 'C')
                ];
                $query_string = http_build_query($params);
                $auth_url = $store_url . $endpoint . '?' . $query_string;
                fn_create_payment_form($auth_url, array(), 'redirecting to WooCommerce....', true, 'get');
            }
        }

        return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.update'];

    }

    if ($mode == 'delete') {
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
            fn_wk_delete_woocommerce_merchant_account($_REQUEST['id']);
        }
        return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage'];
    }

    if ($mode == 'm_delete') {
        if (isset($_REQUEST['wk_merchant_ids']) && !empty($_REQUEST['wk_merchant_ids'])) {
            foreach ($_REQUEST['wk_merchant_ids'] as $wk_merchant_id) {
                fn_wk_delete_woocommerce_merchant_account($wk_merchant_id);
            }
        }
        return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage'];
    }

    if ($mode == 'register_order_webhook') {
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
            $credentials = fn_get_wk_woocommerce_account_data($_REQUEST['id']);
            $data = [
                'name' => 'Order created',
                'topic' => 'order.created',
                'delivery_url' => fn_url("wk_woocommerce.order_webhook&id=".$_REQUEST['id'], 'C')
            ];

            $order_webhook = wk_woocommerce_api_call($credentials, 'POST', 'webhooks', array(), $data);
            if (!empty($order_webhook)) {
                $data = array(
                    'order_webhook_id'=> $order_webhook['id'],
                );
                db_query("UPDATE ?:wk_woocommerce_store SET ?u WHERE shop_id = ?i", $data, $_REQUEST['id']);
            }
            return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.update&id='.$_REQUEST['id']];
        }   
    }

    if ($mode == 'register_product_webhook') {
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
            $credentials = fn_get_wk_woocommerce_account_data($_REQUEST['id']);

            //product create webhook//
            $data1 = [
                'name' => 'Product created',
                'topic' => 'product.created',
                'delivery_url' => fn_url("wk_woocommerce.product_create_webhook&id=".$_REQUEST['id'], 'C')
            ];

            $product_create_webhook = wk_woocommerce_api_call($credentials, 'POST', 'webhooks', array(), $data1);

            //product update webhook//
            $data2 = [
                'name' => 'Product updated',
                'topic' => 'product.updated',
                'delivery_url' => fn_url("wk_woocommerce.product_update_webhook&id=".$_REQUEST['id'], 'C')
            ];

            $product_update_webhook = wk_woocommerce_api_call($credentials, 'POST', 'webhooks', array(), $data2);

            if (!empty($product_create_webhook) && !empty($product_update_webhook)) {
                $data = array(
                    'product_create_webhook_id'=> $product_create_webhook['id'],
                    'product_update_webhook_id'=> $product_update_webhook['id']
                );
                db_query("UPDATE ?:wk_woocommerce_store SET ?u WHERE shop_id = ?i", $data, $_REQUEST['id']);
            }
            return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.update&id='.$_REQUEST['id']];
        }
    }

    if ($mode == 'update') {
        if(isset($_REQUEST['merchant_data']) && !empty($_REQUEST['merchant_data']) && isset($_REQUEST['shop_id']) && !empty($_REQUEST['shop_id'])) {
            $company_id =  Registry::get('runtime.company_id') ? Registry::get('runtime.company_id') : $_REQUEST['merchant_data']['company_id'];
            
            $data = array(
                'default_currency_code'       => $_REQUEST['merchant_data']['default_currency_code'],
                'default_cscart_category_id'  => $_REQUEST['merchant_data']['default_cscart_category_id'],
                'default_payment'             => $_REQUEST['merchant_data']['default_payment'],
                'default_shipping'            => $_REQUEST['merchant_data']['default_shipping'],
                'default_order_status'        => $_REQUEST['merchant_data']['default_order_status'],
                'company_id' => $company_id,
                'timestamp'  => TIME
            );
            $shop_id = db_query("UPDATE ?:wk_woocommerce_store SET ?u WHERE shop_id = ?i", $data, $_REQUEST['shop_id']);
            $suffix = '?id='.$_REQUEST['shop_id'];
        }
        return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.update'.$suffix];
    }

    if ($mode == 'category_map') {
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {

            if (isset($_REQUEST['category_map'])) {
                foreach ($_REQUEST['category_map'] as $id=>$cs_category_id) {
                    if ($cs_category_id) {
                        // if (db_get_field("SELECT id FROM ?:wk_woocommerce_category_map WHERE id != ?i AND category_id = ?i AND account_id = ?i", $id, $cs_category_id, $_REQUEST['account_id'])) {
                        //     $cs_category_id = 0;
                        // }
                        db_query("UPDATE ?:wk_woocommerce_category_map SET category_id = ?i WHERE id = ?i AND account_id =?i", $cs_category_id, $id, $_REQUEST['account_id']);
                    }
                }
                fn_set_notification("N", __("success"), __("category_mapped_successfully"));
            }
        }

        $suffix = 'category_map&account_id='.$_REQUEST['account_id'];
    }

    if ($mode == 'shipping_map') {
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            
            if (isset($_REQUEST['shipping_map'])) {
                foreach ($_REQUEST['shipping_map'] as $id=>$cs_shipping_id) {
                    if ($cs_shipping_id) {
                        if (db_get_field("SELECT id FROM ?:wk_woocommerce_shipping_map WHERE id != ?i AND shipping_id = ?i AND account_id = ?i", $id, $cs_shipping_id, $_REQUEST['account_id'])) {
                            $cs_shipping_id = 0;
                        }
                    } else {
                        $cs_shipping_id = 0;
                    }
                    db_query("UPDATE ?:wk_woocommerce_shipping_map SET shipping_id = ?i WHERE id = ?i AND account_id =?i", $cs_shipping_id, $id, $_REQUEST['account_id']);                    
                }
                fn_set_notification("N", __("success"), __("shipping_mapped_successfully"));
            }
        }

        $suffix = 'shipping_map&account_id='.$_REQUEST['account_id'];
    }

    if ($mode == 'payment_map') {
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {

            if (isset($_REQUEST['payment_map'])) {
                foreach ($_REQUEST['payment_map'] as $id=>$cs_payment_id) {
                    if ($cs_payment_id) {
                        if (db_get_field("SELECT id FROM ?:wk_woocommerce_payment_map WHERE id != ?i AND payment_id = ?i AND account_id = ?i", $id, $cs_payment_id, $_REQUEST['account_id'])) {
                            $cs_payment_id = 0;
                        }
                    } else {
                        $cs_payment_id = 0;
                    }
                    db_query("UPDATE ?:wk_woocommerce_payment_map SET payment_id = ?i WHERE id = ?i AND account_id =?i", $cs_payment_id, $id, $_REQUEST['account_id']);
                }
                fn_set_notification("N", __("success"), __("payment_mapped_successfully"));
            }
        }

        $suffix = 'shipping_map&account_id='.$_REQUEST['account_id'];
    }
    
    if ($mode == 'attribute_map') {
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            if (isset($_REQUEST['attribute_map'])) {
                foreach ($_REQUEST['attribute_map'] as $id=>$cs_feature_id) {
                    if ($cs_feature_id) {
                        if (db_get_field("SELECT id FROM ?:wk_woocommerce_attribute_map WHERE id != ?i AND feature_id = ?i AND account_id = ?i", $id, $cs_feature_id, $_REQUEST['account_id'])) {
                            $cs_feature_id = 0;
                        }   
                    } else {
                        $cs_feature_id = 0;
                    }
                    db_query("UPDATE ?:wk_woocommerce_attribute_map SET feature_id = ?i WHERE id = ?i AND account_id =?i", $cs_feature_id, $id, $_REQUEST['account_id']);
                }
                fn_set_notification("N", __("success"), __("attributes_mapped_successfully"));
            }
        }

        $suffix = 'shipping_map&account_id='.$_REQUEST['account_id'];
    }

    if ($mode == 'get_categories') {
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            $category_data = Fn_Download_woocommerce_categories($_REQUEST['account_id']);
        } else {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }
        Tygh::$app['ajax']->assign('force_redirection', fn_url('wk_woocommerce.category_map&account_id='.$_REQUEST['account_id']));
        Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        exit;
    }

    if ($mode == 'get_shippings') {
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            $shipping_data = Fn_Download_woocommerce_shippings($_REQUEST['account_id']);
        } else {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }
        Tygh::$app['ajax']->assign('force_redirection', fn_url('wk_woocommerce.shipping_map&account_id='.$_REQUEST['account_id']));
        Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        exit;
    }

    if ($mode == 'get_payments') {
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            $payment_data = Fn_Download_woocommerce_payments($_REQUEST['account_id']);
        } else {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }
        Tygh::$app['ajax']->assign('force_redirection', fn_url('wk_woocommerce.payment_map&account_id='.$_REQUEST['account_id']));
        Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        exit;
    }
    
    if ($mode == 'get_attributes') {
        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            $attribute_data = Fn_Download_woocommerce_attributes($_REQUEST['account_id']);
        } else {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }
        Tygh::$app['ajax']->assign('force_redirection', fn_url('wk_woocommerce.attribute_map&account_id='.$_REQUEST['account_id']));
        Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        exit;
    }

    if ($mode == 'delete_category_map') {
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id']) && isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            db_query("DELETE FROM ?:wk_woocommerce_category_map WHERE account_id = ?i AND id = ?i", $_REQUEST['account_id'], $_REQUEST['id']);
        }
        $suffix = 'category_map&account_id='.$_REQUEST['account_id'];
    }

    if ($mode == 'delete_shipping_map') {
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id']) && isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            db_query("DELETE FROM ?:wk_woocommerce_shipping_map WHERE account_id = ?i AND id = ?i", $_REQUEST['account_id'], $_REQUEST['id']);
        }
        $suffix = 'shipping_map&account_id='.$_REQUEST['account_id'];
    }

    if ($mode == 'delete_payment_map') {
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id']) && isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            db_query("DELETE FROM ?:wk_woocommerce_payment_map WHERE account_id = ?i AND id = ?i", $_REQUEST['account_id'], $_REQUEST['id']);
        }
        $suffix = 'payment_map&account_id='.$_REQUEST['account_id'];
    }

    if ($mode == 'delete_attribute_map') {
        if (isset($_REQUEST['id']) && !empty($_REQUEST['id']) && isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            db_query("DELETE FROM ?:wk_woocommerce_attribute_map WHERE account_id = ?i AND id = ?i", $_REQUEST['account_id'], $_REQUEST['id']);
        }
        $suffix = 'attribute_map&account_id='.$_REQUEST['account_id'];
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.'.$suffix);
}

if ($mode == 'manage') {
    list($merchant_accounts,$search) = fn_get_wk_woocommerce_account_list($_REQUEST);
    Registry::get('view')->assign('merchant_accounts', $merchant_accounts);
    Registry::get('view')->assign('search', $search);    
}

if ($mode == 'add' || $mode == 'update') {
    Registry::set(
        'navigation.tabs', array (
            'wk_general' => array (
                'title' => __('general'),
                'js' => true
            ),
            'order_settings' => array(
                'title' => __('order_settings'),
                'js' => true,
            ),
            'product_settings' => array(
                'title' => __('product_settings'),
                'js' => true,
            ),
            'webhook_settings' => array(
                'title' => __('woo_webhook_settings'),
                'js' => true,
            ),
        )
    );
    
    if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
        $merchant_data = fn_get_wk_woocommerce_account_data($_REQUEST['id']);
        if($mode=='update' && empty($merchant_data)){
            return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage'];
        }
        $account_id = $_REQUEST['id'];
        if (!empty($merchant_data) && isset($merchant_data['consumer_key']) && !empty($merchant_data['consumer_key']) && isset($merchant_data['consumer_secret']) && !empty($merchant_data['consumer_secret'])) {
            
            fn_wk_woocoomerce_get_webhook_data($merchant_data);

            Registry::get('view')->assign('merchant_data', $merchant_data);
            Registry::get('view')->assign('account_id', $account_id);

            $payment_arr = fn_get_payments();
            if(fn_allowed_for('ULTIMATE')){ 
                $company_id = Registry::ifGet('runtime.company_id', null); 
            }else {
                $company_id = null;
            }
            $shipping_arr = fn_get_available_shippings($company_id);
            Tygh::$app['view']->assign('payment_arr', $payment_arr);
            Tygh::$app['view']->assign('shipping_arr', $shipping_arr);

           
        }
            Registry::get('view')->assign('account_id', $account_id);
        if (isset($_REQUEST['success'])) {
            if($_REQUEST['success']) 
                fn_set_notification('N',__('success'),__("wk_woocomerce_authorized_successfully"));                
            else
                fn_set_notification('E', 'error', __("wk_unable_to_authorize_user"));
        }
    }

}

if ($mode == 'delete_webhook') {
    if (isset($_REQUEST['id']) && !empty($_REQUEST['id']) && isset($_REQUEST['webhook_id']) && !empty($_REQUEST['webhook_id']) && isset($_REQUEST['type']) && !empty($_REQUEST['type'])) {
        $credentials = fn_get_wk_woocommerce_account_data($_REQUEST['id']);
        $params['force'] = true;
        $response = wk_woocommerce_api_call($credentials, 'DELETE', 'webhooks/'.$_REQUEST['webhook_id'], $params);
        if ($response['id']) {
            $data = array();
            if ($_REQUEST['type'] == 'order_create') {
                $data['order_webhook_id'] = 0;
            }
            if ($_REQUEST['type'] == 'product_create') {
                $data['product_create_webhook_id'] = 0;
            }
            if ($_REQUEST['type'] == 'product_update') {
                $data['product_update_webhook_id'] = 0;
            }
            db_query("UPDATE ?:wk_woocommerce_store SET ?u WHERE shop_id = ?i", $data, $_REQUEST['id']);

            fn_set_notification('N', __('success'), __("wk_woocomerce_webhook_deleted_successfully"));
        }
        else
            fn_set_notification('E', 'error', __("wk_unable_to_delete_webhook"));

        return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.update&id='.$_REQUEST['id']]; 
    } else {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
}

if ($mode == 'category_map') {
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        $merchant_data = fn_get_wk_woocommerce_account_data($_REQUEST['account_id']);
        if(empty($merchant_data)){
            return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage'];
        }
        Tygh::$app['view']->assign('account_id', $_REQUEST['account_id']);        
        list($wk_woocommerce_categories, $search) = woocommerce_get_mapped_category($_REQUEST['account_id'], $_REQUEST);
        Tygh::$app['view']->assign('wk_woocommerce_categories', $wk_woocommerce_categories);
        Tygh::$app['view']->assign('search', $search);
    } else {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
}

if ($mode == 'shipping_map') {
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        $merchant_data = fn_get_wk_woocommerce_account_data($_REQUEST['account_id']);
        if(empty($merchant_data)){
            return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage'];
        }
        Tygh::$app['view']->assign('account_id', $_REQUEST['account_id']);        
        list($wk_woocommerce_shippings, $search) = woocommerce_get_mapped_shipping($_REQUEST['account_id'], $_REQUEST);
        if(fn_allowed_for('ULTIMATE')){ 
            $company_id = Registry::ifGet('runtime.company_id', null); 
        }else {
            $company_id = null;
        }
        $shippings = fn_get_available_shippings($company_id);
        Tygh::$app['view']->assign('wk_woocommerce_shippings', $wk_woocommerce_shippings);
        Tygh::$app['view']->assign('shippings', $shippings);
        Tygh::$app['view']->assign('search', $search);
    } else {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
}

if ($mode == 'payment_map') {
    
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        $merchant_data = fn_get_wk_woocommerce_account_data($_REQUEST['account_id']);
        if(empty($merchant_data)){
            return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage'];
        }
        Tygh::$app['view']->assign('account_id', $_REQUEST['account_id']);        
        list($wk_woocommerce_payments, $search) = woocommerce_get_mapped_payment_methods($_REQUEST['account_id'], $_REQUEST);
        $payments = fn_get_payments(false);
        Tygh::$app['view']->assign('wk_woocommerce_payments', $wk_woocommerce_payments);
        Tygh::$app['view']->assign('payments', $payments);
        Tygh::$app['view']->assign('search', $search);
    } else {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
}

if ($mode == 'attribute_map') {
    if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
        $merchant_data = fn_get_wk_woocommerce_account_data($_REQUEST['id']);
        if(empty($merchant_data)){
            return [CONTROLLER_STATUS_REDIRECT, 'wk_woocommerce.manage'];
        }
        Tygh::$app['view']->assign('account_id', $_REQUEST['account_id']);        
        list($wk_woocommerce_attributes, $search) = woocommerce_get_mapped_product_attributes($_REQUEST['account_id'], $_REQUEST);
        // list($global_options, $option_params) = fn_get_product_global_options();
        $params = array(
            'exclude_group' => true,
            'get_descriptions' => true,
            'variants' => false,
            'plain' => true,
        );
        list($features) = fn_get_product_features($params, 0);
        Tygh::$app['view']->assign('wk_woocommerce_attributes', $wk_woocommerce_attributes);
        Tygh::$app['view']->assign('features', $features);
        Tygh::$app['view']->assign('search', $search);
    } else {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
}

if ($mode == 'picker') {

    $category_count = db_get_field("SELECT COUNT(*) FROM ?:categories");
    $category_id = empty($_REQUEST['category_id']) ? 0 : $_REQUEST['category_id'];
    $except_id = 0;
    if (!empty($_REQUEST['except_id'])) {
        $except_id = $_REQUEST['except_id'];
        Tygh::$app['view']->assign('except_id', $_REQUEST['except_id']);
    }

    $params = array(
        'simple' => false,
        'add_root' => !empty($_REQUEST['root']) ? $_REQUEST['root'] : '',
        'b_id' => !empty($_REQUEST['b_id']) ? $_REQUEST['b_id'] : '',
        'except_id' => $except_id,
        'company_ids' => !empty($_REQUEST['company_ids']) ? $_REQUEST['company_ids'] : '',
        'save_view_results' => !empty($_REQUEST['save_view_results']) ? $_REQUEST['save_view_results'] : ''
    );

    if ($category_count < CATEGORY_THRESHOLD) {
        Tygh::$app['view']->assign('show_all', true);
    } else {
        $params['category_id'] = $category_id;
        $params['current_category_id'] = $category_id;
        $params['visible'] = true;
    }

    list($categories_tree) = fn_get_categories($params, DESCR_SL);
    Tygh::$app['view']->assign('categories_tree', $categories_tree);

    if ($category_count < CATEGORY_SHOW_ALL) {
        Tygh::$app['view']->assign('expand_all', true);
    }
    if (defined('AJAX_REQUEST')) {
        if (!empty($_REQUEST['random'])) {
            Tygh::$app['view']->assign('random', $_REQUEST['random']);
        }
        Tygh::$app['view']->assign('category_id', $category_id);
    }

    if (isset($_REQUEST['disable_cancel'])) {
        if ($_REQUEST['disable_cancel']) {
            Tygh::$app['view']->assign('disable_cancel', $_REQUEST['disable_cancel']);
        }
    }

    Tygh::$app['view']->display('addons/wk_woocommerce/pickers/categories/picker_contents.tpl');
    exit;
}
