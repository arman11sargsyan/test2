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

if( $mode == 'test' )
{
    $configuration = new SdkConfiguration([
        'domain' => Registry::get('addons.ec_auth0.auth0_domain'),
        'clientId'=> Registry::get('addons.ec_auth0.auth0_client_id'),
        'clientSecret'=> Registry::get('addons.ec_auth0.auth0_client_secret'),
        'redirectUri'=> ROUTE_URL_CALLBACK,
        'cookieSecret'=> Registry::get('addons.ec_auth0.auth0_cookie_secret')
    ]);
    $auth0 = new Auth0($configuration);

    // 👆 We're continuing from the "getting started" guide linked in "Prerequisites" above.

    // Begin a client credentials exchange:
    $response = $auth0->authentication()->clientCredentials([
        'audience' => Registry::get('addons.ec_auth0.auth0_audience')
    ]);


    // Does the status code of the response indicate failure?
    if ($response->getStatusCode() !== 200) {
        die("Code exchange failed.");
    }
    // Decode the JSON response into a PHP array:
    $response = json_decode($response->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);




    $response = $auth0->management()->connections()->getAll();
    // Was the API request successful?
    if (HttpResponse::wasSuccessful($response)) {
        // It was, decode the JSON into a PHP array:
        fn_print_r(HttpResponse::decodeContent($response));
    }	


    // Request users from the /users Management API endpoint
    $response = $auth0->management()->users()->getAll();
    // Was the API request successful?
    if (HttpResponse::wasSuccessful($response)) {

        $users = HttpResponse::decodeContent($response);

        foreach ($users as $key => $user) {

            // code...
        }

        // It was, decode the JSON into a PHP array:
        fn_print_r(HttpResponse::decodeContent($response));
    }
    else
    {
         fn_print_r(HttpResponse::decodeContent($response));
    }
}


if( $mode == 'sync_users' )
{
    fn_auth0_sync_users( $auth );
}

if( $mode == 'sync_specific_users_by_email' )
{
    if(isset($_REQUEST['email']) && $_REQUEST['email'] != '' )
    {
        $params['email'] = $_REQUEST['email'];
        fn_auth0_sync_users( $auth, $params );
    }
}

if( $mode == 'sync_individual' )
{
    $params['auth0_user_id'] = $_REQUEST['auth0_user_id'];
    $user_id = $_REQUEST['user_id'];
    if($params['auth0_user_id'])
    {
        fn_auth0_sync_users( $auth, $params, true );    
    }

    return array(CONTROLLER_STATUS_OK, 'profiles' . (!empty($user_id) ? '.update' : '.add') . '?' . http_build_query($redirect_params));
    
}



