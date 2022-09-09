
{if "MULTIVENDOR"|fn_allowed_for}
<div id="content_terms_and_conditions" class="hidden">
    <div class="control-group">
        <label class="control-label" for="elm_company_terms">{__("vendor_terms.terms_and_conditions")}:</label>
        <div class="controls"><p class="muted description">{__("vendor_terms.terms_and_conditions_tooltip")}</p><textarea id="elm_company_terms" name="company_data[terms]" cols="55" rows="8" class="cm-wysiwyg input-large">{$company_data.terms}</textarea>            
        </div>
    </div>
</div>
{/if}
