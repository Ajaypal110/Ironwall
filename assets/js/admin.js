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

        // ── Localize Dates & Times ──
        $('.irw-localize-date').each(function() {
            var ts = $(this).data('timestamp');
            if (ts) {
                var d = new Date(ts.replace(' ', 'T'));
                if (!isNaN(d)) {
                    var opts = { month: 'short', day: 'numeric', year: 'numeric' };
                    // If no year was in the original text and it's short, display without year
                    if ($(this).text().indexOf(',') === -1 && $(this).text().length < 8) {
                         opts = { month: 'short', day: 'numeric' };
                    }
                    $(this).text(d.toLocaleDateString(undefined, opts));
                }
            }
        });

        $('.irw-localize-time').each(function() {
            var ts = $(this).data('timestamp');
            if (ts) {
                var d = new Date(ts.replace(' ', 'T'));
                if (!isNaN(d)) {
                    $(this).text(d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }));
                }
            }
        });

    });

})(jQuery);
