<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if(AREA == 'A' && ACCOUNT_TYPE == 'vendor' && Tygh::$app['session']['auth']['user_id']){
    $aggreed_status = db_get_field("SELECT ec_agreement FROM ?:users WHERE user_id = ?i",Tygh::$app['session']['auth']['user_id']);
    Tygh::$app['view']->assign('aggreed_status', $aggreed_status);
}