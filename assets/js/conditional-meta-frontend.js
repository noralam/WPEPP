/**
 * Frontend JavaScript for Conditional Meta
 *
 * @package WPEPP
 */

(function($) {
    'use strict';

    // Only handle browser-specific conditions that can't be reliably detected server-side
    function handleBrowserSpecificConditions() {
        // Check if conditional data exists
        if (typeof wpeppConditionalMeta === 'undefined') {
            return;
        }

        // Only process if we need client-side detection
        if (!wpeppConditionalMeta.needs_client_detection) {
            return;
        }

        var condition = wpeppConditionalMeta.condition;
        var action = wpeppConditionalMeta.action;
        var conditionData = wpeppConditionalMeta.condition_data;
        var shouldShow = false;
        
        // Only evaluate browser-specific conditions
        if (condition === 'browser_type') {
            shouldShow = evaluateBrowserType(conditionData);
        } else if (condition === 'referrer_source') {
            shouldShow = evaluateReferrerSource(conditionData);
        }

        // If action is 'hide', invert the result
        if (action === 'hide') {
            shouldShow = !shouldShow;
        }

        // Apply display setting to content
        var $content = $('#wpepp-conditional-content');
        if ($content.length) {
            if (shouldShow) {
                $content.show();
            } else {
                $content.hide();
            }
        }
        
        // Apply display setting to title
        var $title = $('#wpepp-conditional-title');
        if ($title.length) {
            if (shouldShow) {
                $title.show();
            } else {
                $title.hide();
            }
        }
        
        // Apply display setting to featured image
        var $thumbnail = $('#wpepp-conditional-thumbnail');
        if ($thumbnail.length) {
            if (shouldShow) {
                $thumbnail.show();
            } else {
                $thumbnail.hide();
            }
        }
    }

    // Browser type condition
    function evaluateBrowserType(data) {
        var browserTypes = data.browser_types;
        if (!browserTypes || !browserTypes.length) {
            return false;
        }
        
        var userAgent = navigator.userAgent.toLowerCase();
        
        for (var i = 0; i < browserTypes.length; i++) {
            var browser = browserTypes[i];
            
            switch (browser) {
                case 'chrome':
                    if (userAgent.indexOf('chrome') > -1 && userAgent.indexOf('edge') === -1) return true;
                    break;
                case 'firefox':
                    if (userAgent.indexOf('firefox') > -1) return true;
                    break;
                case 'safari':
                    if (userAgent.indexOf('safari') > -1 && userAgent.indexOf('chrome') === -1) return true;
                    break;
                case 'edge':
                    if (userAgent.indexOf('edge') > -1 || userAgent.indexOf('edg') > -1) return true;
                    break;
                case 'opera':
                    if (userAgent.indexOf('opr') > -1 || userAgent.indexOf('opera') > -1) return true;
                    break;
                case 'ie':
                    if (userAgent.indexOf('msie') > -1 || userAgent.indexOf('trident') > -1) return true;
                    break;
            }
        }
        
        return false;
    }

    // Referrer source condition
    function evaluateReferrerSource(data) {
        var referrerSource = data.referrer_source;
        
        if (!referrerSource) {
            return false;
        }
        
        var referrer = document.referrer.toLowerCase();
        
        return referrer.indexOf(referrerSource.toLowerCase()) > -1;
    }

    // Run when DOM is ready
    $(document).ready(function() {
        handleBrowserSpecificConditions();
    });

})(jQuery);