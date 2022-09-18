{capture name="mainbox"}
<div class="product-manage" id="content_detailed"> 
<div class="mainproductblock">
  <!--<div class="productheadingtext"><span class="cs-icon icon-arrow-left" style="display:inline;float:left;"></span>
  <span style="display:inline;">{__("vendorinformation_add_products")}</span>
  </div> --> 
  <div class="producttext">{__("vendorinformation_add_products_text")}</div>
  <div class="producttypeblock">
  <div class="radio-group">
	  <div class="productcontent radio productshopify" data-value="{"wk_shopify.add"|fn_url}"><span>{include file="addons/vendorinformation/views/products/components/shopify_icon.tpl"}</span></div>
	  <div  class="productsidebar radio productcommerce" data-value="{"wk_woocommerce.add"|fn_url}"><span>{include file="addons/vendorinformation/views/products/components/woo_icon.tpl"}</span></div>	 
	  <div id="cleared">&nbsp;</div>
	  <div  class="productcontent radio productcsv" data-value="{"import_presets.add&object_type=products"|fn_url}"><span>{include file="addons/vendorinformation/views/products/components/csv_icon.tpl"}</span></div>
	  <div class="productsidebar radio productmanually" data-value="{"products.manage"|fn_url}"><span>Add Manually</div>
	  <input type="hidden" id="producttype" name="producttype" />
	  </div>
	  <div id="cleared">&nbsp;</div>	  
	  <div class="productboxnext"><p>{__("vendorinformation_add_products_btn")}</p></div>
	  <div> <span id="spnError" class="producterror" style="display:none">{__("vendorinformation_add_products_choose_msg")}</span></div>
  <div>
</div>
</div>
{/capture}
{$title = __("vendorinformation_add_products")}
{include file="common/mainbox.tpl" title=$title  
content=$smarty.capture.mainbox  
select_languages=(bool) $id 
buttons=""
adv_buttons=""}

<style>
.productheadingtext
{
  width:100%;
 font-size: 25px !important;
 font-weight: bold;
  float:left; 
   margin-top:40px;
  margin-bottom:40px;
}
.productheadingtext .cs-icon
{
font-size:25px !important;
}
.product-manage
{
  text-align:center;
}
.mainproductblock
{
  width:50%;
 display: inline-block;
}
.mainproductblock h5
{
  font-size:20px;
}
.producttext
{
  font-size:16px;
  text-align:left;
}
.producttypeblock
{
margin-top:50px;
}
.productcontent {
  float: left;
  width: 45%;
  #background-color: #CCF;
  line-height:50px;
  border-radius: 25px;
  padding: 20px; 
  border: 2px solid #ccc;
  font-size:16px;
  font-weight:bold;
  cursor: pointer;
}
.productsidebar {
  border-radius: 25px;
  float: right;
  width: 45%; 
  #background-color: #FFA;
  line-height:50px;
  border-radius: 25px;
  padding: 20px;
  border: 2px solid #ccc;
  font-size:16px;
  font-weight:bold;
  cursor: pointer;
}
.radio.selected{
    border: 2px solid #73FE8B;
    background-color: #CEFFD3;
}
#cleared {
  clear: both;
}
.producterror
{
 color:red;
 font-size:16px;
}
.productboxnext {
    width: 100%; 
    padding: 15px;
    background: #73FE8B;
    border-radius: 5px;
    position: relative;
    cursor:pointer;
}
.productboxnext p {
    margin: 0;
    color: #000;
    font-size:20px;
}
.productboxnext::after {
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
</style>
{literal}
<script>
$('.radio-group .radio').click(function(){
    $(this).parent().find('.radio').removeClass('selected');
    $(this).addClass('selected');
    var val = $(this).attr('data-value');
    //alert(val);
    $(this).parent().find('input').val(val);
});

$(".productboxnext").click(function() {
	var chooseval = $('#producttype').val();
       if (chooseval == '') 
       {
        // alert('please...');
	 $("#spnError")[0].style.display = "block";
         return false; 	 
	}
        else 
	{	
	 //alert(chooseval);
         $("#spnError")[0].style.display = "none";
	 location.href = chooseval;
        }
       return false;
    }); 
</script>
{/literal}