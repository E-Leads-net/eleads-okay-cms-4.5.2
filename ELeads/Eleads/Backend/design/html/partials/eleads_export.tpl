<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="heading_label">
            <strong>{$btr->okaycms__eleads_yml_feed__feed_urls|escape}</strong>
        </div>
        {foreach $languages as $language}
            <div class="row mb-1 eleads_feed_url_row">
                <div class="col-lg-2 col-md-3 eleads_feed_url_label">
                    <label class="heading_label">
                        {$language->name|escape} ({$language->label|escape})
                    </label>
                </div>
                <div class="col-lg-10 col-md-9 eleads_feed_url_input">
                    <div class="input-group eleads_feed_actions">
                        <input class="form-control" type="text" value="{$feed_urls[$language->id]|escape}" readonly>
                        <div class="input-group-append">
                            <a href="#" class="btn btn_small btn-info fn_eleads_action fn_clipboard fn_eleads_copy_url hint-bottom-middle-t-info-s-small-mobile" data-copy-string="{$feed_urls[$language->id]|escape}" data-hint="Click to copy" data-hint-copied="✔ Copied to clipboard">
                                <i class="fa fa-copy"></i>
                            </a>
                            <a href="{$feed_urls[$language->id]|escape}" class="btn btn_small btn-info fn_eleads_action" download="eleads-feed-{$language->label|escape}.xml">
                                <i class="fa fa-download"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="heading_label">
            <strong>{$btr->okaycms__eleads_yml_feed__categories|escape}</strong>
        </div>
        <div class="boxed eleads_scrollbox eleads_section">
            {function name=category_checkboxes categories=[] level=0 parent_id=0}
                <ul class="eleads_tree eleads_tree_level_{$level}">
                    {foreach $categories as $category}
                        <li class="eleads_tree_item">
                            <label class="eleads_tree_label">
                                <input class="fn_eleads_category" type="checkbox" name="eleads__yml_feed__categories[]" value="{$category->id}" data-category-id="{$category->id}" data-parent-id="{$parent_id}" {if in_array($category->id, $selected_categories)}checked{/if}>
                                <span>{$category->name|escape}</span>
                            </label>
                            {if !empty($category->subcategories)}
                                {category_checkboxes categories=$category->subcategories level=$level+1 parent_id=$category->id}
                            {/if}
                        </li>
                    {/foreach}
                </ul>
            {/function}
            {category_checkboxes categories=$categories}
        </div>
        <div class="mt-1">
            <a href="#" class="fn_select_all" data-target=".fn_eleads_category">{$btr->okaycms__eleads_yml_feed__select_all|escape}</a>
            <span>|</span>
            <a href="#" class="fn_select_none" data-target=".fn_eleads_category">{$btr->okaycms__eleads_yml_feed__select_none|escape}</a>
        </div>
        <div class="text_muted small mt-1">
            {$btr->okaycms__eleads_yml_feed__categories_hint|escape}
        </div>
        <div class="eleads_divider"></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="heading_label">
            <strong>{$btr->okaycms__eleads_yml_feed__filter_features|escape}</strong>
        </div>
        <div class="mb-1">
            <div class="okay_switch clearfix">
                <label class="switch_label">{$btr->okaycms__eleads_yml_feed__enable_filter_features|escape}</label>
                <label class="switch switch-default">
                    <input class="switch-input fn_eleads_toggle_section" type="checkbox" name="eleads__yml_feed__filter_features_enabled" value="1" {if $selected_features|count}checked{/if}>
                    <span class="switch-label"></span>
                    <span class="switch-handle"></span>
                </label>
            </div>
        </div>
        <div class="boxed eleads_scrollbox eleads_section fn_eleads_features_section">
            <ul class="eleads_list">
                {foreach $features as $feature}
                    <li>
                        <label class="eleads_tree_label">
                            <input class="fn_eleads_feature" type="checkbox" name="eleads__yml_feed__filter_features[]" value="{$feature->id}" {if in_array($feature->id, $selected_features)}checked{/if}>
                            <span>{$feature->name|escape}</span>
                        </label>
                    </li>
                {/foreach}
            </ul>
        </div>
        <div class="mt-1 fn_eleads_features_section">
            <a href="#" class="fn_select_all" data-target=".fn_eleads_feature">{$btr->okaycms__eleads_yml_feed__select_all|escape}</a>
            <span>|</span>
            <a href="#" class="fn_select_none" data-target=".fn_eleads_feature">{$btr->okaycms__eleads_yml_feed__select_none|escape}</a>
        </div>
        <div class="text_muted small mt-1 fn_eleads_features_section">
            {$btr->okaycms__eleads_yml_feed__filter_features_hint|escape}
        </div>
        <div class="eleads_divider"></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="heading_label">
            <strong>{$btr->okaycms__eleads_yml_feed__filter_options|escape}</strong>
        </div>
        <div class="mb-1">
            <div class="okay_switch clearfix">
                <label class="switch_label">{$btr->okaycms__eleads_yml_feed__enable_filter_options|escape}</label>
                <label class="switch switch-default">
                    <input class="switch-input fn_eleads_toggle_section" type="checkbox" name="eleads__yml_feed__filter_options_enabled" value="1" {if $selected_feature_values|count}checked{/if}>
                    <span class="switch-label"></span>
                    <span class="switch-handle"></span>
                </label>
            </div>
        </div>
        <div class="boxed eleads_scrollbox eleads_section fn_eleads_options_section">
            <ul class="eleads_list">
                {foreach $feature_values as $feature_value}
                    <li>
                        <label class="eleads_tree_label">
                            <input class="fn_eleads_option" type="checkbox" name="eleads__yml_feed__filter_options[]" value="{$feature_value->id}" {if in_array($feature_value->id, $selected_feature_values)}checked{/if}>
                            <span>{$feature_value->value|escape}</span>
                        </label>
                    </li>
                {/foreach}
            </ul>
        </div>
        <div class="mt-1 fn_eleads_options_section">
            <a href="#" class="fn_select_all" data-target=".fn_eleads_option">{$btr->okaycms__eleads_yml_feed__select_all|escape}</a>
            <span>|</span>
            <a href="#" class="fn_select_none" data-target=".fn_eleads_option">{$btr->okaycms__eleads_yml_feed__select_none|escape}</a>
        </div>
        <div class="text_muted small mt-1 fn_eleads_options_section">
            {$btr->okaycms__eleads_yml_feed__filter_options_hint|escape}
        </div>
        <div class="eleads_divider"></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="heading_label">
            <strong>{$btr->okaycms__eleads_yml_feed__grouped_title|escape}</strong>
        </div>
        <div class="okay_switch clearfix">
            <label class="switch_label">{$btr->okaycms__eleads_yml_feed__grouped_label|escape}</label>
            <label class="switch switch-default">
                <input class="switch-input" type="checkbox" name="eleads__yml_feed__grouped" value="1" {if $grouped_products}checked{/if}>
                <span class="switch-label"></span>
                <span class="switch-handle"></span>
            </label>
        </div>
        <div class="eleads_divider"></div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-6">
        <div class="form-group">
            <div class="heading_label">
                <span>{$btr->okaycms__eleads_yml_feed__access_key|escape}</span>
            </div>
            <input class="form-control" type="text" name="eleads__yml_feed__access_key" value="{$settings->eleads__yml_feed__access_key|escape}">
            <div class="text_muted small mt-1">
                {$btr->okaycms__eleads_yml_feed__access_key_hint|escape}
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-6">
        <div class="form-group">
            <div class="heading_label">
                <span>{$btr->okaycms__eleads_yml_feed__shop_name|escape}</span>
            </div>
            <input class="form-control" type="text" name="eleads__yml_feed__shop_name" value="{if $settings->eleads__yml_feed__shop_name}{$settings->eleads__yml_feed__shop_name|escape}{else}{$default_shop_name|escape}{/if}">
        </div>
    </div>
    <div class="col-lg-6 col-md-6">
        <div class="form-group">
            <div class="heading_label">
                <span>{$btr->okaycms__eleads_yml_feed__email|escape}</span>
            </div>
            <input class="form-control" type="email" name="eleads__yml_feed__email" value="{if $settings->eleads__yml_feed__email}{$settings->eleads__yml_feed__email|escape}{else}{$default_email|escape}{/if}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-6">
        <div class="form-group">
            <div class="heading_label">
                <span>{$btr->okaycms__eleads_yml_feed__shop_url|escape}</span>
            </div>
            <input class="form-control" type="text" name="eleads__yml_feed__shop_url" value="{$settings->eleads__yml_feed__shop_url|escape}">
        </div>
    </div>
    <div class="col-lg-3 col-md-3">
        <div class="form-group">
            <div class="heading_label">
                <span>{$btr->okaycms__eleads_yml_feed__currency|escape}</span>
            </div>
            <input class="form-control" type="text" name="eleads__yml_feed__currency" value="{if $settings->eleads__yml_feed__currency}{$settings->eleads__yml_feed__currency|escape}{else}{$default_currency|escape}{/if}">
        </div>
    </div>
    <div class="col-lg-3 col-md-3">
        <div class="form-group">
            <div class="heading_label">
                <span>{$btr->okaycms__eleads_yml_feed__picture_limit|escape}</span>
            </div>
            <input class="form-control" type="number" min="0" name="eleads__yml_feed__picture_limit" value="{if $settings->eleads__yml_feed__picture_limit !== null && $settings->eleads__yml_feed__picture_limit !== ''}{$settings->eleads__yml_feed__picture_limit|escape}{else}{$default_picture_limit|escape}{/if}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 col-md-6">
        <div class="form-group">
            <div class="heading_label">
                <span>{$btr->okaycms__eleads_yml_feed__short_description_source|escape}</span>
            </div>
            <select class="selectpicker form-control" name="eleads__yml_feed__short_description_source">
                <option value="annotation" {if $settings->eleads__yml_feed__short_description_source == 'annotation'}selected{/if}>{$btr->okaycms__eleads_yml_feed__short_description_annotation|escape}</option>
                <option value="meta_description" {if $settings->eleads__yml_feed__short_description_source == 'meta_description'}selected{/if}>{$btr->okaycms__eleads_yml_feed__short_description_meta|escape}</option>
                <option value="description" {if $settings->eleads__yml_feed__short_description_source == 'description'}selected{/if}>{$btr->okaycms__eleads_yml_feed__short_description_description|escape}</option>
            </select>
        </div>
    </div>
</div>
