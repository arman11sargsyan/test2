<?php

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'submit') {
        if(!empty($_REQUEST['agreement']) && $_REQUEST['agreement'] == 'Y' && Tygh::$app['session']['auth']['user_id']){
            $response = db_query("UPDATE ?:users SET ec_agreement = ?s WHERE user_id = ?i", $_REQUEST['agreement'], Tygh::$app['session']['auth']['user_id']);
            if($response){
                fn_set_notification('N', __('notice'), __("ec_vendor_agree.agreed_text"), 'I');
            }
        }
        return [CONTROLLER_STATUS_REDIRECT, $_REQUEST['redirect_url']];
    }
}