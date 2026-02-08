<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="heading_label">
            <strong>{$btr->okaycms__eleads_yml_feed__seo_title|escape}</strong>
        </div>
        <div class="mb-1">
            <div class="input-group eleads_feed_actions">
                <input class="form-control" type="text" value="{$seo_sitemap_url|escape}" readonly>
                <div class="input-group-append">
                    <a href="#" class="btn btn_small btn-info fn_eleads_action fn_clipboard fn_eleads_copy_url hint-bottom-middle-t-info-s-small-mobile" data-copy-string="{$seo_sitemap_url|escape}" data-hint="Click to copy" data-hint-copied="✔ Copied to clipboard">
                        <i class="fa fa-copy"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="okay_switch clearfix">
            <label class="switch_label">{$btr->okaycms__eleads_yml_feed__seo_label|escape}</label>
            <label class="switch switch-default">
                <input class="switch-input" type="checkbox" name="eleads__seo_pages_enabled" value="1" {if $seo_pages_enabled}checked{/if}>
                <span class="switch-label"></span>
                <span class="switch-handle"></span>
            </label>
        </div>
    </div>
</div>
