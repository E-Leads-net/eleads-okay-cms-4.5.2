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
    });
</script>
{/literal}
