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

if (!defined('BOOTSTRAP')) { die('Access denied'); }


$user_metadata_fields = db_get_array("SELECT * FROM ?:profile_fields WHERE field_name LIKE ?l", 'user_metadata_%');
foreach ($user_metadata_fields as $key => $value) {
	$name = strtoupper( str_replace( 'user_metadata_', "", $value['field_name'] ));
	if ( !defined( $name ) ) {
	   define( $name, $value['field_id'] );
	}
}

$app_metadata_fields = db_get_array("SELECT * FROM ?:profile_fields WHERE field_name LIKE ?l", 'app_metadata_%');
foreach ($app_metadata_fields as $key => $value) {
	$name = strtoupper( str_replace( 'app_metadata_', "", $value['field_name'] ));
	if ( !defined( $name ) ) {
	   define( $name, $value['field_id'] );
	}
}