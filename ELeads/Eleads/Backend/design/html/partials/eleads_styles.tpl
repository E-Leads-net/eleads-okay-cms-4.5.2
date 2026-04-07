<style>
    .eleads_scrollbox {
        max-height: 260px;
        overflow-y: auto;
        padding: 10px;
    }
    .eleads_section {
        max-width: 80%;
        margin-left: auto;
        margin-right: auto;
    }
    .eleads_divider {
        height: 1px;
        background: linear-gradient(90deg, rgba(0,0,0,0.08), rgba(0,0,0,0.22), rgba(0,0,0,0.08));
        margin: 16px auto 0;
        max-width: 80%;
        border-radius: 2px;
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
    .eleads_feed_url_row .heading_label {
        margin-bottom: 2px;
    }
    .eleads_feed_url_label {
        padding-right: 6px;
    }
    .eleads_feed_url_input {
        padding-left: 6px;
    }
    .eleads_feed_actions {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .eleads_feed_url_value {
        flex: 1 1 auto;
        min-width: 0;
        padding: 9px 14px;
        border: 1px solid #ccd4d9;
        border-radius: 4px;
        background: #fff;
        color: #2f3a46;
        line-height: 1.3;
        word-break: break-all;
    }
    .eleads_feed_actions .eleads_feed_url_value {
        display: none;
    }
    .eleads_sync_active .eleads_feed_url_value {
        border-color: #3c763d;
        background: #eef8f1;
        box-shadow: inset 0 0 0 1px rgba(60,118,61,0.15);
    }
    .eleads_feed_url_row {
        margin-bottom: 16px;
    }
    .eleads_feed_inline_actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }
    .eleads_feed_btn,
    .fn_eleads_feed_generate {
        height: 36px;
        padding: 0 16px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 13px;
        text-decoration: none;
        box-shadow: none;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease, opacity .18s ease;
    }
    .fn_eleads_feed_generate {
        min-width: 180px;
    }
    .eleads_feed_btn {
        min-width: 132px;
    }
    .fn_eleads_feed_generate.btn,
    .fn_eleads_feed_generate.btn:hover,
    .fn_eleads_feed_generate.btn:focus {
        background: #5aa2f2;
        border: 1px solid #5aa2f2;
        color: #fff;
    }
    .eleads_feed_btn.btn,
    .eleads_feed_btn.btn:hover,
    .eleads_feed_btn.btn:focus {
        background: #fff;
        border: 1px solid #cfd8e3;
        color: #344054;
    }
    .fn_eleads_feed_download.btn,
    .fn_eleads_feed_download.btn:hover,
    .fn_eleads_feed_download.btn:focus {
        background: #e9f7ef;
        border: 1px solid #9bd3ac;
        color: #216a3d;
    }
    .fn_eleads_feed_download.is-disabled,
    .fn_eleads_feed_download.is-disabled:hover,
    .fn_eleads_feed_download.is-disabled:focus {
        background: #f3f4f6;
        border-color: #e1e5ea;
        color: #98a2b3;
        pointer-events: none;
        cursor: default;
    }
    .eleads_feed_status_bar {
        display: inline-flex;
        align-items: center;
        margin-top: 0;
        margin-right: 0;
        flex-wrap: nowrap;
        min-height: 34px;
    }
    .eleads_feed_status_badge {
        display: inline-flex;
        align-items: center;
        min-height: 36px;
        padding: 6px 12px;
        border-radius: 999px;
        background: #eef1f4;
        color: #45515f;
        font-size: 12px;
        line-height: 1.2;
        white-space: nowrap;
    }
    .eleads_feed_status_badge.is-ready {
        background: #e8f6ed;
        color: #257942;
    }
    .eleads_feed_status_badge.is-running {
        background: #eef5ff;
        color: #2462b3;
    }
    .eleads_feed_status_badge.is-failed {
        background: #fdeeee;
        color: #b33a3a;
    }
    .eleads_feed_status_badge.is-idle {
        background: #f2f2f2;
        color: #666;
    }
</style>
