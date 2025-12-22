/**
 * Admin JavaScript for WordPress AI Assistant
 *
 * @since      1.1.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/admin/js
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Test API connection buttons
        $('.at-ai-test-connection').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var provider = $button.data('provider');
            var originalText = $button.text();

            $button.prop('disabled', true).text(at_ai_admin.strings.testing);

            $.post(at_ai_admin.ajax_url, {
                action: 'at_ai_test_connection',
                nonce: at_ai_admin.nonce,
                provider: provider
            })
            .done(function(response) {
                if (response.success) {
                    $button.css('background-color', '#28a745').text(at_ai_admin.strings.test_success);
                    setTimeout(function() {
                        $button.css('background-color', '').text(originalText);
                    }, 3000);
                } else {
                    $button.css('background-color', '#dc3545').text(at_ai_admin.strings.test_failed);
                    alert(response.data || at_ai_admin.strings.test_failed);
                    setTimeout(function() {
                        $button.css('background-color', '').text(originalText);
                    }, 3000);
                }
            })
            .fail(function() {
                $button.css('background-color', '#dc3545').text(at_ai_admin.strings.test_failed);
                alert(at_ai_admin.strings.test_failed);
                setTimeout(function() {
                    $button.css('background-color', '').text(originalText);
                }, 3000);
            })
            .always(function() {
                $button.prop('disabled', false);
            });
        });

        // Auto-generate alt text for media items
        $('.at-ai-generate-alt-text').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var attachmentId = $button.data('attachment-id');
            var originalText = $button.text();

            $button.prop('disabled', true).text(at_ai_admin.strings.generating || 'Generating...');

            $.post(at_ai_admin.ajax_url, {
                action: 'at_ai_generate_alt_text',
                nonce: at_ai_admin.nonce,
                attachment_id: attachmentId
            })
            .done(function(response) {
                if (response.success) {
                    $('#attachment-details-alt-text').val(response.data.alt_text);
                    $button.text(at_ai_admin.strings.generated);
                    setTimeout(function() {
                        $button.text(originalText);
                    }, 3000);
                } else {
                    alert(response.data || at_ai_admin.strings.generation_failed);
                    $button.text(originalText);
                }
            })
            .fail(function() {
                alert(at_ai_admin.strings.network_error);
                $button.text(originalText);
            })
            .always(function() {
                $button.prop('disabled', false);
            });
        });

        // AI processing buttons in post meta boxes
        $('#at-ai-process-post-btn, #at-ai-quick-translate-btn').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var action = $button.attr('id') === 'at-ai-process-post-btn' ? 'at_ai_process_post' : 'at_ai_translate_text';
            var originalText = $button.text();

            $button.prop('disabled', true).text(at_ai_admin.strings.processing);

            var ajaxData = {
                action: action,
                nonce: at_ai_admin.nonce,
                post_id: typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor') ?
                    wp.data.select('core/editor').getCurrentPostId() : $('#post_ID').val()
            };

            if (action === 'at_ai_translate_text') {
                ajaxData.text = $('#title').val() + '\n\n' + (tinyMCE.activeEditor ? tinyMCE.activeEditor.getContent() : $('#content').val());
                ajaxData.target_language = $('select[name="at_ai_quick_translate"]').val();
                ajaxData.context = ['wordpress', 'post', 'content'];
            }

            $.post(at_ai_admin.ajax_url, ajaxData)
            .done(function(response) {
                if (response.success) {
                    $('#at-ai-processing-result, #at-ai-translation-result').html(
                        '<div style="color: green; padding: 10px; border: 1px solid #28a745; border-radius: 4px; background: #d4edda;">' +
                        response.data.message +
                        '</div>'
                    ).show();

                    $button.text(at_ai_admin.strings.processed);

                    if (action === 'at_ai_translate_text') {
                        // Reload page to show translation results
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                } else {
                    $('#at-ai-processing-result, #at-ai-translation-result').html(
                        '<div style="color: red; padding: 10px; border: 1px solid #dc3545; border-radius: 4px; background: #f8d7da;">' +
                        (response.data || at_ai_admin.strings.processing_failed) +
                        '</div>'
                    ).show();

                    $button.text(at_ai_admin.strings.try_again);
                }
            })
            .fail(function() {
                $('#at-ai-processing-result, #at-ai-translation-result').html(
                    '<div style="color: red; padding: 10px; border: 1px solid #dc3545; border-radius: 4px; background: #f8d7da;">' +
                    at_ai_admin.strings.network_error +
                    '</div>'
                ).show();

                $button.text(originalText);
            })
            .always(function() {
                $button.prop('disabled', false);
            });
        });

        // Settings form submission
        $('#at-ai-settings-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $submitButton = $form.find('input[type="submit"]');
            var originalText = $submitButton.val();

            $submitButton.prop('disabled', true).val(at_ai_admin.strings.saving);

            $.post(at_ai_admin.ajax_url, $form.serialize())
            .done(function(response) {
                if (response.success) {
                    $submitButton.css('background-color', '#28a745').val(at_ai_admin.strings.saved);
                    setTimeout(function() {
                        $submitButton.css('background-color', '').val(originalText);
                    }, 3000);
                } else {
                    alert(response.data || at_ai_admin.strings.save_failed);
                    $submitButton.val(originalText);
                }
            })
            .fail(function() {
                alert(at_ai_admin.strings.network_error);
                $submitButton.val(originalText);
            })
            .always(function() {
                $submitButton.prop('disabled', false);
            });
        });

        // Provider selection change
        $('select[name="at_ai_assistant_active_provider"]').on('change', function() {
            var selectedProvider = $(this).val();

            // Hide all provider sections
            $('.at-ai-provider-credentials').hide();

            // Show selected provider section
            $('.at-ai-provider-credentials[data-provider="' + selectedProvider + '"]').show();
        });

        // Initialize provider visibility
        var initialProvider = $('select[name="at_ai_assistant_active_provider"]').val();
        if (initialProvider) {
            $('.at-ai-provider-credentials[data-provider="' + initialProvider + '"]').show();
        }

        // Add provider data attributes
        $('.at-ai-provider-credentials').each(function() {
            var provider = $(this).find('input[type="password"]').attr('id').replace('at_ai_', '').replace('_api_key', '');
            $(this).attr('data-provider', provider);
        });

        // Toggle API key visibility
        $('.at-ai-toggle-api-key').on('click', function(e) {
            e.preventDefault();

            var $button = $(this);
            var $input = $button.siblings('input[type="password"], input[type="text"]');
            var inputType = $input.attr('type');

            if (inputType === 'password') {
                $input.attr('type', 'text');
                $button.text(at_ai_admin.strings.hide);
            } else {
                $input.attr('type', 'password');
                $button.text(at_ai_admin.strings.show);
            }
        });

        // Usage chart initialization (if Chart.js is available)
        // Chart data must be injected via wp_localize_script in PHP
        // (Implementation removed - requires chart library integration)
    });

    // Utility function to generate random colors
    function getRandomColor(alpha = 1) {
        var r = Math.floor(Math.random() * 255);
        var g = Math.floor(Math.random() * 255);
        var b = Math.floor(Math.random() * 255);

        if (alpha === 1) {
            return 'rgb(' + r + ', ' + g + ', ' + b + ')';
        } else {
            return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
        }
    }

})(jQuery);
