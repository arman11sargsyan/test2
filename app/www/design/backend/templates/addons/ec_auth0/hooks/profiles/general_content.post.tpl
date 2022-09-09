{include file="common/subheader.tpl" title=__("auth0.user_info")}
<div class="control-group profile-field-company">
    <label for="elm_vendor_uuid" class="control-label cm-profile-field">{__("auth0.user_uuid")}:</label>
    <div class="controls">
    	<input readonly type="text" id="elm_vendor_uuid" name="user_data[auth0_user_id]" size="32" value="{$user_data.auth0_user_id}"" class="input-large ">
    </div>
</div>