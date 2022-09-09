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
use Tygh\Navigation\LastView;
use Tygh\EmailSync;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_ec_data_migration_create_company_admin($company_data, $fields, $notify, &$user)
{
    if( isset($company_data['skip_auth_creation']) )
    {
        $user['skip_auth_creation'] = true;
    }
}

function fn_ec_data_migration_update_company($company_data, $company_id, $lang_code, $action)
{
    if( $company_id )
    {
        if( isset($company_data['extra']) )
        {
            db_query('DELETE FROM ?:companies_data WHERE company_id = ?i', $company_id);
            $data = array();
            if( isset($company_data['extra']['addition_emails']) )
            {
                foreach ($company_data['extra']['addition_emails'] as $key => $value) {
                   $data[] = array(
                        'company_id' => $company_id,
                        'type' => 'E',
                        'value' => $value,
                   );
                }
    
            }
            if( isset($company_data['extra']['uuids']) )
            {
                foreach ($company_data['extra']['uuids'] as $key => $value) {
                   $data[] = array(
                        'company_id' => $company_id,
                        'type' => 'U',
                        'value' => $value,
                   );
                }
    
            }
            if( isset($company_data['extra']['migration_data']) )
            {
                   $data[] = array(
                        'company_id' => $company_id,
                        'type' => 'J',
                        'value' => $company_data['extra']['migration_data'],
                   );               

            }            
            db_query('INSERT INTO ?:companies_data ?m', $data);

        }
    }
}

function fn_migrate_vendors_old()
{
    $batch_size = 50;
    $offset = 0;
    $done = false;


    while(!$done){

        $vendors = db_get_array("SELECT * FROM devices_locations WHERE  StoreAdminUserID != 0 GROUP BY LocationName ORDER BY LocationName LIMIT ?i OFFSET ?i", $batch_size, $offset);

        if( $vendors )
        {
            #fn_print_r($vendors);
            foreach ($vendors as $key => $vendor) {

                #fn_print_r('vendor',$vendor);

                if( isset($vendor['LocationName']) )
                {
                    $company_data = array(
                        'company' => $vendor['LocationName'],
                        'email' =>  $vendor['LocationEmails'],
                        'status' => 'A',
                        'lang_code' => CART_LANGUAGE,
                        'plan_id' => 1,
                        'vendor_uuid' => $vendor['UUID'],
                        'seo_name' => $vendor['PageURL'],
                        'vendorcompany' => $vendor['LocationName'],
                        'address1' => $vendor['LocationAddress1'],
                        'address2' => $vendor['LocationAddress2'],
                        'addresscity' => $vendor['LocationAddress4'],
                        'addresscountry' => $vendor['LocationCountryCode'],
                        'addressstate' => '',
                        'addresszip' => $vendor['LocationPostcode'],
                        'uuid' => $vendor['UUID']  
                    );

                    $migration_data[] = $vendor;
                    $company_data['extra']['migration_data'] = json_encode($migration_data);


                    $exploded_emails = explode(",", $vendor['LocationEmails']);
                    if( count($exploded_emails) > 1 )
                    {
                        $company_data['email'] = $exploded_emails[0];
                        foreach ($exploded_emails as $key => $value) {
                            if( trim($value) != $company_data['email'] )
                            {
                                $company_data['extra']['addition_emails'][] = trim($value);
                            }
                        }                        
                    }
                    else
                    {
                        $company_data['email'] = $vendor['LocationEmails'];
                    }
  
                    $_vendors = db_get_array("SELECT * FROM devices_locations WHERE  StoreAdminUserID != 0 AND LocationName = ?s", $vendor['LocationName']);

                    if( count($_vendors) > 1)
                    {
                        $company_data['uuids'] = array();
                        foreach ($_vendors as $_key => $_vendor) {


                            if( $_vendor['LocationID'] != $vendor['LocationID'] )
                            {
                                 $migration_data[] = $_vendor;
                            }
                            
                            if( $company_data['address1'] == '')
                            {
                                $company_data['address1'] = $_vendor['LocationAddress1'];
                            }
                            if( $company_data['address2'] == '')
                            {
                                $company_data['address2'] = $_vendor['LocationAddress2'];
                            }
                            if( $company_data['addresscity'] == '')
                            {
                                $company_data['addresscity'] = $_vendor['LocationAddress4'];
                            }
                            if( $company_data['addresszip'] == '')
                            {
                                $company_data['addresszip'] = $_vendor['LocationPostcode'];
                            }
                            if( $company_data['addresscountry'] == '')
                            {
                                $company_data['addresscountry'] = $_vendor['LocationCountryCode'];
                            }    
                                                        
                            $_exploded_emails = explode(",", $_vendor['LocationEmails']);
                            if( count($_exploded_emails) > 1 )
                            {
                                foreach ($_exploded_emails as $key => $_value) {

                                    if( trim($_value) != $company_data['email'] )
                                    {
                                        $company_data['extra']['addition_emails'][] = trim($_value);
                                    }
                                }
                            }
                          
                            if($_vendor['UUID'] != $company_data['uuid'] )
                            {
                                $company_data['extra']['uuids'][] = $_vendor['UUID'];
                            }
                        }
                    }

                    if(isset($company_data['extra']['addition_emails']))
                    {
                        $company_data['extra']['addition_emails'] = array_unique($company_data['extra']['addition_emails']);
                    }

                    $company_data['extra']['migration_data'] = json_encode($migration_data);
                    #fn_print_r('company_data',$company_data, $migration_data);

                    $params = array(
                        'is_search' => 'Y',
                        'company' => $vendor['LocationName']
                    );
                    list($companies, $search) = fn_get_companies($params, $auth, Registry::get('settings.Appearance.admin_elements_per_page'));

                    #fn_print_r('companies',$companies);

                    if(!$companies)
                    {
                        $company_id = fn_update_company($company_data, 0, CART_LANGUAGE);
                    }
                    else
                    {
                        if(count($companies)>1)
                        {
                            fn_print_r('more', $vendor);

                        }

                        foreach ($companies as $key => $company) {
                            if( $company['company'] == $vendor['LocationName'] )
                            {
                                $company_id = fn_update_company($company_data,$company['company_id'], CART_LANGUAGE);
                                 break;
                            }
                        }
                    }
                }
            }

            $offset += $batch_size;
        }
        else
        {
            $done = true;
        }
    }


     fn_print_die('end');
}



function fn_migrate_vendors()
{
    $batch_size = 50;
    $offset = 0;
    $done = false;


    while(!$done){

        $vendors = db_get_array("SELECT * FROM devices_locations WHERE StoreAdminUserID != 0 AND LocationName != 'REMOVED' ORDER BY LocationName LIMIT ?i OFFSET ?i", $batch_size, $offset);

        if( $vendors )
        {
            foreach ($vendors as $key => $vendor) {

                if( isset($vendor['LocationName']) )
                {
                    $company_data = array(
                        'company' => (string)$vendor['LocationName'].'',
                        'email' =>  $vendor['LocationEmails'],
                        'status' => 'A',
                        'lang_code' => CART_LANGUAGE,
                        'plan_id' => 1,
                        'vendor_uuid' => $vendor['UUID'],
                        'seo_name' => $vendor['PageURL'],
                        'vendorcompany' => $vendor['LocationName'],
                        'address' => $vendor['LocationAddress1'].', '.$vendor['LocationAddress2'],
                        'address1' => $vendor['LocationAddress1'],
                        'address2' => $vendor['LocationAddress2'],
                        'addresscity' => $vendor['LocationAddress4'],
                        'city' => $vendor['LocationAddress4'],
                        'country' => $vendor['LocationCountryCode'],
                        'addresscountry' => $vendor['LocationCountryCode'],
                        'addressstate' => '',
                        'addresszip' => strtoupper($vendor['LocationPostcode']),
                        'zipcode' => strtoupper($vendor['LocationPostcode']),
                        'uuid' => $vendor['UUID'],
                        'phone' => format_E164_vendor($vendor['LocationTel']),
                        'latitude' => $vendor['Latitude'],
                        'longitude' => $vendor['Longitude'],
                        'is_create_vendor_admin' => 'Y'
                    );


                    $company_data['extra']['migration_data'] = json_encode($vendor);



                    $exploded_emails = explode(",", $vendor['LocationEmails']);
                    if( count($exploded_emails) > 1 )
                    {
                        $company_data['email'] = $exploded_emails[0];
                        foreach ($exploded_emails as $key => $value) {
                            if( trim($value) != $company_data['email'] )
                            {
                                $company_data['extra']['addition_emails'][] = trim($value);
                            }
                        }                        
                    }
                    $exploded_emails = explode(";", $vendor['LocationEmails']);
                    if( count($exploded_emails) > 1 )
                    {
                        $company_data['email'] = $exploded_emails[0];
                        foreach ($exploded_emails as $key => $value) {
                            if( trim($value) != $company_data['email'] )
                            {
                                $company_data['extra']['addition_emails'][] = trim($value);
                            }
                        }                        
                    }                    

                    if(isset($company_data['extra']['addition_emails']))
                    {
                        $company_data['extra']['addition_emails'] = array_unique($company_data['extra']['addition_emails']);
                    }

                    $exists_already = db_get_row("SELECT * FROM ?:companies WHERE vendor_uuid = ?s LIMIT 1", $vendor['UUID']);

                    if($exists_already && !empty($exists_already))
                    {
                        $company_id = fn_update_company($company_data,$exists_already['company_id'], CART_LANGUAGE);
                    }
                    else
                    {
                        $company_id = fn_update_company($company_data, 0, CART_LANGUAGE);
                        $company_data['company_id'] = $company_id;
    
                        $email_exists_already = db_get_row("SELECT * FROM ?:users WHERE email = ?s LIMIT 1", $company_data['email']);
                        if(!$email_exists_already)
                        {
                            $company_data['admin_firstname'] = '';
                            $company_data['admin_lastname'] = '';
                            $company_data['skip_auth_creation'] = true;
                            $fields = array();
                            fn_create_company_admin($company_data, $fields, false);           
                        }
                    }

                    unset($vendor);
                }
            }
            
            $offset += $batch_size;
        }
        else
        {
            $done = true;
        }
    }
}



function format_E164_vendor($num) 
{
    $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
    $phoneNumberMatcher = $phoneNumberUtil->findNumbers($num, 'GB');

    $numbers = array();
    foreach ($phoneNumberMatcher as $phoneNumberMatch) {
        $numbers[] = $phoneNumberMatch->number();
    }  

    if($numbers)
    {
        return $phoneNumberUtil->format($numbers[0], \libphonenumber\PhoneNumberFormat::E164);
    }
    else{
        return '';
    }
}



