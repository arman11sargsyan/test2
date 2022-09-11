<div class="hidden" title="{__("import_product_from_woocommerce")}" id="content_product_import">
{* {cm-ajax cm-comet} *}
<form action="{""|fn_url}" method="post" enctype="multipart/form-data" class="form-horizontal form-edit cm-ajax cm-comet cm-ajax-full-render" name="woocommerce_import_products_form">
<div style="padding:10px;">
    <input type="hidden" name="fake" value="1" />
    <input type="hidden" name="result_ids" value="pagination_contents"/>
    <input type="hidden" name="account_id" value="{$account_id}"/>
    <div class="control-group">
        <label class="control-label">{__("woocommerce_product_id")}{include file="common/tooltip.tpl" tooltip={__("import_product_id_help")}}:</label>
        <div class="controls">
            <textarea type="text" name="woocommerce_product_id" class="cm-trim input-large" placeholder="{__("import_product_id_placeholder")}"/></textarea>
        </div>
    </div>
    <p class="center"><b>{__("or")}</b></p>
    <div class="control-group">
        <label class="control-label">{__("woocommerce_product_status")}{include file="common/tooltip.tpl" tooltip={__("woocommerce_product_status_help")}}:</label>
        <div class="controls">
            {*<select name="woocommerce_product_status" class="input-text">
                <option value="publish">{__('publish')}</option>
                <option value="draft">{__("draft")}</option>
                <option value="pending">{__("pending")}</option>
                <option value="private">{__("private")}</option>
            </select> *}
            <div class="object-selector">
                <select id="woocommerce_product_status"
                        class="cm-object-selector"
                        name="woocommerce_product_status"
                        single
                        data-ca-placeholder="{__("search")}"
                        data-ca-enable-search="true"
                        data-ca-enable-images="false"
                        data-ca-close-on-select="true"
                        data-ca-load-via-ajax="false"
                        data-ca-allow-clear="true">
                    <option value="">-{__("select")}-</option>
                    <option value="any">{__('all')}</option>                    
                    <option value="publish">{__('publish')}</option>
                    <option value="draft">{__("draft")}</option>
                    <option value="pending">{__("pending")}</option>
                    <option value="private">{__("private")}</option>
                </select>
            </div>
        </div>
    </div>
    <div class="buttons-container buttons-container-picker">
            {include file="buttons/button.tpl"  but_text=__("close") but_role="close" but_target_form="woocommerce_import_products_form"  but_meta="btn cm-dialog-closer"}
        
            {include file="buttons/button.tpl" but_role="submit" but_name="dispatch[wk_woocommerce_product.import_products]" but_target_form="woocommerce_import_products_form"  but_text=__("import") but_meta="btn btn-primary cm-submit cm-dialog-closer" but_id="import_product_btn"}
    </div>
</div>
</form>
<!--content_product_import--></div>