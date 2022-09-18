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

use Tygh\Addons\VendorRating\ServiceProvider;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'update' || $mode === 'add') {
    $tabs = Registry::ifGet('navigation.tabs', []);

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $plan */
    $plan = $view->getTemplateVars('plan');
    $id = empty($plan['plan_id']) ? 0 : $plan['plan_id'];

    $tabs['stripeconnecttax_tax_' . $id] = [
        'title' => __('ec_stripeconnecttax_tax_tab'),
        'js'    => true,
    ];

    Registry::set('navigation.tabs', $tabs);
}

return [CONTROLLER_STATUS_OK];
