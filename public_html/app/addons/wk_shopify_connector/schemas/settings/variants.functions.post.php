<?php
/********************************************************************
# OTP Verification - OTP Verification 								*
# ------------------------------------------------------------------*
# author    Webkul                                                	*
# copyright Copyright (C) 2010 webkul.com. All Rights Reserved.   	*
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL     	*
# Websites: http://webkul.com                                     	*
*********************************************************************
*/

/**
 * Get payment methods.
 */
function fn_settings_variants_addons_wk_shopify_connector_order_status_to_stop_order()
{
    return db_get_hash_single_array("SELECT s.status_id,s.status, sd.description  FROM ?:statuses as s join ?:status_descriptions as sd on s.status_id = sd.status_id WHERE type = 'O' ORDER BY status_id", array('status', 'description'));
}
