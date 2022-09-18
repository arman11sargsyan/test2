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

/*
    if ($mode == 'auth0_callback') {

            if (fn_check_ec_auth0_settings()) {
                

				   $hasAuthenticated = isset($_GET['state']) && isset($_GET['code']);
				   $hasAuthenticationFailure = isset($_GET['error']);

				  // The end user will be returned with ?state and ?code values in their request, when successful.
				  if ($hasAuthenticated) {
					try {

						$sdk = fn_get_auth0_config();
	  
					  //login redirect here?
					  
					  
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
				
            } else {
                #fn_set_notification('W', __('warning'), __('sso_saml_settings_is_not_valid'), 'S');
            }

            return array(CONTROLLER_STATUS_REDIRECT);
        
    }


*/