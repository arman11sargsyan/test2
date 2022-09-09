{if $auth.user_type == "V" && $runtime.company_data.status =='P' && ($runtime.company_data.company_golive =='0' || $runtime.company_data.company_golive =='1' || $runtime.company_data.company_golive =='2')}
<form id="form" action="{"vendorinformation.golive"|fn_url}" method="post" name="product_update_form" class="form-horizontal form-edit  cm-disable-empty-files">
{assign var="companyid" value=$runtime.company_data.company_id}
<input type="hidden" name="companyid" id="companyid" value="{$companyid}" />
<div class="vendor-manage" id="content_detailed"> 
<div class="mainvendorblock">
{assign var="startvalue" value="0"}

<!-- CHECK UPDATE PROFILE START -->
{assign var="updateprofile" value="0"}
{if $runtime.company_data.company !="" && $runtime.company_data.email !=""}
{assign var="updateprofile" value="1"}
{assign var="startvalue" value=$startvalue+1}
{/if}
<!-- CHECK UPDATE PROFILE END -->

<!-- CHECK PRODUCT -->
{assign var="activeproductarray" value=$companyid|fn_get_vendorinformation_companies_active_products_count}
{assign var="totalactiveproduct" value=$activeproductarray[$companyid]}

{if $totalactiveproduct > 0}
{assign var="startvalue" value=$startvalue+1}
{/if}
<!-- CHECK PRODUCT -->

<!--STRIPE CONNECT -->
{assign var="stripeconnect" value="0"}
{if $runtime.company_data.stripe_connect_account_id !=""}
{assign var="stripeconnect" value="1"}
{assign var="startvalue" value=$startvalue+1}
{/if}
<!--STRIPE CONNECT -->

<!-- CHECK SHIPPING -->
{assign var="vedorshippinglist" value=$companyid|fn_get_available_shippings}
{assign var="vedortotalshipping" value=$vedorshippinglist|@count}

{if $vedortotalshipping > 0}
{assign var="startvalue" value=$startvalue+1}
{/if}
<!-- CHECK SHIPPING -->

<!-- CHECK VIDEO CALL SETUP -->
{*{assign var="videocall" value="1"}
{if $videocall > 0}
{assign var="startvalue" value=$startvalue+1}
{/if}*}
<!-- CHECK VIDEO CALL SETUP -->

{assign var="endvalue" value="4"}

<!-- CHECK FOR GO LIVE BUTTON ACTIVE -->
{assign var="golivebtn" value=$endvalue-$startvalue}
<!-- CHECK FOR GO LIVE BUTTON ACTIVE -->

{if $addons.stripe_connect.status != 'A'}
{assign var="endvalue" value=$endvalue-1}
{/if}
  <div class="vendorheadingtext"><!--<span class="cs-icon icon-arrow-left" style="display:inline;float:left;"></span>-->
  <span style="display:inline;">{__("vendorinformation_setupstore_heading")}</span>
  </div>  
  <div class="vendortext">{*{__("vendorinformation_setupstore_subheading", ["[s]" => $startvalue,"[e]" => $endvalue])}*}{__("vendorinformation_setupstore_subheading_text")}</div>
  <div class="vendortypeblock">
  <div class="radio-group">
		  <div class="vendorcontent {if $updateprofile > 0}selectedrow{/if}">
			   <a href="{"companies.update&company_id={$companyid}"|fn_url}" class="linkdata"><div class="vendorupdateprofileheading">Update Store Profile</div>
			   <div class="vendorupdateprofiletext">{__("vendorinfo_setupstore_updatestoreprofile_text")} {if $updateprofile > 0} <span class="checkproduct"></span> {else}<span class="navarrow navright"></span> {/if}</div></a>
		  </div>
	  <div id="cleared">&nbsp;</div>
	         <a href="{"products.addtype"|fn_url}" class="linkdata"><div class="vendorcontent {if $totalactiveproduct > 0}selectedrow{/if}">
			   <div class="vendorupdateprofileheading">Add Products</div>
			   <div class="vendorupdateprofiletext">{__("vendorinfo_setupstore_addproudct_text")} {if $totalactiveproduct > 0} <span class="checkproduct"></span> {else}<span class="navarrow navright"></span> {/if}</div></a>
		  </div> 
		  {if $addons.stripe_connect.status == 'A'}
	  <div id="cleared">&nbsp;</div>
				{if $connect_url =='1'}
						{if $stripe_express_connect_url || $stripe_express_continue_registration_url}
						{if $stripe_express_continue_registration_url}
						<a href="{$stripe_express_continue_registration_url}" class="linkdata">							
						{elseif $stripe_express_connect_url}
						<a href="{$stripe_express_connect_url}" class="linkdata">							
						{/if}
						{if $stripe_standard_connect_url}
						<a href="{$stripe_standard_connect_url}" class="linkdata">							
						{/if}
					{elseif $stripe_standard_connect_url}
					<a href="{$stripe_standard_connect_url}" class="linkdata">
					{/if}
				{else}
				<a href="{"companies.update&company_id={$companyid}"|fn_url}" class="linkdata">
				{/if}
	           <div class="vendorcontent {if $stripeconnect > 0}selectedrow{/if}">
			   <div class="vendorupdateprofileheading">Connect Stripe</div>
			   <div class="vendorupdateprofiletext">{__("vendorinfo_setupstore_stripeconnect_text")} {if $stripeconnect > 0} <span class="checkproduct"></span> {else}<span class="navarrow navright"></span> {/if}</div></a>
		  </div>
		  {/if}
	  <div id="cleared">&nbsp;</div>
	         <a href="{"shippings.manage"|fn_url}" class="linkdata"><div class="vendorcontent {if $vedortotalshipping > 0}selectedrow{/if}">
			   <div class="vendorupdateprofileheading">Select Shipping Method</div>
			   <div class="vendorupdateprofiletext">{__("vendorinfo_setupstore_shipping_text")} {if $vedortotalshipping > 0} <span class="checkproduct"></span> {else}<span class="navarrow navright"></span> {/if}</div></a>
		  </div>
           <!--<div id="cleared">&nbsp;</div>
	         <div class="vendorcontent {if $videocall > 0}selectedrow{/if}">
			   <div class="vendorupdateprofileheading">Set up video Calls</div>
			   <div class="vendorupdateprofiletext">{__("vendorinfo_setupstore_videocall_text")} {if $videocall > 0} <span class="checkproduct"></span> {else}<span class="navarrow navright"></span> {/if}</div>
		  </div>-->	  
   </div>	  
	  <div> <span id="spnError" class="vendorerror" style="display:none">{__("vendorinfo_setupstore_error_msg")}</span></div>
	  <div id="cleared">&nbsp;</div>
	  <div>
	  {if $runtime.company_data.company_golive =='1'}
	  <span class="golivesuccess">Note : Go Live Request status is <strong>Pending</strong>. Request Date {$runtime.company_data.company_golive_date|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</span>
	  {elseif $runtime.company_data.company_golive =='2'}
	  <span class="golivefailed">Note : Go Live Request status is <strong>Disapprove</strong>. Reason : - {$runtime.company_data.disapproved_resason}</span>
	  {/if}
	  </div>
	  <div id="cleared">&nbsp;</div>
	  {if $golivebtn =='0'}
	  <div class="vendorboxnext"><p><button class="ty-btn__primary" type="submit" name="dispatch[joinvivanvendor.requestsave]"><span>{__("vendorinfo_setupstore_btn")}</span></button></p></div>
	  {else}
	  <div class="vendorboxnextpending"><p>{__("vendorinfo_setupstore_btn")}</p></div>
	  {/if}
  <div>
 
</div>
</div>
</form>
<style>
.ty-btn__primary
{
    width: 100%;     
    background: #73FE8B;
      border: 0px;
  font-size: 24px;
}
    
}
.vendorupdateprofileheading
{
font-size:18px;
text-align:left;
}
.linkdata{  
  color:#333;
}
.vendorupdateprofiletext
{
font-size:14px;
color:#828282;
text-align:left;
}
.vendorheadingtext
{
  width:100%;
 font-size: 25px !important;
 font-weight: bold;
  float:left; 
  margin-top:10px;
  margin-bottom:10px;
}
.vendorheadingtext .cs-icon
{
font-size:25px !important;
}
.vendor-manage
{
  text-align:center;
}
.mainvendorblock
{
  width:45%;
 display: inline-block;
}
.mainvendorblock h5
{
  font-size:20px;
}
.vendortext
{
  font-size:16px;
  text-align:center;
}
.vendortypeblock
{
 margin-top:20px;
}
.vendorcontent {
  float: left;
  width: 100%;  
  border-radius: 25px;
  padding: 20px; 
  border: 2px solid #ccc;
  font-size:16px;
  font-weight:bold;
  cursor: pointer;  
  text-align:left;
}
.selectedrow{
    border: 2px solid #73FE8B;
    background-color: #E1FFE4;
}
#cleared {
  clear: both;
}
.vendorerror
{
 color:red;
 font-size:16px;
}
.vendorboxnextpending {
    width: 100%; 
    padding: 15px;
    background: #eee9e9;
    border-radius: 5px;
    position: relative;
    cursor:pointer;   
}
.vendorboxnextpending p {
    margin: 0;
    color: #000;
    font-size:20px;    
}
.vendorboxnextpending::after {
    position: absolute;
    content: "";
    border-style: solid;
    border-width: 20px;
    border-color: transparent transparent transparent #eee9e9;
    left: 100%;
    top: 50%;
    margin-top: -20px;
}
.vendorboxnext {
    width: 100%; 
    padding: 15px;
    background: #73FE8B;
    border-radius: 5px;
    position: relative;
    cursor:pointer;
}
.vendorboxnext p {
    margin: 0;
    color: #000;
    font-size:20px;
}
.vendorboxnext::after {
    position: absolute;
    content: "";
    border-style: solid;
    border-width: 20px;
    border-color: transparent transparent transparent #73FE8B;
    left: 100%;
    top: 50%;
    margin-top: -20px;
}
.arrow-left {
  width: 0; 
  height: 0; 
  border-top: 60px solid transparent;
  border-bottom: 60px solid transparent; 
  border-right: 60px solid blue; 
}
.navarrow {
  border: solid black;
  border-width: 0 3px 3px 0;
  display: inline-block;
  padding: 8px;
  float: right;
}
.navright {
  transform: rotate(-45deg);
  -webkit-transform: rotate(-45deg);
}
.golivesuccess
{
 color:green;
 font-size:18px;
}
.golivefailed
{
  color:red;
  font-size:18px;
}
.checkproduct {
  display: inline-block;
  transform: rotate(45deg);
  height: var(--height);
  width: var(--width);
  border-bottom: var(--borderWidth) solid var(--borderColor);
  border-right: var(--borderWidth) solid var(--borderColor);
   --borderWidth: 5px;
  --height: 27px;
  --width: 14px;
  --borderColor: #78b13f;
  float: right;
}
a, a:hover {  
  text-decoration: none;
}
.actions__wrapper
{
 display:none;
}
</style>

{else}
{/if}