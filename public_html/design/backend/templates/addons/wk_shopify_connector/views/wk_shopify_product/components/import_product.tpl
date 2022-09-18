<div class="hidden" title="{__("import_product_from_shopify")}" id="content_product_import">
<form action="{""|fn_url}" class="form-horizontal form-edit" name="shopify_import_products_form">
<div style="padding:10px;">
    <input type="hidden" name="account_id" value="{$account_id}"/>
    {* <div class="control-group">
        <label class="control-label">{__("colection_id")}{include file="common/tooltip.tpl" tooltip={__("import_colection_id_help")}}:</label>
        <div class="controls">
            <textarea type="text" name="colection_id" class="cm-trim input-large" placeholder="{__("import_colection_id_placeholder")}"/></textarea>
        </div>
    </div> *}
    {* <p class="center"><b>{__("or")}</b></p>
    <div class="control-group">
        <label class="control-label">{__("listing_status")}{include file="common/tooltip.tpl" tooltip={__("listing_status_help")}}:</label>
        <div class="controls">
            <select name="listing_status" class="input-text">
                <option value="active">{__('active')}</option>
                <option value="draft">{__("draft")}</option>
                <option value="inactive">{__("inactive")}</option>
                <option value="expired">{__("expired")}</option>
                <option value="featured">{__("featured")}</option>
            </select> 
        </div>
    </div> *}
    <div class="buttons-container buttons-container-picker">
            {include file="buttons/button.tpl"  but_text=__("close") but_role="close" but_target_form="update_shipping_template_form"  but_meta="btn cm-dialog-closer"}
        
            {include file="buttons/button.tpl" but_role="submit" but_name="dispatch[wk_shopify_product.list_collection_products]" but_target_form="shopify_import_products_form"  but_text=__("list_product") but_meta="btn btn-primary cm-submit cm-dialog-closer" but_id="import_product_btn"}
    </div>
</div>
</form>
<!--content_product_import--></div>