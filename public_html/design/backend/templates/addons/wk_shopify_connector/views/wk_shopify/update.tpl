{capture name="mainbox"}

{capture name="tabsbox"}

{if $merchant_data.shop_id}
    {$id = $merchant_data.shop_id}
{else}
    {$id = 0}
{/if}
    <form id='form' action="{""|fn_url}" method="post" name="add_shopify_account_form" class="form-horizontal form-edit" enctype="multipart/form-data" id="add_shopify_account_form">
    <div class="account-manage" id="content_shopify_general">
        <input type="hidden" name="id" id="elm_company_exists_store" value="{$merchant_data.shop_id}" />
        {include file="common/subheader.tpl" title=__("shopify_credentials")}
          {if ("ULTIMATE"|fn_allowed_for || "MULTIVENDOR"|fn_allowed_for)}
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
                            <p><a href='{"companies.update?company_id=`$merchant_data.company_id`"|fn_url}'>{$merchant_data.company_id|fn_get_company_name}</a></p>
                        </div>
                    </div>
                {else}
                    
                    {include file="views/companies/components/company_field.tpl"
                        name="merchant_data[company_id]"
                        id="merchant_data_company_id"
                        selected=$runtime.company_id
                        tooltip=$companies_tooltip
                    }
                {/if}
        {/if}
        <div class="control-group {if $id}cm-hide-inputs{/if}">
            <label for="elm_shopify_api_key" class="control-label cm-required cm-trim">{__("wk_shopify.shopify_api_key")}{include file="common/tooltip.tpl" tooltip={__("wk_shopify.shopify_api_key_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[shopify_api_key]" id="elm_shopify_api_key" value="{if $id}*******************{/if}" {if $id}readonly {/if}>
            </div>
        </div>
        <div class="control-group {if $id}cm-hide-inputs{/if}">
            <label for="elm_shopify_secret_key" class="control-label cm-required cm-trim">{__("wk_shopify.shopify_secret_key")}{include file="common/tooltip.tpl" tooltip={__("wk_shopify.shopify_secret_key_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[shopify_shared_secret_key]" id="elm_shopify_secret_key" value="{if $merchant_data.api_secret} ******************* {/if}" {if $id}readonly {/if}>
            </div>
        </div>
        <div class="control-group {if $id}cm-hide-inputs{/if}">
            <label for="elm_domain_name" class="control-label cm-required cm-trim">{__("wk_shopify.domain_name")}{include file="common/tooltip.tpl" tooltip={__("wk_shopify.domain_name_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[domain_name]" id="elm_domain_name" value="{$merchant_data.domain_name}" {if $id}readonly {/if} placeholder="shopname.myshopify.com">
            </div>
        </div>
        <div class="control-group {if $id}cm-hide-inputs{/if}">
            <label for="elm_shop_name" class="control-label cm-required cm-trim">{__("wk_shopify.shop_name")}{include file="common/tooltip.tpl" tooltip={__("wk_shopify.shop_name_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[shop_name]" id="elm_shop_name" value="{$merchant_data.shop_name}" {if $id}readonly {/if}>
            </div>
        </div>
    </div>
    <div class="control-group" id="content_product_settings">
        {if $merchant_data.default_cscart_category_id}
            {assign var="request_category_id" value=","|explode:$merchant_data.default_cscart_category_id}
        {else}
            {assign var="request_category_id" value=""}
        {/if}
        {math equation="rand()" assign="rnd"}
        <label for="ccategories_{$rnd}_ids" class="control-label cm-required">{__("default_cscart_category")}{include file="common/tooltip.tpl" tooltip={__("default_cscart_category_help")}}:</label>
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
        <br>
        <div class="control-group">
                <label for="default_shopify_currency" class="control-label cm-trim cm-required">{__("shopify_currency")}{include file="common/tooltip.tpl" tooltip={__("shopify_currency_help")}}:</label>
                <div class="controls">
                    <select id="default_shopify_currency" name="merchant_data[shopify_currency_code]">
                        <option value="0">{__("select")}</option>
                        {if $currencies}
                        {foreach from = $currencies item=currency}
                             <option value="{$currency.currency_code}" {if $merchant_data.shopify_currency_code == $currency.currency_code}selected{/if}>{$currency.currency_code}</option>
                        {/foreach}
                        {/if}
                    </select>
                </div>
            </div>
            {* {fn_print_die($merchant_data)} *}
            <div class="control-group">
                <label for="default_shopify_variaton_one_or_not" class="control-label cm-trim cm-required">{__("shopify_variaton_one_or_not")}{include file="common/tooltip.tpl" tooltip={__("shopify_variaton_one_or_not_help")}}:</label>
                <div class="controls">
                    <select id="default_shopify_variaton_one_or_not" name="merchant_data[shopify_variaton_one_or_not]">
                             <option value="Y" {if $merchant_data.wk_data_for_variaton_one_or_not == 'Y'}selected{/if}>Yes</option>
                             <option value="N" {if $merchant_data.wk_data_for_variaton_one_or_not == 'N'}selected{/if}>No</option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label for="default_shopify_shopify_for_draft_product" class="control-label cm-trim cm-required">{__("shopify_for_draft_product")}{include file="common/tooltip.tpl" tooltip={__("shopify_for_draft_product_help")}}:</label>
                <div class="controls">
                    <select id="default_shopify_for_draft_product" name="merchant_data[wk_data_for_shopify_draft_product]">
                             <option value="A" {if $merchant_data.wk_shopify_draft_product_import == 'A'}selected{/if}>Active</option>
                             <option value="D" {if $merchant_data.wk_shopify_draft_product_import == 'D'}selected{/if}>Disabled</option>
                             <option value="H" {if $merchant_data.wk_shopify_draft_product_import == 'H'}selected{/if}>Hidden</option>
                    </select>
                </div>
            </div>
            </div>
    <!--content_product_settings--></div>
        <div class="control-group" id="content_order_settings">
        {assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses}
            <div class="control-group hidden">
                <label for="close_shopify_order" class="control-label cm-trim">{__("close_shopify_order")}{include file="common/tooltip.tpl" tooltip={__("close_shopify_order_help")}}:</label>
                <div class="controls">
                    <select id="default_close_shopify_order" name="merchant_data[order_close_status]">
                        <option value="0">{__("select")}</option>
                        {if $statuses}
                            {foreach from=$statuses item="s" key="k"}
                                <option value="{$k}" {if $merchant_data.order_close_status == $k}selected="selected"{/if}>{$s}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
            </div>
            <div class="control-group hidden">
                <label for="cancel_shopify_order" class="control-label cm-trim">{__("cancel_shopify_order")}{include file="common/tooltip.tpl" tooltip={__("cancel_shopify_order_help")}}:</label>
                <div class="controls">
                    <select id="default_cancel_shopify_order" name="merchant_data[order_cancel_status]">
                        <option value="0">{__("select")}</option>
                        {if $statuses}
                            {foreach from=$statuses item="s" key="k"}
                                <option value="{$k}" {if $merchant_data.order_cancel_status == $k}selected="selected"{/if}>{$s}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label for="default_payment_processor" class="control-label cm-trim cm-required">{__("payment_processor")}{include file="common/tooltip.tpl" tooltip={__("payment_processor_help")}}:</label>
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
                <label for="default_shipping_method" class="control-label cm-trim cm-required">{__("shipping_method")}{include file="common/tooltip.tpl" tooltip={__("shipping_method_help")}}:</label>
                <div class="controls">
                    <select id="default_shipping_method" name="merchant_data[default_shipping]">
                        <option value="">{__("select")}</option>
                        {if $shipping_arr}
                            {foreach from=$shipping_arr item="s" key="k"}
                                <option value="{$k}" {if $merchant_data.default_shipping == $k}selected="selected"{/if}>{$s}</option>
                            {/foreach}
                        {/if}
                    </select>
                </div>
            </div>
    <!--content_order_settings--></div>
    {if $id}
        <div class="control-group" id="content_webhook_setting">
           {if !$merchant_data.webhook_id}
            {include file="buttons/button.tpl" but_href="wk_shopify.registerhook&id=`$id`" but_text=__("registerhook") but_role="link" but_meta="btn btn-secondory cm-post"}
           {else}
                <ul>
                    {foreach from=$merchant_data.webhook_details item=item key=key name=name}
                        <li>
                            <p> {__('Topic')} : {$item.topic} </p>
                            <p>{__('address')} : {$item.webhook_url} </p>
                        </li>
                    {/foreach}
                </ul>
                 {include file="buttons/button.tpl" but_href="wk_shopify.deletehook&id=`$id`" but_text=__("delete") but_role="link" but_meta="btn btn-secondory cm-post"}
           {/if}
        <!--content_webhook_setting--></div>
    {/if}
</form>


{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox active_tab=$smarty.request.selected_section track=true}

{capture name="buttons"}   
    {if $id}
        {include file="buttons/save_cancel.tpl" but_name="dispatch[wk_shopify.update]" but_target_form="add_shopify_account_form" hide_second_button=false save=$id}
    {else}
        {include file="buttons/save_cancel.tpl" but_name="dispatch[wk_shopify.authenticate]" but_target_form="add_shopify_account_form" hide_second_button=true but_text=__("shopify_authenticate")}
    {/if}
{/capture}
{capture name="sidebar"}
{if $id}
    {* {include file="addons/wk_shopify_connector/common/components/tab.tpl"} *}
{/if}
{/capture}
{/capture}
    {$but_text=__("add_shopify_merchant")}
    {if $id}
        {$but_text=__("edit_shopify_merchant")}
    {/if}
{include file="common/mainbox.tpl" title=$but_text content=$smarty.capture.mainbox sidebar=$smarty.capture.sidebar buttons=$smarty.capture.buttons}