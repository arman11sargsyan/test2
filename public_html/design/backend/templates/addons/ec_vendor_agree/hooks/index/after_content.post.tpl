{if $aggreed_status && $aggreed_status != 'Y'}
    <a id="ec_vendor_agreement" class="cm-dialog-opener cm-dialog-auto-size hidden cm-dialog-non-closable" data-ca-target-id="ec_vendor_agreement_dialog"></a>

    <div class="hidden" title="{__("ec_vendor_agreement_header")}" id="ec_vendor_agreement_dialog">
        <form name="ec_vendor_agreement_form" action="{""|fn_url}" method="post" enctype="multipart/form-data" class="ec_vendor_agree_form">
            <input type="hidden" name="redirect_url" value="{$config.current_url}">
            <div class="ec_logo_top"><img src="{$config.current_location}/images/ec_vendor_agree/restore_logo.svg"></div>
            <div class="ec_welcome_text">{__("ec_vendor_agree.welcome_text")}</div>
            <div class="ec_agreement_subheader">{__("ec_vendor_agree.welcome_subheader")}</div>
            <div class="ec_checkbox_div">
                <input type="hidden" name="agreement" value="N">
                <input type="checkbox" name="agreement" value="Y" id="ec_agree_check">
                <div class="ec_agreement_text">{__("ec_vendor_agree.agreement_text")}</div>
            </div>

            <div class="ec_buttons_container">            
                <button class="ec_btn_onboard" type="submit">{__("ec_vendor_agree.start_onboarding")}</button>
                <input type="hidden" name="dispatch" value="ec_vendor_agree.submit" />
            </div>
            <div class="ec_label">
                <label for="ec_agree_check" class="cm-required">{__("ec_vendor_agree.agree_error_text")}</label>
            </div>
            <div class="ec_logout">
                <a href="{"auth.logout"|fn_url}">{__("ec_vendor_agree.logout")}</a>
            </div>
        </form>
    </div>

    <script type="text/javascript">
    Tygh.$(document).ready(function(){$ldelim}
        Tygh.$('#ec_vendor_agreement').trigger('click');
    });
    </script>
{/if}