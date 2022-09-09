<div class="attach-images">
    <div class="upload-box clearfix">
        <h5>{__("vendorinformation_banner_image")}</h5> 
        <div class="image-upload">
           {include file="common/attach_images.tpl"
                    image_name="vendor_banners_main"
                    image_object_type="vendorbanners"
                    image_pair=$vendorbanner.main_pair
                    image_object_id=$company_data.company_id
                    no_detailed=true
                    hide_titles=true
                }
        </div>
    </div>
</div>

<div class="attach-images">
  <div class="control-label5" for="elm_company_description">{__("vendordescription")}:</div>
  <div class="subheadingdes">{__("vendorsubdescription")}</div>
        <div class="controls5">
	<textarea id="elm_company_description" name="company_data[company_description]" cols="35" rows="8" class="cm-wysiwyg input-large-desc">{$company_data.company_description}</textarea>
	</div>
</div>    


<style>
.image-upload
{
  margin-left :0px !important;
}
.control-label5
{
padding-top: 20px;
padding-bottom: 10px;
font-size: 18px;
font-weight: bold;
}
.subheadingdes
{
font-size: 16px;
margin-bottom: 20px !important;
}
.redactor-layer
{
height:200px !important;
}
</style>