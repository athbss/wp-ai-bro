/**
 * Media AI Generator - JavaScript for media library AI buttons
 *
 * @since      1.3.0
 * @package    WordPress_AI_Assistant
 */

(function($) {
    'use strict';

    /**
     * Single source of truth for which field gets which AI button, on every
     * media edit surface. `selectors` are safe everywhere (their ids/setting
     * attributes only exist in an attachment context). `classicSelectors` run
     * ONLY on the classic "Edit Media" screen (body.post-type-attachment), so
     * the generic #title id can never be matched on a normal post editor.
     */
    var FIELD_CONFIG = [
        {
            type: 'alt', labelKey: 'label_alt', fallback: 'צור טקסט חלופי AI',
            selectors: ['#attachment-details-two-column-alt-text', '#attachment-details-alt-text'],
            classicSelectors: ['#attachment_alt']
        },
        {
            type: 'title', labelKey: 'label_title', fallback: 'צור כותרת AI',
            selectors: ['#attachment-details-two-column-title', '#attachment-details-title'],
            classicSelectors: ['#title']
        },
        {
            type: 'caption', labelKey: 'label_caption', fallback: 'צור כיתוב AI',
            selectors: ['#attachment-details-two-column-caption', '#attachment-details-caption'],
            classicSelectors: ['#attachment_caption']
        },
        {
            type: 'description', labelKey: 'label_description', fallback: 'צור תיאור AI',
            selectors: ['#attachment-details-two-column-description', '#attachment-details-description'],
            classicSelectors: ['#attachment_content']
        }
    ];

    /**
     * True on the classic "Edit Media" screen, where attachment-specific field
     * ids (and the generic #title) are safe to target.
     */
    function isClassicAttachmentScreen() {
        return document.body && document.body.classList.contains('post-type-attachment');
    }

    /**
     * Add exactly one AI button per field, across all media edit surfaces.
     */
    function addAiButtonsToFields() {
        var attachmentId = getAttachmentId();
        if (!attachmentId) {
            return;
        }

        FIELD_CONFIG.forEach(function(cfg) {
            var selectorList = cfg.selectors.slice();
            if (isClassicAttachmentScreen() && cfg.classicSelectors) {
                selectorList = selectorList.concat(cfg.classicSelectors);
            }

            // First existing field wins; only one button per type per screen.
            var $field = null;
            for (var i = 0; i < selectorList.length; i++) {
                var $candidate = $(selectorList[i]).first();
                if ($candidate.length) { $field = $candidate; break; }
            }
            if (!$field) {
                return;
            }

            // Guard against duplicates (setInterval re-runs, modal re-render).
            if ($field.nextAll('.at-ai-generate-' + cfg.type).length) {
                return;
            }

            var $btn = createAiButton(cfg.type, attachmentId, aiMediaLabel(cfg.labelKey, cfg.fallback));
            $field.after($btn);
        });
    }

    /**
     * Resolve a localized button label from atAiMedia.strings, with a safe
     * fallback so buttons still render if localization is unavailable.
     */
    function aiMediaLabel(key, fallback) {
        if (typeof atAiMedia !== 'undefined' && atAiMedia.strings && atAiMedia.strings[key]) {
            return atAiMedia.strings[key];
        }
        return fallback;
    }

    /**
     * Create AI button element
     */
    function createAiButton(fieldType, attachmentId, label) {
        var $btn = $('<button>', {
            'type': 'button',
            'class': 'button button-secondary at-ai-generate-' + fieldType,
            'data-field-type': fieldType,
            'data-attachment-id': attachmentId,
            'aria-label': label
        });

        // Distinct, meaningful dashicon per field type (visual only).
        var icons = {
            'alt': 'universal-access-alt',
            'title': 'editor-textcolor',
            'caption': 'format-quote',
            'description': 'text'
        };
        var icon = icons[fieldType] || 'admin-generic';

        $btn.html('<span class="dashicons dashicons-' + icon + '" aria-hidden="true"></span> ' + label);
        $btn.on('click', handleAiGeneration);

        return $btn;
    }

    /**
     * Get attachment ID from URL or page
     */
    function getAttachmentId() {
        // Try to get from URL parameter
        var urlParams = new URLSearchParams(window.location.search);
        var id = urlParams.get('item');
        
        if (id) return id;

        // Try to get from hidden field
        var $hiddenId = $('#post_ID, input[name="id"]');
        if ($hiddenId.length) {
            return $hiddenId.val();
        }

        return null;
    }

    /**
     * Handle AI generation button click
     */
    function handleAiGeneration(e) {
        e.preventDefault();

        var $button = $(this);
        var fieldType = $button.data('field-type');
        var attachmentId = $button.data('attachment-id');

        // Find the input field. Prefer the field this button is attached to
        // (buttons are inserted directly after their field), which is reliable
        // even when a broad selector would match the wrong element in the media
        // modal. Fall back to a type-based lookup.
        var $field = $button.prevAll('input, textarea').first();
        if (!$field.length) {
            $field = findFieldByType(fieldType);
        }
        if (!$field || !$field.length) {
            alert(aiMediaLabel('field_not_found', 'שדה לא נמצא'));
            return;
        }

        // Disable button and show spinner (loading state)
        var originalHtml = $button.html();
        var succeeded = false;
        $button.prop('disabled', true)
            .addClass('is-loading')
            .attr('aria-busy', 'true')
            .html('<span class="dashicons dashicons-update spin" aria-hidden="true"></span> ' + atAiMedia.strings.generating);

        // Determine AJAX action
        var action = 'at_ai_generate_' + fieldType;
        if (fieldType === 'description') {
            action = 'at_ai_generate_image_description';
        } else if (fieldType === 'alt') {
            action = 'at_ai_generate_alt_text_media';
        }

        // Make AJAX request
        $.ajax({
            url: atAiMedia.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: atAiMedia.nonce,
                attachment_id: attachmentId
            },
            success: function(response) {
                if (response.success) {
                    var content = extractContent(response.data, fieldType);
                    // Set the value, then notify WordPress/media listeners.
                    // Guard the change trigger so a third-party handler that
                    // throws (e.g. WooCommerce wc-tracks on product screens)
                    // can't abort the update, and dispatch a native input event
                    // so WordPress reliably persists the new value.
                    $field.val(content);
                    try {
                        $field.trigger('change');
                    } catch (err) {
                        if (window.console && console.warn) {
                            console.warn('AI media: downstream change handler threw', err);
                        }
                    }
                    if ($field[0]) {
                        $field[0].dispatchEvent(new Event('input', { bubbles: true }));
                    }

                    // Polished success state on the button itself: swap to a
                    // check icon + success label, then restore after a beat.
                    succeeded = true;
                    $button.removeClass('is-loading').addClass('is-success')
                        .prop('disabled', false)
                        .removeAttr('aria-busy')
                        .html('<span class="dashicons dashicons-yes" aria-hidden="true"></span> ' + atAiMedia.strings.success);

                    setTimeout(function() {
                        $button.removeClass('is-success').html(originalHtml);
                    }, 2500);

                    if (response.data.usage) {
                        console.log('AI Usage:', response.data.usage);
                    }
                } else {
                    alert(atAiMedia.strings.error + ': ' + (response.data || aiMediaLabel('unknown_error', 'שגיאה לא ידועה')));
                }
            },
            error: function() {
                alert(atAiMedia.strings.network_error);
            },
            complete: function() {
                // On success the button keeps its success state (restored by the
                // timeout above); only reset here for the error/failure paths.
                if (!succeeded) {
                    $button.prop('disabled', false)
                        .removeClass('is-loading')
                        .removeAttr('aria-busy')
                        .html(originalHtml);
                }
            }
        });
    }

    /**
     * Find field by type
     */
    function findFieldByType(fieldType) {
        var selectors = {
            'title': '#attachment-details-two-column-title, #attachment-details-title, #title, [data-setting="title"]',
            'alt': '#attachment-details-two-column-alt-text, #attachment-details-alt-text, #attachment_alt, [data-setting="alt"]',
            'caption': '#attachment-details-two-column-caption, #attachment-details-caption, #attachment_caption, [data-setting="caption"]',
            'description': '#attachment-details-two-column-description, #attachment-details-description, #attachment_content, [data-setting="description"]'
        };

        return $(selectors[fieldType] || '').first();
    }

    /**
     * Extract content from response
     */
    function extractContent(data, fieldType) {
        var map = {
            'title': 'title',
            'caption': 'caption',
            'alt': 'alt_text',
            'description': 'description'
        };
        
        return data[map[fieldType]] || data.description || data.text || '';
    }

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Initial pass once the media UI has rendered.
        setTimeout(addAiButtonsToFields, 1000);

        // Re-run on DOM changes (modal open/close, Backbone re-render).
        // Buttons are de-duplicated per field inside addAiButtonsToFields().
        setInterval(addAiButtonsToFields, 2000);
    });

    // Spinner/loading/success/hover styling lives in admin/css/admin.css
    // (enqueued on the same admin pages), so no runtime <style> injection here.

})(jQuery);

