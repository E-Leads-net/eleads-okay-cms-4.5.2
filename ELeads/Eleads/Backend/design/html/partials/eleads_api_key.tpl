<div class="row">
    <div class="col-lg-8 col-md-10">
        <div class="form-group">
            <label class="heading_label">
                <span>{$btr->okaycms__eleads_api_key__label|escape}</span>
            </label>
            <input class="form-control" type="text" name="eleads__api_key" value="{$api_key_value|escape}">
        </div>
        <button type="submit" class="btn btn_small btn_blue" name="eleads__api_key_submit" value="1">
            <span>{$btr->okaycms__eleads_api_key__save|escape}</span>
        </button>
        <div class="text_muted small mt-1">{$btr->okaycms__eleads_api_key__hint|escape}</div>
        {if $api_key_error}
            <div class="alert alert--center alert--icon alert--error mt-1">
                <div class="alert__content">
                    <div class="alert__title">{$btr->okaycms__eleads_api_key__invalid|escape}</div>
                </div>
            </div>
        {/if}
    </div>
</div>
