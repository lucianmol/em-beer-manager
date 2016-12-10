/**
 * Copyright (c) 2013-2016, Erin Morelli.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 *
 * EM Beer Manager admin javascript functions
 */

/*global
    jQuery,
    ajaxurl,
    tb_remove,
    embm_settings
*/
/*jslint
    browser: true
    unparam: true
*/

jQuery(document).ready(function ($) {
    'use strict';

    // Get URL and hash
    var url = window.location.href,
        url_hash = window.location.hash,
        url_params = {},
        ajax_params = {
            '_nonce': embm_settings.ajax_nonce,
        },
        ajax_response = function (response) {
            if (typeof response === 'object' && response.hasOwnProperty('redirect')) {
                window.location = response.redirect;
            } else {
                window.location.reload();
            }
        },
        hash,
        page,
        clean_url;

    // Get URL params
    if (window.location.search) {
        window.location.search.replace(/^\?/, '').split('&').forEach(function (param) {
            var split = param.split('=');
            url_params[split[0]] = split[1];
        });
    }

    // Check for a hash in the URL
    if (url_hash) {
        // Don't jump to div
        window.scrollTo(0, 0);

        // Get hash without the #
        hash = url_hash.slice(1);

        // Add/remove active classes
        if (hash !== '') {
            $('#embm-settings--tabs').find('.nav-tab').removeClass('nav-tab-active');
            $('#embm-settings--tabs').find('.nav-tab-' + hash).addClass('nav-tab-active');
        }
    }

    // Clean URL after page load
    if (url_params.hasOwnProperty('page') && url_params.page === 'embm-settings') {
        // Set vars
        page = url.substring(url.lastIndexOf('/') + 1); // Page URL
        clean_url = page.split('?')[0] + '?page=embm-settings' + url_hash; // Reset URL

        // Update URL
        window.history.replaceState(null, null, clean_url);
    }

    // Setup jquery ui tabs
    $('#embm-settings--tabs').tabs({
        activate: function (ignore, ui) {
            // Get tab links
            var new_tab = ui.newTab.find('.nav-tab'),
                old_tab = ui.oldTab.find('.nav-tab'),
                new_hash = new_tab[0].getAttribute("href");

            // Toggle active classes
            new_tab.toggleClass('nav-tab-active');
            old_tab.toggleClass('nav-tab-active');

            // Reset URL hash
            location.hash = new_hash;

            // Don't jump to div
            window.scrollTo(0, 0);
        }
    });

    // Prevent jumping to divs on tab clicks
    $('.embm-nav-tab').on('click', function (e) {
        e.preventDefault();
        return false;
    });

    // Dismiss notices
    $('.embm-notice.notice button.notice-dismiss').on('click', function (e) {
        e.preventDefault();

        // Set vars
        var $el = $('.embm-notice.notice');

        // Remove notice
        $el.fadeTo(100, 0, function () {
            $el.slideUp(100, function () {
                $el.remove();
            });
        });
    });

    // Toggle contextual help for '?' link clicks
    $('.embm-settings--help').on('click', function (e) {
        // Get tab name from link
        var tab = $(this).data('help');

        // Remove 'active' class from all link tabs
        $('li[id^="tab-link-"]').each(function () {
            $(this).removeClass('active');
        });

        // Hide all panels
        $('div[id^="tab-panel-"]').each(function () {
            $(this).css('display', 'none');
        });

        // Set our desired link/panel
        $('#tab-link-' + tab).addClass('active');
        $('#tab-panel-' + tab).css('display', 'block');

        // Force click on the Help tab
        $('#contextual-help-link').click();
    });

    // Select icon option
    $('#embm_untappd_icons').val(embm_settings.options.embm_untappd_icons);

    // Toggle icon image on select change
    $('.embm-settings--untappd-select').on('change', function (e) {
        var img_src = embm_settings.plugin_url + 'assets/img/checkin-button-' + this.value + '.png';
        $('.embm-settings--untappd-icon').attr('src', img_src);
    });

    // Styles reset prompt
    $('button.embm-settings--styles-button').on('click', function (e) {
        e.preventDefault();
        $('#embm-styles-reset-prompt--button').click();
    });

    // Styles reset no
    $('#embm-styles-reset-prompt--no').on('click', function (e) {
        e.preventDefault();
        tb_remove();
    });

    // Styles reset yes
    $('#embm-styles-reset-prompt--yes').on('click', function (e) {
        e.preventDefault();
        ajax_params.action = 'embm-styles-reset';
        $.post(ajaxurl, ajax_params, ajax_response);
    });

    /* ---- UNTAPPD AUTHORIZATION ---- */

    // Redirect to Untappd to authorize user
    $('button.embm-labs--authorize').on('click', function (e) {
        e.preventDefault();
        ajax_params.action = 'embm-untappd-authorize';
        $.post(ajaxurl, ajax_params, ajax_response);
    });

    // Redirect to reauthorize Untappd user
    $('button.embm-labs--reauthorize').on('click', function (e) {
        e.preventDefault();
        ajax_params.action = 'embm-untappd-reauthorize';
        $.post(ajaxurl, ajax_params, ajax_response);
    });

    // Redirect to deauthorize Untappd user
    $('a.embm-untappd--deauthorize').on('click', function (e) {
        e.preventDefault();
        ajax_params.action = 'embm-untappd-deauthorize';
        $.post(ajaxurl, ajax_params, ajax_response);
    });

    /* ---- UNTAPPD METABOX ---- */

    // Toggle beer selection dropdown
    $('.embm-untappd--select select').on('change', function (e) {
        var id_input = $('#embm_untappd'),
            is_reset = (this.value === ''),
            new_value = is_reset ? id_input.data('value') : this.value;

        // Set input & readonly
        id_input.attr('readonly', !is_reset);
        id_input.val(new_value);
    });

    // Handle beer cache flush requests
    $('.embm-metabox--untappd-flush a').on('click', function (e) {
        e.preventDefault();

        var api_root = $(this).data('api-root'),
            untappd_id = $('#embm_untappd').val();

        // Set AJAX params
        ajax_params.action = 'embm-untappd-flush-beer';
        ajax_params.post_id = url_params.post;
        ajax_params.beer_id = untappd_id;
        ajax_params.api_root = api_root;

        // Make AJAX request & reload page
        $.post(ajaxurl, ajax_params, ajax_response);
    });

    /* ---- LABS ---- */

    // Redirect to flush Untappd cache
    $('a.embm-untappd--flush').on('click', function (e) {
        e.preventDefault();
        ajax_params.action = 'embm-untappd-flush';
        $.post(ajaxurl, ajax_params, ajax_response);
    });

    // Handle import requests
    $('a.embm-untappd--import').on('click', function (e) {
        e.preventDefault();

        var import_type = $(this).data('type'),
            api_root = $('#embm-untappd-api-root').val(),
            brewery_id = $('#embm-untappd-brewery-id').val();

        // Set AJAX params
        ajax_params.action = 'embm-untappd-import';
        ajax_params.import_type = import_type;
        ajax_params.api_root = api_root;
        ajax_params.brewery_id = brewery_id;
        ajax_params.beer_ids = $('#embm-untappd-beer-ids').val();
        ajax_params.beer_id = $('#embm-untappd-beer-id').val();

        // Make AJAX request & reload page
        $.post(ajaxurl, ajax_params, ajax_response);
    });
});
