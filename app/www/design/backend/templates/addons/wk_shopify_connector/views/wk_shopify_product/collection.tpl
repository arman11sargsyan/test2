{assign var="id" value=0}
{if $account_id}
    {assign var="id" value=$account_id}
{/if}
<div id="content_product_import" class="hidden">

<form action="{""|fn_url}" method="get" name="shopify_import_products_form" class="form-horizontal form-edit">
<input type="hidden" name="account_id" value="{$id}" />
    <div class="alert alert-success">
        {__('shopify_category_map_notice')}
    </div>
    {* <div class="control-group">
        <label class="control-label">{__("colection_id")}{include file="common/tooltip.tpl" tooltip={__("import_colection_id_help")}}:</label>
        <div class="controls">
            <textarea type="text" name="colection_id" class="cm-trim input-large" placeholder="{__("import_colection_id_placeholder")}"/></textarea>
        </div>
    </div> *}
     <div class="alert alert-success">
        {__('choose_one')}
    </div>
{capture name="tabsbox"}
    <div id="content_smart_{$id}">        
        {include file="common/pagination.tpl" save_current_page=false save_current_url=true div_id="pagination_smart_`$account_id`" search=$search2}
        
        {assign var="rev" value="pagination_smart_`$account_id`"|default:"pagination_contents"}

        {assign var="c_icon" value="<i class=\"icon-`$search.sort_order_rev`\"></i>"}
        {assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}
            {if $smart_collection_list}
                <div class="table-responsive-wrapper">
                    <table width="100%" class="table table-middle">
                        <thead>
                        <tr>
                            <th width="1%" class="center">
                                {* {include file="common/check_items.tpl" check_disabled="1"}</th> *}
                            <th width="10%" class="center nowrap">{__("collection_id")}</th>
                            <th width="12%" class="center">{__("smart_collection_title")}</th>
                            
                        </tr>
                        </thead>
                        {foreach from=$smart_collection_list item=smart_collection}
                        <tr>
                            <td class="center">
                                <input type="radio" name="smart_collection_ids" value="{$smart_collection.id}" class="cm-item" />
                            </td>
                            <td class="center nowrap" data-th="{__("collection_id")}">
                                {$smart_collection.id}
                            </td>
                            <td class="center nowrap" data-th="{__("smart_collection_title")}">
                                {$smart_collection.title}
                            </td>
                        </tr>
                        {/foreach}
                    </table>
                </div>
            {else}
                <p class="no-items">{__("no_data")}</p>
            {/if}
        {include file="common/pagination.tpl" div_id="pagination_smart_`$account_id`"}
   <!--content_smart_{$id}--></div>

    <div id="content_custom_{$id}">
          {include file="common/pagination.tpl" save_current_page=false save_current_url=true div_id="pagination_custom_`$account_id`" search=$search3}
            {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}
            {assign var="rev" value="pagination_custom_`$account_id`"|default:"pagination_contents"}

            {assign var="c_icon" value="<i class=\"icon-`$search3.sort_order_rev`\"></i>"}
            {assign var="c_dummy" value="<i class=\"icon-dummy\"></i>"}
            {if $custom_collection_list}
                <div class="table-responsive-wrapper">
                    <table width="100%" class="table table-middle">
                        <thead>
                        <tr>
                            <th width="1%" class="left">
                                {* {include file="common/check_items.tpl" check_disabled="1"}</th> *}
                            <th width="10%" class="center nowrap">{__("collection_id")}</th>
                            <th width="12%" class="center">{__("custom_collection_title")}</th>
                        </tr>
                        </thead>
                        {foreach from=$custom_collection_list item=custom_collection}
                        <tr>
                            <td class="left">
                                <input type="radio" name="smart_collection_ids" value="{$custom_collection.id}" class="cm-item" />
                            </td>
                            <td class="center nowrap" data-th="{__("collection_id")}">
                                {$custom_collection.id}
                            </td>
                            <td class="center nowrap" data-th="{__("custom_collection_title")}">
                                {$custom_collection.title}
                            </td>
                        </tr>
                        {/foreach}
                    </table>
                </div>
            {else}
                <p class="no-items">{__("no_data")}</p>
            {/if}
        {include file="common/pagination.tpl" div_id="pagination_custom_`$account_id`"}
    <!--content_custom_{$id}--></div>


{/capture}
{include file="common/tabsbox.tpl" content=$smarty.capture.tabsbox}

    <div class="buttons-container buttons-container-picker">
        {include file="buttons/save_cancel.tpl" but_name="dispatch[wk_shopify_product.list_collection_products]" cancel_action="close" save=$id but_text=__("list_product") but_target_form="shopify_import_products_form"}
        {include file="buttons/button.tpl" but_name="dispatch[wk_shopify_product.all_sync]" cancel_action="close" save=$id but_text="Sync" but_target_form="shopify_import_products_form" but_meta="pull-left"}

       
        {* {include file="buttons/button.tpl"  but_text=__("close") but_role="close" but_target_form="update_shipping_template_form"  but_meta="btn cm-dialog-closer"}

        {include file="buttons/button.tpl" but_role="submit" but_name="dispatch[wk_shopify_product.list_collection_products]" but_target_form="shopify_import_products_form"  but_text=__("list_product") but_meta="btn btn-primary cm-submit cm-dialog-closer" but_id="import_product_btn"} *}
    </div>

</form>

<!--content_product_import--></div>