/**
 * Media AI Generator - JavaScript for media library AI buttons
 *
 * @since      1.3.0
 * @package    WordPress_AI_Assistant
 */

(function($) {
    'use strict';

    /**
     * Add AI buttons to attachment fields after page load
     */
    function addAiButtonsToFields() {
        var attachmentId = getAttachmentId();
        if (!attachmentId) {
            console.log('No attachment ID found');
            return;
        }

        console.log('Adding AI buttons for attachment:', attachmentId);

        // Alt text field
        var $altInput = $('#attachment-details-two-column-alt-text');
        if ($altInput.length && !$altInput.next('.at-ai-generate-alt').length) {
            var $altBtn = createAiButton('alt', attachmentId, 'צור טקסט חלופי AI');
            $altInput.after($altBtn);
            console.log('Alt button added');
        }

        // Title field
        var $titleInput = $('#attachment-details-two-column-title');
        if ($titleInput.length && !$titleInput.next('.at-ai-generate-title').length) {
            var $titleBtn = createAiButton('title', attachmentId, 'צור כותרת AI');
            $titleInput.after($titleBtn);
            console.log('Title button added');
        }

        // Caption field  
        var $captionInput = $('#attachment-details-two-column-caption');
        if ($captionInput.length && !$captionInput.next('.at-ai-generate-caption').length) {
            var $captionBtn = createAiButton('caption', attachmentId, 'צור כיתוב AI');
            $captionInput.after($captionBtn);
            console.log('Caption button added');
        }

        // Description field
        var $descInput = $('#attachment-details-two-column-description');
        if ($descInput.length && !$descInput.next('.at-ai-generate-description').length) {
            var $descBtn = createAiButton('description', attachmentId, 'צור תיאור AI');
            $descInput.after($descBtn);
            console.log('Description button added');
        }
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
            'style': 'margin-top: 5px;'
        });

        $btn.html('<span class="dashicons dashicons-admin-generic"></span> ' + label);
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

        // Find the input field
        var $field = findFieldByType(fieldType);
        if (!$field || !$field.length) {
            alert('שדה לא נמצא');
            return;
        }

        // Disable button and show spinner
        var originalHtml = $button.html();
        $button.prop('disabled', true).html(
            '<span class="dashicons dashicons-update spin"></span> ' + atAiMedia.strings.generating
        );

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
                    $field.val(content).trigger('change');
                    
                    $button.after('<span class="description" style="color: #46b450; margin-right: 5px;">✓ ' + atAiMedia.strings.success + '</span>');
                    
                    setTimeout(function() {
                        $button.siblings('.description').fadeOut(function() {
                            $(this).remove();
                        });
                    }, 3000);

                    if (response.data.usage) {
                        console.log('AI Usage:', response.data.usage);
                    }
                } else {
                    alert(atAiMedia.strings.error + ': ' + (response.data || 'שגיאה לא ידועה'));
                }
            },
            error: function() {
                alert(atAiMedia.strings.network_error);
            },
            complete: function() {
                $button.prop('disabled', false).html(originalHtml);
            }
        });
    }

    /**
     * Find field by type
     */
    function findFieldByType(fieldType) {
        var selectors = {
            'title': '#attachment-details-two-column-title, [data-setting="title"]',
            'alt': '#attachment-details-two-column-alt-text, [data-setting="alt"]',
            'caption': '#attachment-details-two-column-caption, [data-setting="caption"]',
            'description': '#attachment-details-two-column-description, [data-setting="description"]'
        };

        var $field = $(selectors[fieldType] || '');
        console.log('Finding field for', fieldType, ':', $field.length, 'found');
        return $field.first();
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
        console.log('Media AI Generator loading...');
        
        // Add buttons after page loads
        setTimeout(function() {
            addAiButtonsToFields();
            console.log('AI buttons added');
        }, 1000);

        // Re-add buttons every 2 seconds (in case DOM changes)
        setInterval(function() {
            addAiButtonsToFields();
        }, 2000);
    });

    // Add simple spin animation for dashicons
    var style = document.createElement('style');
    style.textContent = '.dashicons.spin { animation: rotation 1s infinite linear; } @keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }';
    document.head.appendChild(style);

})(jQuery);

