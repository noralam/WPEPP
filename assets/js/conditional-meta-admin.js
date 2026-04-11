/**
 * JavaScript for Conditional Meta Box
 *
 * @package WPEPP
 */

jQuery(document).ready(function($) {
    // Toggle conditional options when enable checkbox is clicked
    $('input[name="wpepp_conditional_display_enable"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('.wpepp-conditional-options').slideDown(300);
        } else {
            $('.wpepp-conditional-options').slideUp(300);
        }
    });

    // Toggle condition-specific fields when condition changes
    $('#wpepp_conditional_display_condition').on('change', function() {
        var selectedCondition = $(this).val();
        
        // Hide all condition fields
        $('.wpepp-condition-fields').hide();
        
        // Show the selected condition fields
        $('.wpepp-condition-' + selectedCondition).show();
    });

    // Initialize select2 for multiple select fields if available
    if ($.fn.select2) {
        $('#wpepp_conditional_browser_type, #wpepp_conditional_recurring_days, #wpepp_conditional_day_of_week, #wpepp_conditional_post_type, #wpepp_conditional_user_role').select2({
            width: '100%',
            placeholder: wpepp_conditional_data.select_placeholder
        });
    }

    // Toggle notice text when show notice checkbox changes
    $('input[name="wpepp_conditional_notice_enable"]').on('change', function() {
        if ($(this).is(':checked')) {
            $('.wpepp-notice-text-wrapper').slideDown(300);
        } else {
            $('.wpepp-notice-text-wrapper').slideUp(300);
        }
    });
});