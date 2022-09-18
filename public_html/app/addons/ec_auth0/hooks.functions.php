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

use Auth0\SDK\Auth0;
use Auth0\SDK\Configuration\SdkConfiguration;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_ec_auth0_update_user_pre( $user_id, $user_data, $auth, $ship_to_another, $notify_user, $can_update)
{
	if( $user_id )
	{
		Tygh::$app['session']['user_update'][$user_id] = array();
		$user_update_informaton = & Tygh::$app['session']['user_update'][$user_id];
		$user_update_informaton = fn_get_user_info($user_id, true);
	}
}

function fn_ec_auth0_update_profile( $action, $user_data, $current_user_data )
{
	if( $action == 'add' )
	{
		$update_body = fn_auth0_generate_user_body( $user_data, true, true );

		if( !empty( $update_body ) && (!isset($user_data['skip_auth_creation'])))
		{
			fn_auth0_create_user( $update_body );
		}
	}
	else{


			if( $user_data['user_id'] &&  !isset($user_data['auth0_update']) )
			{
				$update_body = array();


				Tygh::$app['session']['user_update'][ $user_data['user_id'] ] = isset(Tygh::$app['session']['user_update'][$user_data['user_id']]) ? Tygh::$app['session']['user_update'][ $user_data['user_id'] ] : array();

				$previous_user_data = Tygh::$app['session']['user_update'][$user_data['user_id']];


				$difference = arrayRecursiveDiff($user_data, $previous_user_data);

				$update_body = fn_auth0_generate_user_body( $difference );

				if( !empty( $update_body ) )
				{
					fn_auth0_update_user( $user_data['auth0_user_id'], $update_body );
				}
				Tygh::$app['session']['user_update'][$user_data['user_id']] = array();
			}
		
	}
}


function fn_ec_auth0_get_companies( $params, &$fields, $sortings, &$condition, $join, $auth, $lang_code, $group )
{
    // Define fields that should be retrieved
    $fields[] = '?:companies.vendor_uuid';

    if (isset($params['vendor_uuid']) && fn_string_not_empty($params['vendor_uuid'])) {
        $condition .= db_quote(' AND ?:companies.vendor_uuid = ?s', trim($params['vendor_uuid']));
    }	
}

