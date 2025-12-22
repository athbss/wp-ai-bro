/**
 * Content Optimizer - JavaScript for advanced AI features
 *
 * @since      1.3.0
 * @package    WordPress_AI_Assistant
 */

(function($) {
    'use strict';

    var suggestedTerms = {};

    /**
     * Initialize
     */
    $(document).ready(function() {
        // Suggest taxonomies
        $('.at-ai-suggest-taxonomies').on('click', handleSuggestTaxonomies);

        // Apply suggestions
        $('.at-ai-apply-suggestions').on('click', handleApplySuggestions);

        // Optimize content
        $('.at-ai-optimize-content').on('click', handleOptimizeContent);
    });

    /**
     * Handle suggest taxonomies
     */
    function handleSuggestTaxonomies() {
        var $button = $(this);
        var postId = $button.data('post-id');
        var selectedTaxonomies = [];

        // Get selected taxonomies
        $('input[name="at_ai_taxonomies[]"]:checked').each(function() {
            selectedTaxonomies.push($(this).val());
        });

        if (selectedTaxonomies.length === 0) {
            alert(atAiOptimizer.strings.error + ': בחר לפחות טקסונומיה אחת');
            return;
        }

        // Disable button
        var originalHtml = $button.html();
        $button.prop('disabled', true).html(
            '<span class="dashicons dashicons-update at-ai-spinner"></span> ' + 
            atAiOptimizer.strings.suggesting
        );

        // Make AJAX request
        $.ajax({
            url: atAiOptimizer.ajax_url,
            type: 'POST',
            data: {
                action: 'at_ai_suggest_taxonomies',
                nonce: atAiOptimizer.nonce,
                post_id: postId,
                taxonomies: selectedTaxonomies
            },
            success: function(response) {
                if (response.success) {
                    suggestedTerms = response.data.suggestions;
                    displayTaxonomySuggestions(response.data.suggestions);
                    displayUsageStats(response.data.usage);
                } else {
                    alert(atAiOptimizer.strings.error + ': ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert(atAiOptimizer.strings.error + ': ' + error);
            },
            complete: function() {
                $button.prop('disabled', false).html(originalHtml);
            }
        });
    }

    /**
     * Display taxonomy suggestions
     */
    function displayTaxonomySuggestions(suggestions) {
        var $container = $('#at-ai-taxonomy-suggestions');
        var $content = $container.find('.at-ai-suggestions-content');
        
        $content.empty();

        if (Object.keys(suggestions).length === 0) {
            $content.html('<p>לא נמצאו הצעות רלוונטיות.</p>');
            $container.show();
            return;
        }

        $.each(suggestions, function(taxonomy, termIds) {
            var taxonomyObj = getTaxonomyObject(taxonomy);
            if (!taxonomyObj) return;

            var $section = $('<div class="at-ai-taxonomy-section"></div>');
            $section.append('<p><strong>' + taxonomyObj.label + ':</strong></p>');

            $.each(termIds, function(index, termId) {
                var termName = getTermName(taxonomy, termId);
                if (!termName) return;

                var $item = $('<p></p>');
                $item.append(
                    '<label>' +
                    '<input type="checkbox" class="at-ai-suggestion-checkbox" data-taxonomy="' + taxonomy + '" data-term-id="' + termId + '" checked> ' +
                    termName +
                    '</label>'
                );

                $section.append($item);
            });

            $content.append($section);
        });

        $container.fadeIn();
    }

    /**
     * Handle apply suggestions
     */
    function handleApplySuggestions() {
        var $button = $(this);
        var postId = $('.at-ai-suggest-taxonomies').data('post-id');
        var selectedSuggestions = {};

        // Get checked suggestions
        $('.at-ai-suggestion-checkbox:checked').each(function() {
            var $checkbox = $(this);
            var taxonomy = $checkbox.data('taxonomy');
            var termId = $checkbox.data('term-id');

            if (!selectedSuggestions[taxonomy]) {
                selectedSuggestions[taxonomy] = [];
            }

            selectedSuggestions[taxonomy].push(termId);
        });

        if (Object.keys(selectedSuggestions).length === 0) {
            alert('בחר לפחות הצעה אחת');
            return;
        }

        // Disable button
        var originalText = $button.text();
        $button.prop('disabled', true).text(atAiOptimizer.strings.processing);

        // Make AJAX request
        $.ajax({
            url: atAiOptimizer.ajax_url,
            type: 'POST',
            data: {
                action: 'at_ai_apply_taxonomy_suggestions',
                nonce: atAiOptimizer.nonce,
                post_id: postId,
                suggestions: selectedSuggestions
            },
            success: function(response) {
                if (response.success) {
                    alert(atAiOptimizer.strings.applied);
                    // Reload to show applied terms
                    window.location.reload();
                } else {
                    alert(atAiOptimizer.strings.error + ': ' + (response.data || 'Unknown error'));
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                alert(atAiOptimizer.strings.error + ': ' + error);
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    /**
     * Handle optimize content
     */
    function handleOptimizeContent() {
        var $button = $(this);
        var postId = $button.data('post-id');

        // Disable button
        var originalHtml = $button.html();
        $button.prop('disabled', true).html(
            '<span class="dashicons dashicons-update at-ai-spinner"></span> ' + 
            atAiOptimizer.strings.optimizing
        );

        // Make AJAX request
        $.ajax({
            url: atAiOptimizer.ajax_url,
            type: 'POST',
            data: {
                action: 'at_ai_optimize_content',
                nonce: atAiOptimizer.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    displayOptimizationResults(response.data.optimization);
                    displayUsageStats(response.data.usage);
                } else {
                    alert(atAiOptimizer.strings.error + ': ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert(atAiOptimizer.strings.error + ': ' + error);
            },
            complete: function() {
                $button.prop('disabled', false).html(originalHtml);
            }
        });
    }

    /**
     * Display optimization results
     */
    function displayOptimizationResults(optimization) {
        var $container = $('#at-ai-optimization-results');
        $container.empty();

        if (Object.keys(optimization).length === 0) {
            $container.html('<div class="notice notice-warning inline"><p>לא נמצאו הצעות אופטימיזציה.</p></div>').show();
            return;
        }

        var categoryLabels = {
            'structure': 'מבנה',
            'keywords': 'מילות מפתח',
            'readability': 'קריאות',
            'featured_snippets': 'Featured Snippets',
            'ai_answers': 'תשובות AI'
        };

        var categoryIcons = {
            'structure': 'admin-appearance',
            'keywords': 'tag',
            'readability': 'editor-textcolor',
            'featured_snippets': 'star-filled',
            'ai_answers': 'admin-generic'
        };

        $.each(optimization, function(category, content) {
            var label = categoryLabels[category] || category;
            var icon = categoryIcons[category] || 'yes-alt';

            var $suggestion = $('<div class="notice notice-info inline"></div>');
            $suggestion.append(
                '<p><strong><span class="dashicons dashicons-' + icon + '"></span> ' + label + '</strong></p>' +
                '<div>' + escapeHtml(content).replace(/\n/g, '<br>') + '</div>'
            );

            $container.append($suggestion);
        });

        $container.fadeIn();
    }

    /**
     * Display usage stats
     */
    function displayUsageStats(usage) {
        if (!usage || !usage.total_tokens) return;

        var $stats = $('#at-ai-operation-stats');
        $stats.html(
            '<p class="description">' +
            '<strong>סטטיסטיקת שימוש:</strong><br>' +
            'טוקנים נכנסים: ' + (usage.input_tokens || 0) + '<br>' +
            'טוקנים יוצאים: ' + (usage.output_tokens || 0) + '<br>' +
            'סה"כ: ' + usage.total_tokens +
            '</p>'
        );
    }

    /**
     * Get taxonomy object
     */
    function getTaxonomyObject(taxonomy) {
        var $checkbox = $('input[name="at_ai_taxonomies[]"][value="' + taxonomy + '"]');
        if (!$checkbox.length) return null;

        var label = $checkbox.parent().text().trim().split('(')[0].trim();
        return {
            name: taxonomy,
            label: label
        };
    }

    /**
     * Get term name
     */
    function getTermName(taxonomy, termId) {
        // This is a simplified version - in reality, term names are embedded in the response
        // or we would need to fetch them via AJAX
        return 'Term ' + termId;
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

})(jQuery);

