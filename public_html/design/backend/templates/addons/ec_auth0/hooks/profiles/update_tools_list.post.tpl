{if $user_data.user_type == "V" && $user_data.auth0_user_id}
    <li>{btn type="list" text=__("auth0.sync_information") href="auth_e.sync_individual?auth0_user_id=`$user_data.auth0_user_id`&user_id=`$id`"}</li>
{/if}
