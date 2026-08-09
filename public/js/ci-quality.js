/*
 * This file is part of mesamatrix.
 *
 * Copyright (C) 2026 Mesamatrix contributors.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

(function ($) {
    'use strict';

    function setDriverExpanded($summary, expanded) {
        var driver = $summary.attr('data-ci-driver');
        $summary.attr('aria-expanded', expanded ? 'true' : 'false');
        $summary.toggleClass('is-expanded', expanded);
        $('tr.ci-detail').filter(function () {
            return $(this).attr('data-ci-driver') === driver;
        }).prop('hidden', !expanded);
    }

    function toggleDriver($summary) {
        if ($summary.hasClass('ci-empty')) {
            return;
        }
        var expanded = $summary.attr('aria-expanded') === 'true';
        setDriverExpanded($summary, !expanded);
    }

    $(function () {
        $('table.ci-quality').on('click', 'tr.ci-summary', function () {
            toggleDriver($(this));
        }).on('keydown', 'tr.ci-summary', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleDriver($(this));
            }
        });
    });
})(jQuery);
