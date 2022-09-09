{capture name="mainbox"}

{capture name="tabsbox"}
{if $merchant_data.shop_id}
{$id = $merchant_data.shop_id}
{else}
{$id = 0}
{/if}
<form id='form' action="{""|fn_url}" method="post" name="add_woocommerce_account_form" class="form-horizontal cm-disable-empty-files" enctype="multipart/form-data" id="add_woocommerce_account_form">
    <div class="account-manage" id="content_wk_general">
        <input type="hidden" name="shop_id" id="elm_company_exists_store" value="{$merchant_data.shop_id}" />
        {include file="common/subheader.tpl" title=__("woocommerce_shop_details")}
          {if ("ULTIMATE"|fn_allowed_for || "MULTIVENDOR"|fn_allowed_for) && !$runtime.company_id}
                {if $merchant_data.shop_id}
                    <div class="control-group">
                        <label class="control-label cm-required" for="elm_company_exists_store">
                        {if "ULTIMATE"|fn_allowed_for}
                            {__("store")}:
                        {else}
                            {__("vendor")}:
                        {/if}
                        </label>
                        <div class="controls">
                            <input type="hidden" name="merchant_data[company_id]" value="{$merchant_data.company_id}" />
                            <p><a href='{"companies.update?company_id=`$merchant_data.company_id`"|fn_url}'>{$merchant_data.company_id|fn_get_company_name}</a></p>
                        </div>
                    </div>
                {else}
                    {include file="views/companies/components/company_field.tpl"
                        name="merchant_data[company_id]"
                        id="merchant_data_company_id"
                        selected=$merchant_data.company_id
                        tooltip=$companies_tooltip
                    }
                {/if}
        {/if}
        {if $merchant_data.consumer_key}
        <div class="control-group {if $merchant_data.consumer_key}cm-hide-inputs{/if}">
            <label for="elm_consumer_key" class="control-label cm-required cm-trim">{__("wk_woocommerce.consumer_key")}{include file="common/tooltip.tpl" tooltip={__("wk_woocommerce.consumer_key_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[consumer_key]" id="elm_consumer_key" value="{$merchant_data.consumer_key}" {if $merchant_data.consumer_key}readonly {/if}>
            </div>
        </div>
        {/if}
        {if $merchant_data.consumer_secret}
        <div class="control-group {if $merchant_data.consumer_secret}cm-hide-inputs{/if}">
            <label for="elm_consumer_secret" class="control-label cm-required cm-trim">{__("wk_woocommerce.consumer_secret")}{include file="common/tooltip.tpl" tooltip={__("wk_woocommerce.consumer_secret_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[consumer_secret]" id="elm_consumer_secret" value="{$merchant_data.consumer_secret}" {if $merchant_data.consumer_secret}readonly {/if}>
            </div>
        </div>
        {/if}
        <div class="control-group {if $merchant_data.consumer_secret && $merchant_data.consumer_key}cm-hide-inputs{/if}">
            <label for="elm_app_name" class="control-label cm-required cm-trim">{__("wk_woocommerce.app_name")}{include file="common/tooltip.tpl" tooltip={__("wk_woocommerce.app_name_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[app_name]" id="elm_app_name" value="{$merchant_data.app_name}" {if $merchant_data.consumer_secret && $merchant_data.consumer_key}readonly {/if}>
                <span id="elm_app_name_html_message" class="help-inline"><p></p></span>
            </div>
        </div>
        <input type = "hidden" name="merchant_data[shop_id]" value= {$account_id} >
        <div class="control-group {if $merchant_data.consumer_secret && $merchant_data.consumer_key}cm-hide-inputs{/if}">
            <label for="elm_store_url" class="control-label cm-required cm-trim">{__("wk_woocommerce.store_url")}{include file="common/tooltip.tpl" tooltip={__("wk_woocommerce.store_url_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[store_url]" id="elm_store_url" value="{$merchant_data.store_url}" {if $merchant_data.consumer_secret && $merchant_data.consumer_key}readonly {/if}>
                <span id="elm_store_url_html_message" class="help-inline"></span>
            </div>
        </div>

    <!--content_general--></div>

    <div class="control-group" id="content_order_settings">
        {if $merchant_data.consumer_secret && $merchant_data.consumer_key}
        {assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses}
            <div class="control-group hidden">
                <label for="default_order_status" class="control-label cm-trim cm-required">{__("woo_default_order_status")}{include file="common/tooltip.tpl" tooltip={__("default_order_status_help")}}:</label>
                <div class="controls">
                    <select id="default_order_status" name="merchant_data[default_order_status]">
                        <option value="0">{__("select")}</option>
                        {if $statuses}
                            {foreach from=$statuses item="s" key="k"}
                                <option value="{$k}" {if $merchant_data.default_order_status == $k}selected="selected"{/if}>{$s}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
            </div>

            <div class="control-group">
                <label for="default_payment_processor" class="control-label cm-trim cm-required">{__("woo_payment_processor")}{include file="common/tooltip.tpl" tooltip={__("payment_processor_help")}}:</label>
                <div class="controls">
                    <select id="default_payment_processor" name="merchant_data[default_payment]">
                        <option value="">{__("select")}</option>
                        {if $payment_arr}
                            {foreach from=$payment_arr item="s" key="k"}
                                <option value="{$s.payment_id}" {if $merchant_data.default_payment == $s.payment_id}selected="selected"{/if}>{$s.payment}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
            </div>
            
            <div class="control-group">
                <label for="default_shipping_method" class="control-label cm-trim cm-required">{__("woo_shipping_method")}{include file="common/tooltip.tpl" tooltip={__("shipping_method_help")}}:</label>
                <div class="controls">
                    <select id="default_shipping_method" name="merchant_data[default_shipping]">
                        <option value="">{__("select")}</option>
                        {if $shipping_arr}
                        
                            {foreach from=$shipping_arr item="s" key="k"}
                                <option value="{$s.shipping_id}" {if $merchant_data.default_shipping == $s.shipping_id}selected="selected"{/if}>{$s.shipping}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
            </div>
        {/if}
    <!--content_order_settings--></div>

    <div id="content_product_settings">
        {if $merchant_data.consumer_secret && $merchant_data.consumer_key}
        <div class="control-group">
            {if $merchant_data.default_cscart_category_id}
                {assign var="request_category_id" value=","|explode:$merchant_data.default_cscart_category_id}
            {else}
                {assign var="request_category_id" value=""}
            {/if}
            {math equation="rand()" assign="rnd"}
            <label for="ccategories_{$rnd}_ids" class="control-label cm-required">{__("woo_default_cscart_category")}{include file="common/tooltip.tpl" tooltip={__("woo_default_cscart_category_help")}}:</label>
            <div class="controls">
                {include file="pickers/categories/picker.tpl"
                    company_ids=$merchant_data.company_id
                    rnd=$rnd
                    data_id="categories"
                    input_name="merchant_data[default_cscart_category_id]"
                    main_category=$merchant_data.default_cscart_category_id
                    item_ids=$request_category_id
                    hide_link=true
                    hide_delete_button=true
                    display_input_id="category_ids"
                    disable_no_item_text=true
                    but_meta="btn"
                    show_active_path=true}
            </div>
        </div>
        <div class="control-group">
                <label for="woocommerce_currency" class="control-label cm-trim">{__("woocommerce_currency")}{include file="common/tooltip.tpl" tooltip={__("woocommerce_currency_help")}}:</label>
                <div class="controls">
                    <select id="default_shopify_currency" name="merchant_data[default_currency_code]">
                        <option value="0">{__("select")}</option>
                        {if $currencies}
                        {foreach from = $currencies item=currency}
                             <option value="{$currency.currency_code}" {if $merchant_data.default_currency_code == $currency.currency_code}selected{/if}>{$currency.currency_code}</option>
                        {/foreach}
                        {/if}
                    </select>
                </div>
            </div>
        {/if}
    <!--content_product_settings--></div>

    {if $merchant_data.consumer_secret && $merchant_data.consumer_key}
        <div class="control-group" id="content_webhook_settings">
            {if !$merchant_data.order_webhook_id}
                {include file="buttons/button.tpl" but_href="wk_woocommerce.register_order_webhook&id=`$id`" but_text=__("register_order_webhook") but_role="link" but_meta="btn btn-secondory cm-post"}
                <br><br>
            {/if}
            
            {if !$merchant_data.product_create_webhook_id && !$merchant_data.product_update_webhook_id}
                {include file="buttons/button.tpl" but_href="wk_woocommerce.register_product_webhook&id=`$id`" but_text=__("register_product_webhook") but_role="link" but_meta="btn btn-secondory cm-post"}
                <br><br>
            {/if}
            {if $merchant_data.webhook_data}
                <ul>
                    {foreach from=$merchant_data.webhook_data item=item name=name}
                        <li>
                            <p><b>{__('name')} </b>: {$item.name} </p>
                            <p><b>{__('topic')} </b>: {$item.topic} </p>
                            <p><b>{__('status')} </b>: {$item.status} </p>
                            <p><b>{__('address')} </b>: {$item.delivery_url} </p>
                        </li>
                        <br>
                    {/foreach}
                </ul>                  
            {/if}
        <!--content_webhook_settings--></div>
    {/if}

</form>
{/capture} 
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section track=true}

{capture name="buttons"}   
    {if $merchant_data.consumer_secret && $merchant_data.consumer_key}
        {include file="buttons/save_cancel.tpl" but_name="dispatch[wk_woocommerce.update]" but_target_form="add_woocommerce_account_form" hide_second_button=false save=$id}
    {else}
        {include file="buttons/save_cancel.tpl" but_id="authenticate" but_name="dispatch[wk_woocommerce.authenticate]" but_target_form="add_woocommerce_account_form" hide_second_button=true but_text=__("woocommerce_authenticate")}
    {/if}
{/capture}

{capture name="sidebar"}
    {include file="addons/wk_woocommerce/common/components/tab.tpl"}
{/capture}

{/capture}
    {$but_text=__("add_woocommerce_merchant")}
    {if $id}
        {$but_text=__("edit_woocommerce_merchant")}
    {/if}
{include file="common/mainbox.tpl" title=$but_text content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar buttons=$smarty.capture.buttons}


<script>
$('#authenticate').click(function(){
    var reg =/<(.|\n)*?>/g; 
    $('#elm_app_name_html_message p').text('');
    $('#elm_app_name').css('border-color','');
    $('#elm_store_url_html_message p').text('');
    $('#elm_store_url').css('border-color','');
    
    if (reg.test($('#elm_app_name').val()) == true) {
        
        $('#elm_app_name_html_message p').text('HTML Content Not Allowed').css('color','red');
        $('#elm_app_name').css('border-color','red');
    return false;

    }

    if (reg.test($('#elm_store_url').val()) == true) {
        $('#elm_store_url').css('border-color','red');
        $('#elm_store_url_html_message').text('HTML Content Not Allowed').css('color','red');
    return false;

    }
});
</script>
