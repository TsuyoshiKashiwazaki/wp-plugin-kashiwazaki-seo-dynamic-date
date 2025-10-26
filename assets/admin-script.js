/**
 * Kashiwazaki SEO Dynamic Date - Admin Script
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Live Preview
        $('#ksdate-preview-button').on('click', function() {
            const format = $('#preview_format').val();
            const offset = $('#preview_offset').val();
            const $button = $(this);
            const $resultDiv = $('#ksdate-preview-result');
            const $shortcodeDiv = $('#ksdate-preview-shortcode');

            // Disable button and show loading
            $button.prop('disabled', true).text('Loading...');

            $.ajax({
                url: ksdateAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ksdate_preview',
                    nonce: ksdateAdmin.nonce,
                    format: format,
                    offset: offset
                },
                success: function(response) {
                    if (response.success) {
                        // Show result
                        $resultDiv.find('.ksdate-preview-output').text(response.data.result);
                        $resultDiv.fadeIn();

                        // Generate shortcode
                        let shortcode = '[ksdate';
                        if (format && format !== 'Y年m月d日') {
                            shortcode += ' format="' + format + '"';
                        }
                        if (offset) {
                            shortcode += ' offset="' + offset + '"';
                        }
                        shortcode += ']';

                        $shortcodeDiv.find('.ksdate-shortcode-output').val(shortcode);
                        $shortcodeDiv.fadeIn();
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('AJAX request failed. Please try again.');
                },
                complete: function() {
                    // Re-enable button
                    $button.prop('disabled', false).text('Preview');
                }
            });
        });

        // Quick format buttons
        $('.ksdate-quick-format').on('click', function() {
            const format = $(this).data('format');
            $('#preview_format').val(format);
            $('#ksdate-preview-button').trigger('click');
        });

        // Copy shortcode to clipboard
        $(document).on('click', '.ksdate-copy-shortcode', function() {
            const $input = $('.ksdate-shortcode-output');
            $input.select();
            document.execCommand('copy');

            const $button = $(this);
            const originalText = $button.text();
            $button.text('Copied!');

            setTimeout(function() {
                $button.text(originalText);
            }, 2000);
        });

        // Auto-preview on Enter key
        $('#preview_format, #preview_offset').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#ksdate-preview-button').trigger('click');
            }
        });

        // Format validation on settings page
        $('#default_format').on('blur', function() {
            const format = $(this).val();
            if (format) {
                // Simple validation - just check if it's not empty
                // Actual validation happens on the server side
                $(this).css('border-color', '#8c8f94');
            }
        });

        // Tab handling (if needed for future enhancements)
        $('.nav-tab').on('click', function() {
            const tab = $(this).attr('href').split('tab=')[1];
            // Save tab to localStorage
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('ksdate_active_tab', tab);
            }
        });

        // Restore active tab from localStorage
        if (typeof(Storage) !== "undefined") {
            const savedTab = localStorage.getItem('ksdate_active_tab');
            if (savedTab) {
                const currentUrl = window.location.href;
                if (currentUrl.indexOf('tab=') === -1) {
                    // No tab in URL, redirect to saved tab
                    const newUrl = currentUrl + (currentUrl.indexOf('?') > -1 ? '&' : '?') + 'tab=' + savedTab;
                    // Don't redirect automatically to avoid confusion
                }
            }
        }

        // Confirm before leaving if there are unsaved changes
        let formChanged = false;
        $('form input, form textarea, form select').on('change', function() {
            formChanged = true;
        });

        $('form').on('submit', function() {
            formChanged = false;
        });

        $(window).on('beforeunload', function() {
            if (formChanged) {
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });

        // Initialize tooltips (if WordPress has them)
        if (typeof $.fn.tooltip !== 'undefined') {
            $('[data-toggle="tooltip"]').tooltip();
        }

        // Add animation to cards
        $('.card').css('opacity', '0').animate({ opacity: 1 }, 300);

        // Smooth scroll for anchor links
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this).attr('href');
            if (target !== '#') {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $(target).offset().top - 32
                }, 500);
            }
        });
    });

})(jQuery);