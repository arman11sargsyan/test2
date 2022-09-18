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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Addons\StripeConnect\OAuthHelper;
use Tygh\Addons\StripeConnect\Payments\StripeConnect;
use Tygh\Addons\StripeConnect\ServiceProvider;
use Tygh\Enum\Addons\StripeConnect\AccountTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;
use Tygh\Enum\YesNo;
use Tygh\Enum\UserTypes;

/** @var string $mode */

if ($mode == 'index') {
	$checkaddonstatus = Registry::get('addons.stripe_connect');
	Tygh::$app['view']->assign('connect_url',2);
   if(trim($checkaddonstatus['status']) =='A')
	{
    $company_id = Registry::get('runtime.company_id');
    $company_data = fn_get_company_data($company_id);
    //$company_data = Tygh::$app['view']->getTemplateVars('company_data');
    $processor_params = StripeConnect::getProcessorParameters();
	
    if (!empty($company_data['company_id'])) {

        if (empty($company_data['stripe_connect_account_id'])) {

            $oauth_helper = ServiceProvider::getOAuthHelper();
            $account_helper = ServiceProvider::getAccountHelper();

            if (YesNo::toBool($processor_params['allow_express_accounts'])) {
                $account_id = $account_helper->getStorageAccountId($company_data['company_id']);
                if ($account_id) {
                    Tygh::$app['view']->assign(
                        'stripe_express_continue_registration_url',
                        fn_url('companies.continue_express_registration')
                    );
                } else {
                    $authorize_express_result = $oauth_helper->getAuthorizeUrl(
                        AccountTypes::EXPRESS,
                        $account_helper->prefillAccountData($company_data['company_id'])
                    );
                    if ($authorize_express_result->isSuccess()) {
                        Tygh::$app['view']->assign(
                            'stripe_express_connect_url',
                            $authorize_express_result->getData()
                        );
                    }
                }
            }

            $authorize_standard_result = $oauth_helper->getAuthorizeUrl(AccountTypes::STANDARD);

            if ($authorize_standard_result->isSuccess()) {
                Tygh::$app['view']->assign(
                    'stripe_standard_connect_url',
                    $authorize_standard_result->getData()
                );
            }
        } else {

            Tygh::$app['view']->assign(
                'stripe_disconnect_url',
                fn_url('companies.stripe_connect_disconnect')
            );
        }
		Tygh::$app['view']->assign('connect_url',1);
    }
 }

/********* VENDOR DASHBOARD REMOVE WEB PAGE BLOCK IN ***************/
if ($auth['user_type'] === UserTypes::VENDOR) {
	$runtime_company_id = Registry::get('runtime.company_id');
		// Products on moderation & Disapproved Box hide	
		$auth = Tygh::$app['session']['auth'];
        if ($auth['user_type'] === UserTypes::ADMIN) {
            $company_ids = $storefront ? $storefront->getCompanyIds() : [];
        } else {
            $company_ids = [$auth['company_id']];
        }
		$view = Tygh::$app['view'];
		$general_stats = fn_dashboard_get_general_stats($runtime_company_id, $company_ids);
		unset($general_stats['pages']);
		Tygh::$app['view']->assign([
            'general_stats'     => $general_stats,
        ]);
}
/********* VENDOR DASHBOARD REMOVE WEB PAGE BLOCK IN ***************/
}