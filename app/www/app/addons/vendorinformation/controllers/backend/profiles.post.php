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

use Tygh\Api;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Tools\Url;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

$auth = & Tygh::$app['session']['auth'];

if ($mode == 'update' || $mode == 'add') {
	/**************** Vendor User Profile Field value assign *************/
	//Tygh::$app['view']->assign('field_mobile','9'); // language variable vendorinformation_text_field_9
	//Tygh::$app['view']->assign('field_emailid','39');  // language variable vendorinformation_text_field_39
	//Tygh::$app['view']->assign('field_profiledisplayname','54');  // language variable vendorinformation_text_field_54
	//Tygh::$app['view']->assign('field_profilebio','55');  // language variable vendorinformation_text_field_55

	//Tygh::$app['view']->assign('field_profileshowsubheading','54');  // Show your Profle field sub heading before that field value
	
	/**************** Vendor User Profile Field value assign *************/

	$tabs = Registry::get('navigation.tabs');	
	$tabs['general']['title']="My Account";
	Registry::set('navigation.tabs', $tabs);
}