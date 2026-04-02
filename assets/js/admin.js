/**
 * Ironwall v5.0 — Admin JS
 *
 * Handles global admin UI interactions and animations.
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // ── Smooth card entrance animations ──
        $('.wsg-card, .wsg-stat-card').each(function(i) {
            $(this).css({
                'animation-delay': (i * 0.06) + 's'
            });
        });

        // ── Tooltip setup for truncated user-agents ──
        $(document).on('mouseenter', '.wsg-ua', function() {
            var $el = $(this);
            if (this.offsetWidth < this.scrollWidth && !$el.attr('title')) {
                $el.attr('title', $el.text());
            }
        });

        // ── Settings toggle visual feedback ──
        $('.wsg-toggle input').on('change', function() {
            var $row = $(this).closest('.wsg-setting-row');
            $row.css('background', 'rgba(99, 102, 241, 0.04)');
            setTimeout(function() {
                $row.css('background', 'transparent');
            }, 600);
        });

        // ── Keyboard shortcut: Ctrl+S to save settings ──
        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                var $form = $('.wsg-settings-form');
                if ($form.length) {
                    e.preventDefault();
                    $form.submit();
                }
            }
        });

    });

})(jQuery);
