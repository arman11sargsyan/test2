{capture name="mainbox"}

{capture name="tabsbox"}
{if $merchant_data.id}
{$id = $merchant_data.id}
{else}
{$id = 0}
{/if}
<form id='form' action="{""|fn_url}" method="post" name="add_shopify_account_form" class="form-horizontal form-edit" enctype="multipart/form-data" id="add_shopify_account_form">
    <div class="account-manage" id="content_shopify_general">
        <input type="hidden" name="id" id="elm_company_exists_store" value="{$merchant_data.id}" />
        {include file="common/subheader.tpl" title=__("shopify_credentials")}
          {if ("ULTIMATE"|fn_allowed_for || "MULTIVENDOR"|fn_allowed_for) && !$runtime.company_id}
                {if $merchant_data.id}
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
                    {* {if "ULTIMATE"|fn_allowed_for}
                        {assign var="companies_tooltip" value=__("text_ult_product_store_field_tooltip")}
                    {/if} *}
                    {include file="views/companies/components/company_field.tpl"
                        name="merchant_data[company_id]"
                        id="merchant_data_company_id"
                        selected=$merchant_data.company_id
                        tooltip=$companies_tooltip
                    }
                {/if}
        {/if}
        <div class="control-group {if $id}cm-hide-inputs{/if}">
            <label for="elm_shopify_api_key" class="control-label cm-required cm-trim">{__("wk_shopify.shopify_api_key")}{include file="common/tooltip.tpl" tooltip={__("wk_shopify.shopify_api_key_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[shopify_api_key]" id="elm_shopify_api_key" value="{$merchant_data.shopify_api_key}" {if $id}readonly {/if}>
            </div>
        </div>
        <div class="control-group {if $id}cm-hide-inputs{/if}">
            <label for="elm_shopify_shared_secret_key" class="control-label cm-required cm-trim">{__("wk_shopify.shopify_shared_secret_key")}{include file="common/tooltip.tpl" tooltip={__("wk_shopify.shopify_shared_secret_key_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[shopify_shared_secret_key]" id="elm_shopify_shared_secret_key" value="{$merchant_data.shared_secret_key}" {if $id}readonly {/if}>
            </div>
        </div>
        <div class="control-group {if $id}cm-hide-inputs{/if}">
            <label for="elm_domain_name" class="control-label cm-required cm-trim">{__("wk_shopify.domain_name")}{include file="common/tooltip.tpl" tooltip={__("wk_shopify.domain_name_help")}}:</label>
            <div class="controls">
                <input type="text" class="" name="merchant_data[domain_name]" id="elm_domain_name" value="{$merchant_data.shop_id}" {if $id}readonly {/if}>
            </div>
        </div>
    {if $id}
        <style>
            .form-horizontal .control-label{
                width:205px !important;
            }
            .form-horizontal .controls {
                margin-left: 215px !important;
            }
        </style>
        <!--content_general--></div>
        {* <div class="account-manage hidden" id="content_etsy_settings"> *}
            {include file="common/subheader.tpl" title=__("etsy_product_import_settings") }
            <div class="control-group" id="default_categories">
                {if $merchant_data.default_cscart_category_id}
                    {assign var="request_category_id" value=","|explode:$merchant_data.default_cscart_category_id}
                {else}
                    {assign var="request_category_id" value=""}
                {/if}
                {math equation="rand()" assign="rnd"}
                <label for="ccategories_{$rnd}_ids" class="control-label cm-required">{__("wk_etsy.default_cscart_category")}{include file="common/tooltip.tpl" tooltip={__("wk_etsy.default_cscart_category_help")}}:</label>
                <div class="controls">
                    {include file="pickers/categories/picker.tpl"
                        company_ids=$merchant_data.company_id
                        rnd=$rnd
                        data_id="categories"
                        input_name="etsy_settings[default_cscart_category_id]"
                        main_category=$merchant_data.default_cscart_category_id
                        item_ids=$request_category_id
                        hide_link=true
                        hide_delete_button=true
                        display_input_id="category_ids"
                        disable_no_item_text=true
                        but_meta="btn"
                        show_active_path=true
                    }
                </div>
            <!--default_categories--></div>

            {include file="common/subheader.tpl" title=__("etsy_product_export_settings") }
            <div class="control-group">
                <label for="default_etsy_category" class="control-label cm-trim">{__("wk_etsy.default_etsy_category")}{include file="common/tooltip.tpl" tooltip={__("wk_etsy.default_etsy_category_help")}}:</label>
                <div class="controls">
                    <select id="default_etsy_category" name="etsy_settings[default_etsy_category_id]">
                        <option value="">{__("select")}</option>
                        {if $etsy_categories}
                        {foreach from = $etsy_categories item=category}
                             <option value="{$category.taxenomy_id}" {if $merchant_data.default_etsy_category_id == $category.taxenomy_id}selected{/if}>{$category.category}</option>
                        {/foreach}
                        {/if}
                    </select>

                    <span class="hidden c_loader">{__("loading")}<i class="icon-refresh"></i></span>
                </div>
            </div>
            <div class="control-group">
                <label for="elm_default_shipping_template" class="control-label cm-trim">{__("wk_etsy.default_shipping_template")}{include file="common/tooltip.tpl" tooltip={__("wk_etsy.default_shipping_template_help")}}:</label>
                <div class="controls">
                    <select name="etsy_settings[default_shipping_template_id]" id="elm_default_shipping_template">
                    <option value="">{__("select")}</option>  
                    {foreach from=$shipping_data item=item key=key name=name}
                        <option value="{$item.shipping_template_id}" {if $item.shipping_template_id == $merchant_data.default_shipping_template_id}selected{/if}>{$item.title}</option>
                    {/foreach}
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label for="elm_default_product_export_state" class="control-label cm-trim">{__("wk_etsy.default_product_export_state")}{include file="common/tooltip.tpl" tooltip={__("wk_etsy.default_product_export_state_help")}}:</label>
                <div class="controls">
                    <select name="etsy_settings[default_listing_state]" id="elm_default_product_export_state">
                        <option value="draft" {if $merchant_data.default_listing_state == 'draft'}selected{/if}>{__("draft")}</option>
                        <option value="active" {if $merchant_data.default_listing_state == 'active'}selected{/if}>{__("active")}</option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label for="elm_default_who_made_it" class="control-label cm-trim">{__("default_who_made_it")}{include file="common/tooltip.tpl" tooltip={__("default_who_made_it_help")}}:</label>
                <div class="controls">
                    <select name="etsy_settings[default_who_made]" id="elm_default_who_made_it">
                        <option value="i_did" {if $merchant_data.default_who_made == 'i_did'}selected{/if}>{__("i_did")}</option>
                        <option value="collective" {if $merchant_data.default_who_made == 'collective'}selected{/if}>{__("collective")}</option>
                        <option value="someone_else" {if $merchant_data.default_who_made == 'someone_else'}selected{/if}>{__("someone_else")}</option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label for="elm_when_made" class="control-label cm-trim">{__("default_when_made")}{include file="common/tooltip.tpl" tooltip={__("default_when_made_help")}}:</label>
                <div class="controls">
                    <select name="etsy_settings[default_when_made]" id="elm_when_made">
                        <option value="made_to_order" {if $merchant_data.default_when_made == 'made_to_order'}selected{/if}>{__("made_to_order")}</option>
                        <option value="2010_2018" {if $merchant_data.default_when_made == '2010_2018'}selected{/if}>2010-2018</option>
                        <option value="2000_2009" {if $merchant_data.default_when_made == '2000_2009'}selected{/if}>2000-2009</option>
                        <option value="1999_1999" {if $merchant_data.default_when_made == '1999_1999'}selected{/if}>1999-1999</option>
                        <option value="before_1999" {if $merchant_data.default_when_made == 'before_1999'}selected{/if}>{__("before")}-1999</option>
                        <option value="1990_1998" {if $merchant_data.default_when_made == '1990_1998'}selected{/if}>1990-1998</option>
                    </select>
                </div>
            </div>
            <div class="control-group">
                <label for="elm_default_is_supply" class="control-label cm-trim">{__("default_is_supply")}{include file="common/tooltip.tpl" tooltip={__("default_is_supply_help")}}:</label>
                <div class="controls">
                    <select name="etsy_settings[default_is_supply]" id="elm_default_is_supply">
                        <option value="Y" {if $merchant_data.default_is_supply == 'Y'}selected{/if}>{__("yes")}</option>
                        <option value="N" {if $merchant_data.default_is_supply == 'N'}selected{/if}>{__("no")}</option>
                    </select>
                </div>
            </div>
        <!--content_etsy_settings--></div>     
        {* <div class="account-manage hidden" id="content_etsy_realtime"> *}
            <div class="control-group">
                <label for="elm_export_full_product_update" class="control-label cm-trim">{__("wk_etsy.export_full_product_update")}{include file="common/tooltip.tpl" tooltip={__("wk_etsy.export_full_product_update_help")}}:</label>
                <div class="controls">
                    <input type="hidden" name="etsy_settings[is_export_product_update]" value="N"/>
                    <input type="checkbox" name="etsy_settings[is_export_product_update]" value="Y" {if $merchant_data.is_export_product_update == 'Y'} checked {/if} id="elm_export_full_product_update"/>
                </div>
            </div>

            <div class="control-group">
                <label for="elm_import_full_product_update" class="control-label cm-trim">{__("wk_etsy.import_full_product_update")}{include file="common/tooltip.tpl" tooltip={__("wk_etsy.import_full_product_update_help")}}:</label>
                <div class="controls">
                    <input type="hidden" name="etsy_settings[is_import_product_update]" value="N"/>
                    <input type="checkbox" name="etsy_settings[is_import_product_update]" value="Y" {if $merchant_data.is_import_product_update == 'Y'} checked {/if} id="elm_import_full_product_update"/>
                </div>
            </div>

            <div class="control-group">
                <label for="elm_is_quantity_update" class="control-label cm-trim">{__("wk_etsy.is_quantity_update")}{include file="common/tooltip.tpl" tooltip={__("wk_etsy.is_quantity_update_help")}}:</label>
                <div class="controls">
                     <input type="hidden" name="etsy_settings[is_quantity_update]" value="N"/>
                    <input type="checkbox" name="etsy_settings[is_quantity_update]" value="Y" {if $merchant_data.is_quantity_update == 'Y'} checked {/if} id="elm_is_quantity_update"/>
                </div>
            </div>
        <!--content_etsy_realtime--></div>
        {* <div class="account-manage hidden"  id="content_etsy_downloads"> *}
            {include file="common/subheader.tpl" title=__("etsy_categories") target="#etsy_categories_header"}
            <div id="etsy_categories_header" class="collapse in">
                {* {include file="buttons/button.tpl" but_href="wk_etsy.download_categories&id=`$id`" but_text=__("download_etsy_categories") but_role="link" but_meta="btn btn-primary cm-post cm-comet cm-ajax"} *}
                {include file="buttons/button.tpl" but_href="wk_etsy.category_map&account_id=`$id`" but_text=__("map_etsy_categories") but_role="link" but_meta="btn btn-primary"}
            </div>

            {include file="common/subheader.tpl" title=__("etsy_countries") target="#etsy_etsy_countries_header"}
            <div id="etsy_etsy_countries_header" class="collapse in">
                {include file="buttons/button.tpl" but_href="wk_etsy.download_countries&id=`$id`" but_text=__("download_etsy_countries") but_role="link" but_meta="btn btn-secondory cm-post"}
                
                {include file="buttons/button.tpl" but_href="wk_etsy.view_countries&account_id=`$id`" but_text=__("view_etsy_countries") but_role="link" but_meta="btn btn-primary"}
        
            </div>
        <!--content_etsy_downloads--></div>
    {* {if !$etsy_categories}
        <script type="text/javascript">
            var api_key = "{$merchant_data.etsy_api_key}";
            var selected_category_id = "{$merchant_data.default_etsy_category_id}";
            var category_array = [];
            (function (_, $) {
                var categories = {};
                $(document).ready(function(){
                    $(".c_loader").show();
                    is_running = true;
                    etsyURL = "https://openapi.etsy.com/v2/taxonomy/seller/get.js?api_key="+api_key;
                    $.ajax({
                        url: etsyURL,
                        dataType: 'jsonp',
                        success: function(data) {
                            if(data.ok && data.count >0){
                                categories = data.results;
                                $.each(categories,function(i,item) {
                                    category = {};
                                    category.category_id = item.category_id;
                                    category.category = item.name;
                                    category.taxenomy_id = item.id;
                                    category_array[i]=category;
                                    $("#default_etsy_category").append("<option value="+item.id+" >"+item.name+"</option>");
                                });
                                $("#default_etsy_category").val(selected_category_id);
                                if(category_array != null && category_array.length >0)
                                    fn_send_category_to_store_database(JSON.stringify(category_array))
                                
                            } 
                            $(".c_loader").hide();
                        },
                    });
                });
                function fn_send_category_to_store_database(data){
                    var post_data = {};
                    post_data.data = data;
                    post_data.account_id = "{$merchant_data.id}";
                    $.ceAjax('request', fn_url('wk_etsy.store_categories'),{
                        method: 'post',
                        full_render: false,
                        data: post_data,
                    });
                }
            }(Tygh, Tygh.$));
        </script>
    {/if} *}
    {else}
        </div>
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