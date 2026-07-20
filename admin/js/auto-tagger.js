(function ($) {
    'use strict';

    function escapeHtml(value) {
        return $('<div>').text(value).html();
    }

    function displayTagsPreview(tags) {
        var html = '';

        if (tags.taxonomies) {
            $.each(tags.taxonomies, function (taxonomy, terms) {
                if (Array.isArray(terms) && terms.length) {
                    html += '<div><strong>' + escapeHtml(taxonomy) + ':</strong> ' + escapeHtml(terms.join(', ')) + '</div>';
                }
            });
        }

        $.each(['tags', 'categories', 'audience'], function (_, key) {
            if (Array.isArray(tags[key]) && tags[key].length) {
                html += '<div><strong>' + escapeHtml(key) + ':</strong> ' + escapeHtml(tags[key].join(', ')) + '</div>';
            }
        });

        $('#at_ai_tags_content').html(html);
    }

    $(function () {
        var generatedTags = at_ai_tagger.initial_tags || {};

        $('#at_ai_generate_tags_btn').on('click', function () {
            var button = $(this);
            button.prop('disabled', true).text(at_ai_tagger.strings.generating);

            $.post(at_ai_tagger.ajax_url, {
                action: 'at_ai_generate_tags',
                nonce: at_ai_tagger.nonce,
                object_id: at_ai_tagger.post_id,
                object_type: at_ai_tagger.post_type
            }).done(function (response) {
                if (response.success) {
                    generatedTags = response.data.tags || {};
                    displayTagsPreview(generatedTags);
                    $('#at_ai_tags_preview').show();
                    return;
                }
                window.alert(response.data || at_ai_tagger.strings.error);
            }).fail(function () {
                window.alert(at_ai_tagger.strings.error);
            }).always(function () {
                button.prop('disabled', false).text(at_ai_tagger.strings.generate);
            });
        });

        $('#at_ai_apply_tags_btn').on('click', function () {
            if (!Object.keys(generatedTags).length) {
                window.alert(at_ai_tagger.strings.generate_first);
                return;
            }

            var button = $(this);
            button.prop('disabled', true).text(at_ai_tagger.strings.applying);

            $.post(at_ai_tagger.ajax_url, {
                action: 'at_ai_apply_tags',
                nonce: at_ai_tagger.apply_nonce,
                object_id: at_ai_tagger.post_id,
                tags: generatedTags
            }).done(function (response) {
                if (response.success) {
                    window.alert(at_ai_tagger.strings.applied);
                    window.location.reload();
                    return;
                }
                window.alert(response.data || at_ai_tagger.strings.error);
            }).fail(function () {
                window.alert(at_ai_tagger.strings.error);
            }).always(function () {
                button.prop('disabled', false).text(at_ai_tagger.strings.apply);
            });
        });
    });
}(jQuery));
