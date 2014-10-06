/* =================================================================================== 
 * File : global.js
 * Description : JS generic functions
 * Authors : Hamza Iqbal - hiqbal[at]actualys.com
 *			 Mikaël Guillin - mguillin[at]actualys.com
 * Copyright : Actualys
 /* =================================================================================== */


/* =================================================================================== */
/* JQUERY CONTEXT */
/* =================================================================================== */
(function($)
{
    /* =================================================================================== */
    /* GLOBAL VARS */
    /* =================================================================================== */

    // Anchor
    var _anchor = window.location.hash;


    // Main elements
    var _doc = $(document);
    var _win = $(window);
    var _html = $('html');
    var _body = $('body');
    var _header = $('#header');
    var _navigation = $('#navigation');
    var _content = $('#content');
    var _footer = $('#footer');

    // Carousels
    var _carousels = $('.carousel-content');

    var _classNames =
            {
                active: 'active',
                opened: 'opened',
                disabled: 'disabled'
            };


    // Fancybox - Defaut config
    var _fbConfig =
            {
                padding: 0,
                autoSize: true,
                fitToView: true,
                helpers:
                        {
                            title:
                                    {
                                        type: 'outside',
                                        position: 'top'
                                    }
                        }
            };

    $.initDatePickers = function()
    {
        var datePickers = $('.date-picker');

        datePickers.each(function()
        {
            var currentDp = $(this);

            hasValue = currentDp.find('input').val();

            currentDp.datetimepicker
                    ({
                        language: 'fr',
                        pickTime: false,
                        useCurrent: false,
                        daysOfWeekDisabled: [0, 2, 3, 4, 5, 6]
                    });

            if(!hasValue) {
                currentDp.find('input').val('');
            }

            currentDp.on('focus', 'input', function()
            {
                currentDp.data('DateTimePicker').show();
            });
        });
        
        var datePickers = $('.date-picker-all-days');

        datePickers.each(function()
        {
            var currentDp = $(this);

            currentDp.datetimepicker
                    ({
                        language: 'fr',
                        pickTime: false
                    });

            currentDp.on('focus', 'input', function()
            {
                currentDp.data('DateTimePicker').show();
            });
        });
    };

    $.initSelect2Autocomplete = function()
    {

        $('.select2autocomplete').select2({allowClear: true, placeholder: true});
    }

    $.initCheckboxRelations = function()
    {
        $('.checkbox-relation').click(function() {
            $($(this).attr('data-relation')).toggleClass("hidden");
        })

    }

    /* =================================================================================== */
    /* FUNCTIONS CALL */
    /* =================================================================================== */
    _doc.ready(function()
    {
        $.initDatePickers();
        $.initSelect2Autocomplete();
        $.initCheckboxRelations();
    });

})(jQuery);