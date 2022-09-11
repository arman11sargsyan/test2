<div class="hidden"  id="content_import_order_search" title="{__("import_order")}">
    <style>
        .division_box_container{
            padding: 0px 10px 0px 10px;
            margin-bottom: 20px;
            text-shadow: 0 1px 0 rgba(255,255,255,0.5);
            border: 1px solid #fbeed5;
        }
    </style>

    <form action="{""|fn_url}" class="form-horizontal form-edit" method="get" name="import_order_search_form" >
	<div style="padding:10px;">
        <input type="hidden" name="account_id" value="{$account_id}" />
        <div class="division_box_container">
            {include file="common/subheader.tpl" title=__("import_order_by_date") }
            <div class="alert alert-success">
                <div class="control-group">
                    <label class="control-label">{__("order_date_period")}</label>
                    <div class="controls">
                    {include file="common/calendar.tpl" date_name="min_created" date_id="CreatedAfterImport" date_val=$min_created date_meta="search-input-text" }-
                    {include file="common/calendar.tpl" date_name="max_created" date_id="CreatedBeforeImport" date_val=$search.max_created|default:$smarty.const.TIME  date_meta="search-input-text" max_date=$smarty.const.TIME}
                    </div>
                </div>
                <div class="control-group">
                    <label class="radio inline" for="elm_fullfillment_shipped"><input type="radio" name="fullfillment" id="elm_fullfillment_shipped" {if $fullfillment_shipped == 'S'}checked="checked"{/if} value="shipped">{__('fullfillment_shipped')}</label>
                    <label class="radio inline" for="fullfillment_partial"><input type="radio" name="fullfillment" id="fullfillment_partial" {if $fullfillment_partial == 'D'}checked="checked"{/if} value="partial">{__('fullfillment_partial')}</label>
                    <label class="radio inline" for="fullfillment_unshipped"><input type="radio" name="fullfillment" id="fullfillment_unshipped" {if $fullfillment_unshipped == 'U'}checked="checked"{/if} value="unshipped">{__('fullfillment_unshipped')}</label>
                    
                </div>
            </div>
        </div>

        <div class="division_box_container">
            {include file="common/subheader.tpl" title=__("import_order_by_order_id") }
            <div class="alert alert-success">
                <div class="control-group">
                    <label class="control-label" for="elm_order_id">{__("order_id")}</label>
                    <div class="controls">
                    <input type="text" name="order_ids" size="20" value="{$search.order_id}" id="elm_order_id" class="" style="width:100%" />
                    </div>
                </div>
            </div>
        </div>
        <div class="division_box_container">
            {include file="common/subheader.tpl" title=__("import_order_by_status") }
            <div class="alert alert-success">
                <div class="control-group">
                    <label class="control-label">{__("status")}</label>
                    <div class="controls">
                        <select name="order_status" class="input-text">
                            <option value="any">{__('all')}</option>
                            <option value="open" {if $search.order_status eq 'open'}selected{/if}>{__("open")}</option>
                            <option value="closed" {if $search.order_status eq 'closed'}selected{/if}>{__("closed")}</option>
                            <option value="cancelled" {if $search.order_status eq 'cancelled'}selected{/if}>{__("cancelled")}</option>
                        </select> 
                    </div>
                </div>
            </div>
        </div>        
       
        <div class="buttons-container buttons-container-picker">
			{assign var="but_label" value={__("import_order")}}
            {include file="buttons/button.tpl"  but_text=__("close") but_role="close"   but_target_form="import_order_search_form"  but_meta="btn cm-dialog-closer"}
			{include file="buttons/button.tpl"  but_text=$but_label but_role="submit" but_name="dispatch[wk_shopify.list_shopify_orders]"  but_target_form="import_order_search_form" but_id="import_form_submit" but_meta="btn btn-primary cm-submit cm-dialog-closer"}

		</div>
    </div>
    </form>
<!--content_import_order_search--></div>
	
