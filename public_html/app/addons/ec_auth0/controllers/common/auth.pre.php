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

 declare(strict_types=1);

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


if ($mode == 'auth0_callback') {

	if (fn_auth0_enabled_loggon()) {
		/**
		* Upon returning from the Auth0 Universal Login, we need to perform a code exchange using the `exchange()` method
		* to complete the authentication flow. This process configures the session for use by the application.
		*
		* If successful, the user will be redirected back to the index route.
		*/
		$hasAuthenticated = isset($_GET['state']) && isset($_GET['code']);
		$hasAuthenticationFailure = isset($_GET['error']);

		fn_log_auth0( 'auth_repsonse', $_REQUEST );

		// The end user will be returned with ?state and ?code values in their request, when successful.
		if ($hasAuthenticated) {
			try {
				$sdk = fn_auth0_client_connection(false);
				try {
					$sdk->exchange();				  
				} catch (\Throwable $th) {
					printf('Unable to complete authentication: %s', $th->getMessage());
					exit;
				}

				// Check if the user is logged in already
				$auth_user_data = $sdk->getCredentials();

				fn_log_auth0('login', $auth_user_data);

				if ($auth_user_data) {
					$auth_user_id = $auth_user_data->user['sub'];
					$email = $auth_user_data->user['email'];

					$auth = fn_get_auth_for_auth0();
					

					$user_id = db_get_field("SELECT user_id FROM ?:users WHERE auth0_user_id = ?s limit 1", $auth_user_id);
					if( $user_id )
					{
						fn_login_user($user_id, true);
						fn_auth0_sync_users( $auth, array( 'email' => $email ), true);
					}
					else
					{
						//sync users not on system.
						#$redirect_to = '../index.php';
					}


					return array(CONTROLLER_STATUS_OK, fn_url('index.index'));
					exit;
				}
			} catch (\Throwable $th) {
				printf('Unable to complete authentication: %s', $th->getMessage());
				exit;
			}
		}

		// When authentication was unsuccessful, the end user will be returned with an ?error in their request.
		if ($hasAuthenticationFailure) {
			printf('Authentication failure: %s', htmlspecialchars(strip_tags(filter_input(INPUT_GET, 'error'))));
			exit;
		}


		fn_user_logout($auth);
		header('Location: ../index.php');
		exit;
    }

    fn_user_logout($auth);
	header('Location: ../index.php');
	exit;
}

if ($mode == 'login_form') {

	if (fn_auth0_enabled_loggon()) {
		if( AREA == 'C' || AREA == 'A' && $_SERVER['SCRIPT_NAME'] == '/vendor.php' )
		{
			$sdk = fn_auth0_client_connection(false);

			header(sprintf('Location: %s', $sdk->login(ROUTE_URL_CALLBACK)));	
			exit;
		}
    }
}


if ($mode == 'logout') {
	
    if (fn_auth0_enabled_loggon()) {

    	$sdk = fn_auth0_client_connection(false);
    	header(sprintf('Location: %s', $sdk->logout()));

    	fn_user_logout($auth);	
    	exit;
    }
}