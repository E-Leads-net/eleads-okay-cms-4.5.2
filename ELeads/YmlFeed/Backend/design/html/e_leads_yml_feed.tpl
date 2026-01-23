{$meta_title = $btr->okaycms__eleads_yml_feed__title|escape scope=global}

{* Page title *}
<div class="row">
    <div class="col-lg-12 col-md-12">
        <div class="wrap_heading">
            <div class="box_heading heading_page">
                {$btr->okaycms__eleads_yml_feed__title|escape}
            </div>
        </div>
    </div>
</div>

{* Success message *}
{if $message_success}
<div class="row">
    <div class="col-lg-12 col-md-12 col-sm-12">
        <div class="alert alert--center alert--icon alert--success">
            <div class="alert__content">
                <div class="alert__title">
                    {if $message_success == 'saved'}
                        {$btr->general_settings_saved|escape}
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>
{/if}

<form method="post" class="fn_fast_button">
    <input type="hidden" name="session_id" value="{$smarty.session.id}">

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="boxed fn_toggle_wrap">
                <div class="heading_box">
                    {$btr->okaycms__eleads_yml_feed__params|escape}
                    <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                        <a class="btn-minimize" href="javascript:;"><i class="fa fn_icon_arrow fa-angle-down"></i></a>
                    </div>
                </div>
                <div class="toggle_body_wrap on fn_card">
                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="heading_label">
                                <strong>{$btr->okaycms__eleads_yml_feed__feed_urls|escape}</strong>
                            </div>
                            {foreach $languages as $language}
                                <div class="row mb-1">
                                    <div class="col-lg-3 col-md-4">
                                        <label class="heading_label">
                                            {$language->name|escape} ({$language->label|escape})
                                        </label>
                                    </div>
                                    <div class="col-lg-9 col-md-8">
                                        <input class="form-control" type="text" value="{$feed_urls[$language->id]|escape}" readonly>
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
                            <div class="boxed eleads_scrollbox">
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
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="heading_label">
                                <strong>{$btr->okaycms__eleads_yml_feed__filter_features|escape}</strong>
                            </div>
                            <div class="boxed eleads_scrollbox">
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
                            <div class="mt-1">
                                <a href="#" class="fn_select_all" data-target=".fn_eleads_feature">{$btr->okaycms__eleads_yml_feed__select_all|escape}</a>
                                <span>|</span>
                                <a href="#" class="fn_select_none" data-target=".fn_eleads_feature">{$btr->okaycms__eleads_yml_feed__select_none|escape}</a>
                            </div>
                            <div class="text_muted small mt-1">
                                {$btr->okaycms__eleads_yml_feed__filter_features_hint|escape}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="heading_label">
                                <strong>{$btr->okaycms__eleads_yml_feed__filter_options|escape}</strong>
                            </div>
                            <div class="boxed eleads_scrollbox">
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
                            <div class="mt-1">
                                <a href="#" class="fn_select_all" data-target=".fn_eleads_option">{$btr->okaycms__eleads_yml_feed__select_all|escape}</a>
                                <span>|</span>
                                <a href="#" class="fn_select_none" data-target=".fn_eleads_option">{$btr->okaycms__eleads_yml_feed__select_none|escape}</a>
                            </div>
                            <div class="text_muted small mt-1">
                                {$btr->okaycms__eleads_yml_feed__filter_options_hint|escape}
                            </div>
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

                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <button type="submit" class="btn btn_small btn_blue float-md-right">
                                <span>{$btr->general_apply|escape}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

{literal}
<style>
    .eleads_scrollbox {
        max-height: 260px;
        overflow-y: auto;
        padding: 10px;
    }
    .eleads_tree,
    .eleads_list {
        list-style: none;
        margin: 0;
        padding-left: 0;
    }
    .eleads_tree_level_1 { padding-left: 18px; }
    .eleads_tree_level_2 { padding-left: 18px; }
    .eleads_tree_level_3 { padding-left: 18px; }
    .eleads_tree_level_4 { padding-left: 18px; }
    .eleads_tree_item { margin: 2px 0; }
    .eleads_tree_label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        font-weight: normal;
    }
</style>
<script>
    $(function() {
        $(document).on('click', '.fn_select_all', function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            $(target).prop('checked', true).trigger('change');
        });
        $(document).on('click', '.fn_select_none', function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            $(target).prop('checked', false).trigger('change');
        });

        function updateParents(categoryId, map, inputs) {
            var parentId = map.parents[categoryId];
            if (!parentId) {
                return;
            }
            var children = map.children[parentId] || [];
            var allChecked = true;
            var anyChecked = false;
            children.forEach(function(childId) {
                var child = inputs[childId];
                if (!child) {
                    return;
                }
                if (child.checked) {
                    anyChecked = true;
                } else {
                    allChecked = false;
                }
            });
            var parentInput = inputs[parentId];
            if (parentInput) {
                parentInput.checked = allChecked;
                parentInput.indeterminate = anyChecked && !allChecked;
            }
            updateParents(parentId, map, inputs);
        }

        var categoryInputs = {};
        var categoryMap = { parents: {}, children: {} };
        $('.fn_eleads_category').each(function() {
            var id = $(this).data('category-id');
            var parentId = $(this).data('parent-id');
            categoryInputs[id] = this;
            categoryMap.parents[id] = parentId;
            if (!categoryMap.children[parentId]) {
                categoryMap.children[parentId] = [];
            }
            categoryMap.children[parentId].push(id);
        });

        $(document).on('change', '.fn_eleads_category', function() {
            var isChecked = this.checked;
            var id = $(this).data('category-id');
            var children = categoryMap.children[id] || [];
            children.forEach(function(childId) {
                var child = categoryInputs[childId];
                if (child) {
                    child.checked = isChecked;
                    child.indeterminate = false;
                }
            });
            updateParents(id, categoryMap, categoryInputs);
        });

        $('.fn_eleads_category').each(function() {
            updateParents($(this).data('category-id'), categoryMap, categoryInputs);
        });
    });
</script>
{/literal}
