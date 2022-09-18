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
use Auth0\SDK\Utility\HttpResponse;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

require_once __DIR__ . '/hooks.functions.php';

//control whether to turn on or off login
function fn_auth0_enabled_loggon()
{
    if( Registry::get('addons.ec_auth0.auth0_login') == 'Y')
    {
        return true;
    }
    return false;
}


function fn_auth0_client_connection( $auth_management=false )
{
    $configuration = new SdkConfiguration([
        'domain' => Registry::get('addons.ec_auth0.auth0_domain'),
        'clientId'=>Registry::get('addons.ec_auth0.auth0_client_id'),
        'clientSecret'=> Registry::get('addons.ec_auth0.auth0_client_secret'),
        'redirectUri'=> ROUTE_URL_CALLBACK,
        'cookieSecret'=> Registry::get('addons.ec_auth0.auth0_cookie_secret')
    ]);
    try
    {
        $auth0 = new Auth0($configuration);
    }
    catch(Auth0\SDK\Exception\ConfigurationException $e)
    {
        fn_print_r($e->getMessage());
    }
    

    if( $auth_management == true )
    {
        // Begin a client credentials exchange:
        $response = $auth0->authentication()->clientCredentials([
            'audience' => Registry::get('addons.ec_auth0.auth0_audience')
        ]);
    }
    return $auth0;
}

function fn_auth0_update_user( $user_id, $body )
{
    $auth0 = fn_auth0_client_connection( true );

    if( $auth0 && $user_id && $body )
    {
        // Request users from the /users Management API endpoint
        $response = $auth0->management()->users()->update( $user_id, $body );

        // Was the API request successful?
        if (HttpResponse::wasSuccessful($response)) {
            $result = HttpResponse::decodeContent($response);
            return true;
        }
        else
        {
            error_log(HttpResponse::getContent($response));
        }
    }    

    return false;
}

function fn_auth0_create_user( $body )
{
    $auth0 = fn_auth0_client_connection( true );

    if( $auth0 && $body )
    {
        $connection = 'Username-Password-Authentication';

        // Request users from the /users Management API endpoint
        $response = $auth0->management()->users()->create( $connection, $body );

        // Was the API request successful?
        if (HttpResponse::wasSuccessful($response)) {
            $result = HttpResponse::decodeContent($response);
            return true;
        }
        else
        {
            error_log(HttpResponse::getContent($response));
            return false;
        }
    }    

    return false;
}


//format data for use with cscart
function fn_auth0_generate_user_data( $user, $user_data = array() )
{
    $user_data['auth0_user_id'] = ($user['user_id'] ? $user['user_id'] : '');
    $user_data['email'] = $user['email'];

    if( isset($user['blocked']) && $user['blocked'] )
    {
        $user_data['status'] = 'D';
    }

    if( isset($user['given_name']) && isset($user['family_name']) )
    {
        $user_data['lastname'] = $user['family_name'];
        $user_data['firstname'] = $user['given_name'];
    }
    else
    {
        $names = explode(" ", $user['name']);
        $user_data['lastname'] = (isset($names[1]) ? $names[1] : '');
        $user_data['firstname'] = (isset($names[0]) ? $names[0] : '');
    }

    $profile_fields = fn_format_auth0_profile_fields( $user );
    if(!empty($profile_fields))
    {
        $user_data['fields'] = $profile_fields;
    }


    if( isset($user_data['password']) && $user_data['password'] == ''  )
    {
        $password = fn_generate_password();
        $user_data['password1'] =  $password;
        $user_data['password2'] =  $password;
    }

    if( isset($user['user_metadata']['phone_number']) && trim($user['user_metadata']['phone_number']) != '' )
    {
        $user_data['phone'] = $user['user_metadata']['phone_number'];
    }

    if( $user_data['fields'][ORG_LEVEL] == 'PRIMARY' )
    {
        if( isset($user_data['fields'][ORG_NAME]) && trim($user_data['fields'][ORG_NAME]) != ''
            && isset($user_data['fields'][ORG_UUID]) && trim($user_data['fields'][ORG_UUID]) != '' )
        {
            $company_id = db_get_field("SELECT company_id FROM ?:companies WHERE vendor_uuid = ?s LIMIT 1",$user_data['fields'][ORG_UUID]);

            if($company_id)
            {
                $user_data['company_id'] = $company_id;
            }
        }
        //vendor
        $user_data['user_type'] = 'V';
    }
    else
    {
        $user_data['user_type'] = 'C';
    }

    return $user_data;
}

//format data for use with auth0
function fn_auth0_generate_user_body( $user_data, $create = false, $include_password = false )
{
    $update_body = array();
    $app_metadata = array();
    $user_metadata = array();

    foreach ($user_data as $key => $value) {

        if( $key == 'email' )
        {
            $update_body['email'] = $value;
        }

        if( $key == 'password1' && $include_password && $create)
        {
            $update_body['password'] = $value;
        }

        if( $key == 'firstname' || $key == 'lastname' )
        {
            if($key == 'firstname')
            {
                $update_body['given_name'] = $value;
            }
            if($key == 'lastname')
            {
                $update_body['family_name'] = $value;

            }            
        }

        if( $key == 'phone')
        {
            $user_metadata['phone_number'] = $value;
        }


        if( $key == 'fields' )
        {
            foreach ($value as $field_id => $field_value) {
                switch ($field_id) {
                    case ORG_LEVEL:
                        $user_metadata['org_level'] = $field_value;
                        break;
                    case ORG_NAME:
                        $user_metadata['org_name'] = $field_value;
                        break;  
                    case ORG_UUID:
                        $user_metadata['org_uuid'] = $field_value;
                        break;          
                    case USER_BIO:
                        $user_metadata['user_bio'] = $field_value;
                        break;
                    case CREATED_DATE_TIME:
                        $app_metadata['created_date_time'] = $field_value;
                        break;
                    case ORIGIN:
                        $app_metadata['origin'] = $field_value;
                        break;                                                                                                  
                }
            }
        }
    }


    if( isset($update_body['given_name']) && trim($update_body['given_name']) != '' 
        && isset($update_body['family_name']) && trim($update_body['family_name']) != '' 
    )
    {
        $update_body['name'] = $update_body['given_name']. ' '.$update_body['family_name'];     
    }


    if( !empty($user_metadata) )
    {
        $update_body['user_metadata'] = $user_metadata;
    }
    if( !empty($app_metadata) )
    {
        $update_body['app_metadata'] = $app_metadata;
    }

    return $update_body;
}

function fn_format_auth0_profile_fields( $user )
{
    $profile_fields = array();

    if( isset($user['user_metadata']['org_name']) && trim($user['user_metadata']['org_name']) != '' )
    {
        $profile_fields[ORG_NAME] = $user['user_metadata']['org_name'];
    }
    if( isset($user['user_metadata']['org_level']) && trim($user['user_metadata']['org_level']) != '' )
    {
        $profile_fields[ORG_LEVEL] = $user['user_metadata']['org_level'];
    }
    if( isset($user['user_metadata']['org_uuid']) && trim($user['user_metadata']['org_uuid']) != '' )
    {
        $profile_fields[ORG_UUID] = $user['user_metadata']['org_uuid'];
    }
    if( isset($user['user_metadata']['org_name']) && trim($user['user_metadata']['org_name']) != '' )
    {
        $profile_fields[ORG_NAME] = $user['user_metadata']['org_name'];
    }                                        
    if( isset($user['user_metadata']['user_bio']) && trim($user['user_metadata']['user_bio']) != '' )
    {
        $profile_fields[USER_BIO] = $user['user_metadata']['user_bio'];
    }     
    if( isset($user['app_metadata']['origin']) && trim($user['app_metadata']['origin']) != '' )
    {
        $profile_fields[ORIGIN] = $user['app_metadata']['origin'];
    }     
    if( isset($user['app_metadata']['created_date_time']) && trim($user['app_metadata']['created_date_time']) != '' )
    {
        $profile_fields[CREATED_DATE_TIME] = $user['app_metadata']['created_date_time'];
    }

    return $profile_fields;
}


function fn_auth0_sync_user( $auth, $user_id )
{
    $auth0 = fn_auth0_client_connection(true);

    if( $auth0 && $user_id )
    {
        $auth0_user_id = db_get_field("SELECT auth0_user_id FROM ?;users WHERE user_id = ?i ", $user_id);

        $response = $auth0->management()->users()->get( $auth0_user_id );

        // Was the API request successful?
        if (HttpResponse::wasSuccessful($response)) {

            $user = HttpResponse::decodeContent($response);

                $cscart_user_id = db_get_field( "SELECT user_id FROM ?:users WHERE auth0_user_id = ?s LIMIT 1",$auth0_user_id );

                if( !$cscart_user_id )
                {
                    $user_data = array(
                        'status' => 'A',
                        'email' => $user['email'],
                        'auth0_user_id' => $user['user_id'],
                        'lang_code' => CART_LANGUAGE,
                        'company_id' => 0
                    );
                    $user_data = fn_auth0_generate_user_data( $user, $user_data );                     
                }
                else
                {
                    $user_data = fn_get_user_info($cscart_user_id, true);
                    $user_data = fn_auth0_generate_user_data( $user, $user_data );
                }                

                if( $update_details )
                {
                    $user_data['auth0_update'] = true;
                    $user_data['skip_auth_creation'] = true;
                    list($cscart_user_id, $data) = fn_update_user($cscart_user_id, $user_data, $auth, false, false);
               
                }

            
        }
    }
    else
    {
        return false;
    }    
}

function fn_auth0_sync_users( $auth, $params=array(), $update_details=true  )
{
    $auth0 = fn_auth0_client_connection(true);

    if( $auth0 )
    {
        if( isset($params['email']) && $params['email'] != '' )
        {
            $response = $auth0->management()->usersByEmail()->get( $params['email'] );
        }
        else if( isset($params['auth0_user_id']) && $params['auth0_user_id'] != '' )
        {
            $response = $auth0->management()->users()->get( $params['auth0_user_id'] );
        }        
        else if( !empty($params) )
        {
            $response = $auth0->management()->users()->getAll();
        }
        else
        {
            // Request users from the /users Management API endpoint
            $response = $auth0->management()->users()->getAll();
        }

        // Was the API request successful?
        if (HttpResponse::wasSuccessful($response)) {

            $users = HttpResponse::decodeContent($response);

            foreach ($users as $key => $user) {

                $cscart_user_id = db_get_field( "SELECT user_id FROM ?:users WHERE auth0_user_id = ?s LIMIT 1",$user['user_id'] );

                if( !$cscart_user_id )
                {
                    $user_data = array(
                        'status' => 'A',
                        'email' => $user['email'],
                        'auth0_user_id' => $user['user_id'],
                        'lang_code' => CART_LANGUAGE,
                        'company_id' => 0
                    );
                    $user_data = fn_auth0_generate_user_data( $user, $user_data );                     
                }
                else
                {
                    $user_data = fn_get_user_info($cscart_user_id, true);
                    $user_data = fn_auth0_generate_user_data( $user, $user_data );
                }                

                if( $update_details )
                {
                    $user_data['auth0_update'] = true;
                    $user_data['skip_auth_creation'] = true;
                    list($cscart_user_id, $data) = fn_update_user($cscart_user_id, $user_data, $auth, false, false);

                                      
                }

            }
        }
    }
    else
    {
        return false;
    }
}


function format_E164($num) 
{
    $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    try {
        $num = $phoneUtil->parse($num);
    } catch (\libphonenumber\NumberParseException $e) {
        var_dump($e);
    }

    return $phoneUtil->format($num, \libphonenumber\PhoneNumberFormat::E164);
}



function fn_auth0_login(&$auth, $user_id)
{
    $auth = array (
        'user_id' => $user_id,
        'area' => 'C',
        'user_type' => 'C',
        'password_change_timestamp' => time(),
        'first_expire_check' => false,
        'this_login' => time(),
        'is_root' => db_get_field('SELECT is_root FROM ?:users WHERE user_id = ?i', $user_id)
    );  
}


function fn_get_auth_for_auth0()
{
    $root_admin_id = db_get_row("SELECT user_id FROM ?:users WHERE is_root = 'Y' AND company_id = 0 LIMIT 1");
    $_auth = array(
        'user_id' => $root_admin_id,
        'area' => 'A',
        'user_type' => 'A',
        'password_change_timestamp' => TIME,
        'this_login' => TIME,
        'is_root' => 'Y',
        'usergroup_ids' => array()
    );    
    return $_auth;
}

function fn_auth0_update_cscart_user($email, $user_id=null, $auth_user_data )
{
    $password = empty($user_id) ? fn_generate_password() : '';
    $user_data = array(
        'status' => 'A',
        'user_type' => 'A',
        'email' => $email,
        'password1' => $password,
        'password2' => $password,
        'lang_code' => CART_LANGUAGE,
        'company_id' => Registry::get('runtime.company_id')
    );

    $root_admin_id = db_get_row("SELECT user_id FROM ?:users WHERE is_root = 'Y'");
    $_auth = array(
        'user_id' => $root_admin_id,
        'area' => 'A',
        'user_type' => 'A',
        'password_change_timestamp' => TIME,
        'this_login' => TIME,
        'is_root' => 'Y',
        'usergroup_ids' => array()
    );
    /*
    $_attributes = array_keys($attributes);
    $fields_data = db_get_array('SELECT field_id, field_name, field_type, saml_field FROM ?:profile_fields WHERE saml_field IN (?n) AND saml_field != ?s', $_attributes, '');
    foreach ($fields_data as $field) {
        if (!isset($attributes[$field['saml_field']])) {
            continue;
        }
        $value = $attributes[$field['saml_field']][0];
        if ($field['field_type'] == 'C' && $value) {
            if (__('yes') == $value) {
                $value = 'Y';
            } elseif (__('no') == $value) {
                $value = 'N';
            }
        } elseif(($field['field_type'] == 'R' || $field['field_type'] == 'S') && $value) {
            $_value = db_get_field('SELECT object_id FROM ?:profile_field_descriptions WHERE description = ?s AND lang_code = ?s', $value, CART_LANGUAGE);
            if (empty($_value)) {
                $variant = array(
                    'field_id' => $field['field_id'],
                    'description' => $value,
                    'object_type' => 'V',
                    'lang_code' => CART_LANGUAGE
                );
                $variant_id = db_query("INSERT INTO ?:profile_field_values ?e" , $variant);
                $variant['object_id'] = $variant_id;
                db_query("INSERT INTO ?:profile_field_descriptions ?e" , $variant);
                $value = $variant_id;
            } else {
                $value = $_value;
            }
        } elseif($field['field_type'] == 'O' && $value) {
            $_value = db_get_field('SELECT code FROM ?:country_descriptions WHERE country = ?s AND lang_code = ?s', $value, CART_LANGUAGE);
            $value = $_value ? $_value : $value;
        } elseif($field['field_type'] == 'A' && $value) {
            $_value = db_get_field(
                'SELECT s.code FROM ?:states AS s ' .
                'LEFT JOIN ?:state_descriptions AS sd ON s.state_id = sd.state_id ' .
                'WHERE sd.state = ?s AND sd.lang_code = ?s', $value, CART_LANGUAGE
            );
            $value = $_value ? $_value : $value;
        }
        if ($value) {
            if ($field['field_name']) {
                $user_data[$field['field_name']] = $value;
            } else {
                $user_data['fields'][$field['field_id']] = $value;
            }
        }
    }
    */

    list($user_id, ) = fn_update_user($user_id, $user_data, $_auth, false, false);
    /*
    $member_of = Registry::get('addons.sd_sso_saml.member_saml_field');
    $user_is_root = db_get_field('SELECT is_root FROM ?:users WHERE user_id = ?i', $user_id);
    if (isset($attributes[$member_of]) && $user_is_root == 'N') {
        $usergroups = db_get_array('SELECT * FROM ?:usergroups WHERE saml_field = ?s', $attributes['memberOf'][0]);
        if ($usergroups) {
            foreach ($usergroups as $usergroup) {
                fn_change_usergroup_status('A', $user_id, $usergroup['usergroup_id']);
            }
        }
    }
*/
    if( $user_id )
    {
        $auth0_user_id = $auth_user_data->user['sub'];

        db_query("REPLACE INTO `?:ec_users_auth0` (`user_id`,`auth0_user_id`,`timestamp`) VALUES (?i,?s,?i)", $user_id, $auth0_user_id, time() );
    }



    return $user_id;
}

function arrayRecursiveDiff($aArray1, $aArray2) {
  $aReturn = array();

  foreach ($aArray1 as $mKey => $mValue) {
    if (array_key_exists($mKey, $aArray2)) {
      if (is_array($mValue)) {
        $aRecursiveDiff = arrayRecursiveDiff($mValue, $aArray2[$mKey]);
        if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
      } else {
        if ($mValue != $aArray2[$mKey]) {
          $aReturn[$mKey] = $mValue;
        }
      }
    } else {
      $aReturn[$mKey] = $mValue;
    }
  }
  return $aReturn;
} 


function fn_log_auth0( $title, $message=null )
{
    $log  = "[".date("c")."] : $title : ".print_r($message,1).PHP_EOL;

    if (!file_exists(Registry::get('config.dir.var').'auth0_logs')) {
        mkdir(Registry::get('config.dir.var').'auth0_logs', 0777, true);
    }

    file_put_contents(Registry::get('config.dir.var').'auth0_logs/log_'.date("d_m_Y").'.log', $log, FILE_APPEND);    
}