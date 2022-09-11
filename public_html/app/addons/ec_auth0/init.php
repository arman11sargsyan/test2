<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/
use Tygh\Registry;


defined('BOOTSTRAP') or die('Access denied');

require_once __DIR__ . '/lib/vendor/autoload.php';

// Define route constants:
define('ROUTE_URL_INDEX', rtrim( 'https://'. Registry::get('config.https_host'), '/'));
define('ROUTE_URL_LOGIN', ROUTE_URL_INDEX . '/index.php?dispatch=auth.login_form');
define('ROUTE_URL_CALLBACK', ROUTE_URL_INDEX . '/index.php?dispatch=auth.auth0_callback');
define('ROUTE_URL_LOGOUT', ROUTE_URL_INDEX . '/index.php?dispatch=auth.logout');

fn_register_hooks(
    //users hooks
    'update_user_pre',
    'update_profile',
    #'get_user_info_before',
    #'get_user_info',


    'get_companies'
);
