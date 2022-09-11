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

use Tygh\Enum\NotificationSeverity;
use Tygh\Notifications\EventIdProviders\OrderProvider;
use Tygh\Registry;
use Tygh\Shippings\Shippings;
use Tygh\Storage;
use Tygh\Tools\Url;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */


if ($mode === 'print_orderreceipt') {
    if (!empty($_REQUEST['order_id'])) {
        echo(fn_print_order_receipt($_REQUEST['order_id'], $_REQUEST));
    }

    return [CONTROLLER_STATUS_NO_CONTENT];
}