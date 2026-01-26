<div class="row">
    <div class="col-lg-8 col-md-10">
        {if $update_result == 'success'}
            <div class="alert alert--center alert--icon alert--success">
                <div class="alert__content">
                    <div class="alert__title">{$btr->okaycms__eleads_update__success|escape}</div>
                </div>
            </div>
        {elseif $update_result == 'error'}
            <div class="alert alert--center alert--icon alert--error">
                <div class="alert__content">
                    <div class="alert__title">{$btr->okaycms__eleads_update__failed|escape}</div>
                    {if $update_message}
                        <div class="text_muted small mt-05">{$update_message|escape}</div>
                    {/if}
                </div>
            </div>
        {/if}

        <div class="boxed mt-1">
            <div class="mb-1">
                <strong>{$btr->okaycms__eleads_update__current|escape}</strong>
                <div>{$update_info.local_version|escape}</div>
            </div>
            <div class="mb-1">
                <strong>{$btr->okaycms__eleads_update__latest|escape}</strong>
                <div>
                    {if $update_info.latest_version}
                        {$update_info.latest_version|escape}
                        {if $update_info.html_url}
                            <a href="{$update_info.html_url|escape}" target="_blank" rel="noopener">{$btr->okaycms__eleads_update__view|escape}</a>
                        {/if}
                    {else}
                        {$btr->okaycms__eleads_update__unknown|escape}
                    {/if}
                </div>
            </div>

            {if $update_info.error}
                <div class="alert alert--center alert--icon alert--error">
                    <div class="alert__content">
                        <div class="alert__title">{$update_info.error|escape}</div>
                    </div>
                </div>
            {/if}

            {if $update_info.update_available}
                <button type="submit" class="btn btn_small btn_blue" formaction="{$update_action_url|escape}" formmethod="post">
                    <span>{$btr->okaycms__eleads_update__button|escape}</span>
                </button>
            {else}
                <div class="text_muted small">{$btr->okaycms__eleads_update__up_to_date|escape}</div>
            {/if}
        </div>
    </div>
</div>
