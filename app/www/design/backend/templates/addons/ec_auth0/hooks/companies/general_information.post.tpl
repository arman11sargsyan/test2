{if !"ULTIMATE"|fn_allowed_for}
{include file="common/subheader.tpl" title=__("auth0.vendor_info")}
<div class="control-group profile-field-company">
    <label for="elm_vendor_uuid" class="control-label cm-profile-field">{__("auth0.vendor_uuid")}:</label>
    <div class="controls">
    	<input readonly type="text" id="elm_vendor_uuid" name="company_data[vendor_uuid]" size="32" value="{$company_data.vendor_uuid}"" class="input-large ">
    </div>
</div>
{/if}