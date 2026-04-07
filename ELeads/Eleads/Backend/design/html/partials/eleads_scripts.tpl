{literal}
<script>
    $(function() {
        function toggleSection($checkbox, selector) {
            var isChecked = $checkbox.is(':checked');
            var $section = $(selector);
            $section.toggle(isChecked);
            $section.find('input, select, textarea').prop('disabled', !isChecked);
        }

        $('.fn_eleads_toggle_section').each(function() {
            var $checkbox = $(this);
            if ($checkbox.attr('name') === 'eleads__yml_feed__filter_features_enabled') {
                toggleSection($checkbox, '.fn_eleads_features_section');
            }
            if ($checkbox.attr('name') === 'eleads__yml_feed__filter_options_enabled') {
                toggleSection($checkbox, '.fn_eleads_options_section');
            }
        });

        $(document).on('change', '.fn_eleads_toggle_section', function() {
            var $checkbox = $(this);
            if ($checkbox.attr('name') === 'eleads__yml_feed__filter_features_enabled') {
                toggleSection($checkbox, '.fn_eleads_features_section');
            }
            if ($checkbox.attr('name') === 'eleads__yml_feed__filter_options_enabled') {
                toggleSection($checkbox, '.fn_eleads_options_section');
            }
        });

        function eleadsCopyToClipboard(text) {
            var temp = $('<input>');
            $('body').append(temp);
            temp.val(text).select();
            document.execCommand('copy');
            temp.remove();
        }

        $(document).on('click', '.fn_eleads_copy_url', function(e) {
            e.preventDefault();
            var text = $(this).data('copyString') || '';
            if (text) {
                eleadsCopyToClipboard(text);
            }
        });

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

        function updateSyncHighlight() {
            var enabled = $('input[name="eleads__sync_enabled"]').is(':checked');
            $('.eleads_feed_actions').toggleClass('eleads_sync_active', enabled);
        }

        updateSyncHighlight();
        $(document).on('change', 'input[name="eleads__sync_enabled"]', updateSyncHighlight);

        var eleadsFeedPollers = {};

        function eleadsSetFeedStatus($row, state) {
            var $bar = $row.find('.eleads_feed_status_bar');
            var $status = $row.find('.fn_eleads_feed_status_text');
            var $button = $row.find('.fn_eleads_feed_generate');
            var $download = $row.find('.fn_eleads_feed_download');
            var labels = {
                idle: $bar.data('labelIdle'),
                running: $bar.data('labelRunning'),
                ready: $bar.data('labelReady'),
                failed: $bar.data('labelFailed')
            };
            var status = state && state.status ? state.status : 'idle';
            var text = labels[status] || status;
            var buttonText = $bar.data('labelGenerate');

            if (status === 'running' && typeof state.processed !== 'undefined') {
                text += ': ' + ($bar.data('labelProcessing') || 'Processing') + ' ' + state.processed;
            }
            if (status === 'failed' && state.error) {
                text += ' (' + state.error + ')';
            }
            if (status === 'ready') {
                buttonText = $bar.data('labelRegenerate') || $bar.data('labelGenerate');
            }

            $status
                .text(text)
                .removeClass('is-idle is-running is-ready is-failed')
                .addClass('is-' + status);

            if (status === 'ready') {
                $download.removeClass('is-disabled').attr('aria-disabled', 'false');
            } else {
                $download.addClass('is-disabled').attr('aria-disabled', 'true');
            }

            if (status === 'idle' || status === 'failed' || status === 'ready') {
                $button.text(buttonText).prop('disabled', false).show();
            } else {
                $button.hide();
            }
        }

        $(document).on('click', '.fn_eleads_feed_download.is-disabled', function(e) {
            e.preventDefault();
        });

        function eleadsFetchFeedStatus($row, pollNext) {
            var $bar = $row.find('.eleads_feed_status_bar');
            var lang = $row.data('lang');

            if (!$bar.length) {
                return;
            }
            if ($bar.data('apiValid') !== 1 && $bar.data('apiValid') !== '1') {
                $row.find('.fn_eleads_feed_status_text')
                    .text($bar.data('labelApiRequired'))
                    .removeClass('is-idle is-running is-ready is-failed')
                    .addClass('is-failed');
                return;
            }

            $.ajax({
                url: $bar.data('statusUrl'),
                method: 'GET',
                data: { lang: lang },
                headers: {
                    'Authorization': 'Bearer ' + ($bar.data('apiKey') || ''),
                    'Accept': 'application/json'
                }
            }).done(function(response) {
                eleadsSetFeedStatus($row, response || {});
                if (pollNext && response && response.status === 'running') {
                    eleadsSchedulePoll($row);
                }
            }).fail(function(xhr) {
                var errorText = 'status_error';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorText = xhr.responseJSON.error;
                }
                eleadsSetFeedStatus($row, {
                    status: 'failed',
                    error: errorText
                });
            });
        }

        function eleadsSchedulePoll($row) {
            var lang = $row.data('lang');
            if (eleadsFeedPollers[lang]) {
                clearTimeout(eleadsFeedPollers[lang]);
            }
            eleadsFeedPollers[lang] = setTimeout(function() {
                eleadsFetchFeedStatus($row, true);
            }, 1500);
        }

        $(document).on('click', '.fn_eleads_feed_generate', function(e) {
            e.preventDefault();
            var $button = $(this);
            var $row = $button.closest('[data-eleads-feed-row]');
            var $bar = $row.find('.eleads_feed_status_bar');
            var lang = $row.data('lang');

            if ($button.prop('disabled')) {
                return;
            }

            $button.prop('disabled', true);
            eleadsSetFeedStatus($row, { status: 'running', processed: 0 });

            $.ajax({
                url: $bar.data('generateUrl') + '?lang=' + encodeURIComponent(lang),
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + ($bar.data('apiKey') || ''),
                    'Accept': 'application/json'
                }
            }).done(function(response) {
                var job = response && response.job ? response.job : { status: 'running', processed: 0 };
                eleadsSetFeedStatus($row, job);
                eleadsSchedulePoll($row);
            }).fail(function(xhr) {
                var errorText = 'generation_failed';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorText = xhr.responseJSON.error;
                }
                eleadsSetFeedStatus($row, {
                    status: 'failed',
                    error: errorText
                });
                $button.prop('disabled', false).show();
            });
        });

        $('[data-eleads-feed-row]').each(function() {
            eleadsFetchFeedStatus($(this), false);
        });
    });
</script>
{/literal}
