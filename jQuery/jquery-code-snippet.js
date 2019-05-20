jQuery(document).ready(function ($) {

    let toggleLoading = function (state) {
        if (state === 1) {
            $('.cj-loading-form-fields').fadeIn();
            $('#cj-form-builder-form-fields').hide(0);
        }
        if (state === 0) {
            $('.cj-loading-form-fields').hide();
            $('#cj-form-builder-form-fields').fadeIn();
        }
    };

    // add new form field
    $('.cj-add-form-field').on('click', function () {
        let field = $(this).data('field-map');
        let form_id = $('#form-id').val();
        let saved_values = $('#saved-values').val();
        let defaults = $(this).find('textarea.defaults').val();
        let settings = $(this).find('textarea.settings').val();
        let params = $(this).find('textarea.params').val();
        let validation_rules = $(this).find('textarea.validation_rules').val();
        let data = {
            'action': 'add_form_field',
            'saved_values': saved_values,
            'defaults': defaults,
            'settings': settings,
            'params': params,
            'validation_rules': validation_rules,
            'form_id': form_id,
            'add_field': field,
        };
        $.post(ajaxurl, data, function (response) {
            $('#saved-values').val(response);
            populateFormFields();
        });
        return false;
    });

    let updateSortOrder = function () {
        let order = [];
        $('#cj-form-builder-form-fields .field-sort-order').each(function () {
            order.push($(this).val());
        });
        let form_id = $('#form-id').val();
        let data = {
            'action': 'update_field_sort_order',
            'order': order,
            'form_id': form_id,
        };
        $.post(ajaxurl, data, function (response) {
            populateFormFields();
        });
    };

    let make_sortable = function () {
        $('#cj-form-builder-form-fields').sortable({
            handle: ".cj-field-sort-handle",
            placeholder: ".sortable-highlight",
            cursor: 'move',
            revert: true,
            dropOnEmpty: false,
            connectWith: "#cj-form-builder-form-fields",
            tolerance: 'pointer',
            start: function (e, ui) {
                ui.placeholder.height(ui.item.height());
                ui.placeholder.attr('class', ui.item.attr('class') + ' sortable-highlight');
            },
            stop: function (event, ui) {
                updateSortOrder();
            }
        });
    };

    let populateFormFields = function () {
        toggleLoading(1);
        let form_id = $('#form-id').val();
        if (form_id !== undefined) {
            let data = {
                'action': 'load_form_fields',
                'form_id': form_id,
            };
            $.post(ajaxurl, data, function (response) {
                toggleLoading(0);
                $('#form-fields .cj-form-fields-ajax-content').html(response);
                make_sortable();
            });
        }
    };

    // load form fields
    populateFormFields();

    $(document).on('click', '.cj-remove-field', function () {
        let msg = $(this).data('confirm');
        let location = $(this).attr('href');
        let field = $(this).data('remove-field');
        let form_id = $('#form-id').val();
        let data = {
            'action': 'remove_field',
            'form_id': form_id,
            'remove_field': field,
        };
        swal({
            title: '',
            text: msg,
            type: 'error',
            confirmButtonColor: '#DA461E',
            showCancelButton: true,
            buttonsStyling: true,
        }).then(function () {
            $.post(ajaxurl, data, function (response) {
                populateFormFields();
            });
            return false;
        }, function () {
            return false;
        });
        return false;
    });

    $(document).on('click', '.cj-form-builder-populate-fields', function () {
        populateFormFields();
    });


    // save form settings
    $(document).on('submit', '#cj-form-builder-save-form-settings', function () {
        let $form = $(this);
        $form.find('button[type="submit"]').addClass('cj-is-loading');
        let data = $(this).serialize();
        $.ajax({
            url: ajaxurl,
            type: 'post',
            data: {
                action: 'update_form_settings',
                data: data
            },
            success: function (response) {
                let obj = JSON.parse(response);
                $('.cj-modal').removeClass('cj-is-active');
                $form.find('button[type="submit"]').removeClass('cj-is-loading');
                if (obj.success) {
                    swal({
                        title: '',
                        text: obj.msg,
                        type: 'success',
                        showConfirmButton: false,
                        buttonsStyling: true,
                        timer: 1500,
                    }).then(function () {
                    }, function () {
                        $(document).find('.form-post-title').text(obj.data.post_title);
                    });
                } else {
                    swal({
                        title: '',
                        text: obj.msg,
                        type: 'error',
                        confirmButtonColor: '#DA461E',
                        buttonsStyling: true
                    });
                }
            }
        });
        return false;
    });


    let formSubmitted = function ($form) {
        let $button = $form.find('button[type=submit]');
        let button_text = $button.html();
        $button.toggleClass('cj-is-primary cj-is-success');
        $button.html('<span class="cj-icon cj-is-small"><i class="fa fa-check"></i></span><span class="cj-ml-5">Saved</span>');
        setTimeout(function () {
            $button.html(button_text);
            $button.toggleClass('cj-is-primary cj-is-success');
        }, 1500);
    };


    // save field options
    $(document).on('submit', '.cj-field-options-form', function () {
        let $form = $(this);
        $form.find('button[type="submit"]').addClass('cj-is-loading');
        let form_data = $(this).serialize();
        let field_key = $(this).data('field-key');
        let field_map = $(this).data('field-map');
        let form_id = $('#form-id').val();
        let data = {
            'action': 'update_field_options',
            'field_key': field_key,
            'field_map': field_map,
            'form_id': form_id,
            'form_data': form_data,
        };
        $.post(ajaxurl, data, function (response) {
            $form.find('button[type="submit"]').removeClass('cj-is-loading');
            let obj = $.parseJSON(response);
            if (obj.success) {
                formSubmitted($form);
            } else {
                populateFormFields();
                swal({title: '', text: obj.data, type: 'error', confirmButtonColor: '#DA461E', buttonsStyling: true});
            }
            $form.closest('li').find('div.title').html(obj.label);
        });
        return false;
    });

    // save field attributes
    $(document).on('submit', '.cj-field-attributes-form', function () {
        let $form = $(this);
        $form.find('button[type="submit"]').addClass('cj-is-loading');
        let form_data = $(this).serialize();
        let field_key = $(this).data('field-key');
        let field_map = $(this).data('field-map');
        let form_id = $('#form-id').val();

        let data = {
            'action': 'update_field_attributes',
            'field_key': field_key,
            'field_map': field_map,
            'form_id': form_id,
            'form_data': form_data,
        };
        $.post(ajaxurl, data, function (response) {
            // $('.cj-modal').removeClass('cj-is-active');
            $form.find('button[type="submit"]').removeClass('cj-is-loading');
            let obj = $.parseJSON(response);
            if (obj.success) {
                formSubmitted($form);
            } else {
                populateFormFields();
                swal({title: '', text: obj.data, type: 'error', confirmButtonColor: '#DA461E', buttonsStyling: true});
            }
        });
        return false;
    });

    // save form validations
    $(document).on('submit', '.cj-field-validation-form', function () {
        let $form = $(this);
        $form.find('button[type="submit"]').addClass('cj-is-loading');
        let form_data = $(this).serialize();
        let field_key = $(this).data('field-key');
        let field_map = $(this).data('field-map');
        let form_id = $('#form-id').val();
        let data = {
            'action': 'update_field_validations',
            'field_key': field_key,
            'field_map': field_map,
            'form_id': form_id,
            'form_data': form_data,
        };
        $.post(ajaxurl, data, function (response) {
            // $('.cj-modal').removeClass('cj-is-active');
            $form.find('button[type="submit"]').removeClass('cj-is-loading');
            let obj = $.parseJSON(response);
            if (obj.success) {
                formSubmitted($form);
            } else {
                populateFormFields();
                swal({title: '', text: obj.data, type: 'error', confirmButtonColor: '#DA461E', buttonsStyling: true});
            }
        });
        return false;
    });

});