jQuery(document).ready(function($) {
    // WP Color Picker
    $('.color-picker').wpColorPicker();

    // Media Uploader
    $(document).on('click', '.upload_image_button', function(e) {
        e.preventDefault();
        var button = $(this);
        var wrapper = button.closest('.image-uploader-wrapper');
        var inputField = wrapper.find('.image-url-input');
        var removeButton = wrapper.find('.remove_image_button');

        var frame = wp.media({
            title: 'Select or Upload an Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            inputField.val(attachment.url);
            removeButton.show();

            // Check if this is the login logo uploader and populate width/height
            if (inputField.attr('id') === 'ecb_login_logo') {
                var widthInput = $('#ecb_login_logo_width');
                var heightInput = $('#ecb_login_logo_height');

                // Always update the dimensions with the new image's dimensions.
                if (attachment.width) {
                    widthInput.val(attachment.width + 'px');
                }
                if (attachment.height) {
                    heightInput.val(attachment.height + 'px');
                }
            }
        });

        frame.open();
    });

    // Remove Image
    $(document).on('click', '.remove_image_button', function(e) {
        e.preventDefault();
        var button = $(this);
        var wrapper = button.closest('.image-uploader-wrapper');
        var inputField = wrapper.find('.image-url-input');

        inputField.val('');
        button.hide();
    });
});
