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

{if $api_key_required}
<div class="row">
    <div class="col-lg-8 col-md-10">
        <div class="boxed">
            <div class="heading_box">{$btr->okaycms__eleads_api_key__title|escape}</div>
            <div class="mt-1 text_muted">{$btr->okaycms__eleads_api_key__hint|escape}</div>
            {if $api_key_error}
                <div class="alert alert--center alert--icon alert--error mt-1">
                    <div class="alert__content">
                        <div class="alert__title">{$btr->okaycms__eleads_api_key__invalid|escape}</div>
                    </div>
                </div>
            {/if}
            <form method="post" class="mt-1">
                <input type="hidden" name="session_id" value="{$smarty.session.id}">
                <input type="hidden" name="eleads__api_key_submit" value="1">
                <div class="form-group">
                    <label class="heading_label">
                        <span>{$btr->okaycms__eleads_api_key__label|escape}</span>
                    </label>
                    <input class="form-control" type="text" name="eleads__api_key" value="{$api_key_value|escape}">
                </div>
                <button type="submit" class="btn btn_small btn_blue">
                    <span>{$btr->general_apply|escape}</span>
                </button>
            </form>
        </div>
    </div>
</div>
{else}
<form method="post" class="fn_fast_button">
    <input type="hidden" name="session_id" value="{$smarty.session.id}">

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="boxed fn_toggle_wrap tabs">
                <div class="heading_tabs">
                    <div class="tab_navigation">
                        <a href="#tab_export" class="heading_box tab_navigation_link">{$btr->okaycms__eleads_yml_feed__tab_export|escape}</a>
                        <a href="#tab_api_key" class="heading_box tab_navigation_link">{$btr->okaycms__eleads_yml_feed__tab_api_key|escape}</a>
                        <a href="#tab_update" class="heading_box tab_navigation_link">{$btr->okaycms__eleads_yml_feed__tab_update|escape}</a>
                    </div>
                    <div class="toggle_arrow_wrap fn_toggle_card text-primary">
                        <a class="btn-minimize" href="javascript:;"><i class="fa fn_icon_arrow fa-angle-down"></i></a>
                    </div>
                </div>
                <div class="toggle_body_wrap on fn_card">
                    <div class="tab_container">
                        <div id="tab_export" class="tab">
                            {include file='partials/eleads_export.tpl'}
                        </div>
                        <div id="tab_api_key" class="tab">
                            {include file='partials/eleads_api_key.tpl'}
                        </div>
                        <div id="tab_update" class="tab">
                            {include file='partials/eleads_update.tpl'}
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
{/if}

{include file='partials/eleads_styles.tpl'}
{if !$api_key_required}
    {include file='partials/eleads_scripts.tpl'}
{/if}
