<?php
/******************************************************************
# Woocommerce Connector                                       *
# ----------------------------------------------------------------*
# author    Webkul                                                *
# copyright Copyright (C) 2010 webkul.com. All Rights Reserved.   *
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL     *
# Websites: http://webkul.com                                     *
*******************************************************************
*/ 

use Tygh\Tools\SecurityHelper;

$schema['merchant_data'] = array(
        SecurityHelper::SCHEMA_SECTION_FIELD_RULES => array(
        'app_name' => SecurityHelper::ACTION_REMOVE_HTML,
        'store_url' => SecurityHelper::ACTION_REMOVE_HTML,
    )
);
    return $schema;