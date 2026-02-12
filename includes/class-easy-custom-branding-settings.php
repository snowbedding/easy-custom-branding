<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'Easy_Custom_Branding_Settings' ) ) {

    class Easy_Custom_Branding_Settings {

        private static $instance;
        private $options;

        public static function get_instance() {
            if ( ! isset( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        private function __construct() {
            $this->options = get_option( 'easy_custom_branding_settings', [] );
            add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
            add_action( 'admin_init', [ $this, 'settings_init' ] );
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
            $this->init_hooks();
        }
        
        public function init_hooks() {
            // Admin hooks
            if ( ! empty( $this->options['admin_logo'] ) || ! empty( $this->options['custom_admin_css'] ) ) {
                add_action('admin_head', [ $this, 'custom_admin_head_styles' ]);
            }
            if ( ! empty( $this->options['hide_admin_menu_items'] ) ) {
                add_action( 'admin_menu', [ $this, 'remove_admin_menu_items' ], 9999 );
            }
            if ( ! empty( $this->options['hide_dashboard_widgets'] ) ) {
                add_action( 'wp_dashboard_setup', [ $this, 'remove_dashboard_widgets' ], 999 );
            }
            if ( ! empty( $this->options['hide_dashboard_widgets'] ) && ! empty( $this->options['hide_dashboard_widgets']['welcome_panel'] ) ) {
                add_action( 'admin_init', [ $this, 'remove_welcome_panel' ] );
            }
            if ( ! empty( $this->options['admin_footer_text'] ) ) {
                 add_filter('admin_footer_text', [ $this, 'custom_admin_footer_text' ]);
            }
            if ( ! empty( $this->options['admin_version_text'] ) ) {
                add_filter( 'update_footer', [ $this, 'custom_admin_version_text' ], 999 );
            }
            if ( ! empty( $this->options['hide_help_tabs'] ) ) {
                add_action('admin_head', [ $this, 'hide_help_tabs' ]);
            }
            if ( ! empty( $this->options['hide_screen_options'] ) ) {
                add_action('admin_head', [ $this, 'hide_screen_options' ]);
            }

            // General hooks
            if ( ! empty( $this->options['remove_password_strength'] ) ) {
                add_action( 'wp_print_scripts', [ $this, 'remove_password_strength' ], 100 );
            }
            if ( ! empty( $this->options['hide_admin_bar'] ) ) {
                add_action('after_setup_theme', [ $this, 'remove_admin_bar' ]);
            }
            if ( ! empty( $this->options['clean_header'] ) ) {
                add_filter( 'xmlrpc_enabled', '__return_false' );
                remove_action('wp_head', 'rsd_link');
                remove_action('wp_head', 'wlwmanifest_link');
                remove_action('wp_head', 'feed_links_extra', 3 );
                remove_action('wp_head', 'feed_links', 2 );
                remove_action('wp_head', 'wp_shortlink_wp_head');
                remove_action('wp_head', 'wp_generator');
            }
            if ( ! empty( $this->options['email_from_name'] ) ) {
                add_filter( 'wp_mail_from_name', [ $this, 'custom_from_name' ] );
            }
            if ( ! empty( $this->options['email_from_address'] ) ) {
                add_filter( 'wp_mail_from', [ $this, 'custom_from_email' ] );
            }

            // Login hooks
            if ( ! empty( $this->options['login_logo'] ) || ! empty( $this->options['login_bg'] ) || ! empty( $this->options['custom_login_css'] ) || ! empty( $this->options['login_form_bg_color'] ) ) {
                add_action('login_head', [ $this, 'custom_login_styles' ]);
            }
            add_filter('login_headerurl', [ $this, 'login_logo_link' ]);
            if ( ! empty( $this->options['login_logo_sr_text'] ) ) {
                add_filter('login_headertext', [ $this, 'login_logo_sr_text' ]);
            }
            if ( ! empty( $this->options['login_custom_html'] ) ) {
                add_action( 'login_footer', [ $this, 'custom_login_footer_html' ] );
            }
        }
        
        public function enqueue_scripts( $hook ) {
            if ( 'settings_page_easy_custom_branding' !== $hook ) {
                return;
            }
            // Enqueue WP Color Picker
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_media();
            wp_enqueue_script( 'easy-custom-branding-admin-js', EASY_CUSTOM_BRANDING_PLUGIN_URL . 'assets/js/admin.js', [ 'jquery', 'wp-color-picker' ], EASY_CUSTOM_BRANDING_VERSION, true );
            wp_enqueue_style( 'easy-custom-branding-admin-css', EASY_CUSTOM_BRANDING_PLUGIN_URL . 'assets/css/admin.css', [], EASY_CUSTOM_BRANDING_VERSION );
        }

        public function add_admin_menu() {
            add_options_page(
                __( 'Easy Custom Branding', 'easy-custom-branding' ),
                __( 'Easy Custom Branding', 'easy-custom-branding' ),
                'manage_options',
                'easy_custom_branding',
                [ $this, 'options_page_html' ]
            );
        }

        public function settings_init() {
            register_setting( 'easy_custom_branding_page', 'easy_custom_branding_settings', [ $this, 'sanitize_settings' ] );

            // Login Page Section
            add_settings_section(
                'easy_custom_branding_login_section',
                __( 'Login Page Customization', 'easy-custom-branding' ),
                null,
                'easy_custom_branding_page'
            );

            // Admin Area Section
            add_settings_section(
                'easy_custom_branding_admin_section',
                __( 'Admin Area Customization', 'easy-custom-branding' ),
                null,
                'easy_custom_branding_page'
            );

            // General Settings Section
            add_settings_section(
                'easy_custom_branding_general_section',
                __( 'General Settings', 'easy-custom-branding' ),
                null,
                'easy_custom_branding_page'
            );

            // Email Settings Section
            add_settings_section(
                'easy_custom_branding_email_section',
                __( 'Email Settings', 'easy-custom-branding' ),
                null,
                'easy_custom_branding_page'
            );

            $fields = [
                'admin_section' => [
                    [ 'uid' => 'admin_logo', 'label' => __( 'Admin Bar Logo', 'easy-custom-branding' ), 'type' => 'file', 'description' => __( 'Recommended size: 20x20 pixels.', 'easy-custom-branding' ) ],
                    [ 'uid' => 'admin_footer_text', 'label' => __( 'Admin Footer Text', 'easy-custom-branding' ), 'type' => 'textarea' ],
                    [ 'uid' => 'admin_version_text', 'label' => __( 'Custom Admin Version Text', 'easy-custom-branding' ), 'type' => 'text', 'description' => __( 'Replaces the WordPress version number in the bottom right corner of the admin area.', 'easy-custom-branding' ) ],
                    [ 'uid' => 'custom_admin_css', 'label' => __( 'Custom Admin CSS', 'easy-custom-branding' ), 'type' => 'textarea', 'placeholder' => ".wp-block { display: none; }" ],
                    [ 'uid' => 'hide_dashboard_widgets', 'label' => __( 'Hide Dashboard Widgets', 'easy-custom-branding' ), 'type' => 'multicheckbox', 'options' => [
                        'dashboard_right_now'    => __( 'At a Glance', 'easy-custom-branding' ),
                        'dashboard_activity'     => __( 'Activity', 'easy-custom-branding' ),
                        'dashboard_quick_press'  => __( 'Quick Draft', 'easy-custom-branding' ),
                        'dashboard_primary'      => __( 'WordPress Events and News', 'easy-custom-branding' ),
                        'dashboard_site_health'  => __( 'Site Health Status', 'easy-custom-branding' ),
                        'welcome_panel'          => __( 'Welcome Panel', 'easy-custom-branding' ),
                    ] ],
                    [ 'uid' => 'hide_admin_menu_items', 'label' => __( 'Hide Admin Menu Items', 'easy-custom-branding' ), 'type' => 'multicheckbox', 'options' => $this->get_admin_menu_items(), 'description' => __( 'Hide selected menu items for all non-administrator users.', 'easy-custom-branding' ) ],
                    [ 'uid' => 'hide_help_tabs', 'label' => __( 'Hide Help Tabs', 'easy-custom-branding' ), 'type' => 'checkbox' ],
                    [ 'uid' => 'hide_screen_options', 'label' => __( 'Hide Screen Options', 'easy-custom-branding' ), 'type' => 'checkbox' ],
                ],
                'login_section' => [
                    [ 'uid' => 'login_logo', 'label' => __( 'Login Logo', 'easy-custom-branding' ), 'type' => 'file' ],
                    [ 'uid' => 'login_logo_width', 'label' => __( 'Login Logo Width', 'easy-custom-branding' ), 'type' => 'text', 'description' => __( 'Defaults to the uploaded image width. You can override it here.', 'easy-custom-branding' ) ],
                    [ 'uid' => 'login_logo_height', 'label' => __( 'Login Logo Height', 'easy-custom-branding' ), 'type' => 'text', 'description' => __( 'Defaults to the uploaded image height. You can override it here.', 'easy-custom-branding' ) ],
                    [ 'uid' => 'login_logo_link', 'label' => __( 'Login Logo Link', 'easy-custom-branding' ), 'type' => 'text', 'placeholder' => home_url('/') ],
                    [ 'uid' => 'login_logo_sr_text', 'label' => __( 'Login Logo Screen Reader Text', 'easy-custom-branding' ), 'type' => 'text', 'placeholder' => get_bloginfo('name'), 'description' => __( 'This text is used for screen readers and is not visually displayed.', 'easy-custom-branding' ) ],
                    [ 'uid' => 'login_bg', 'label' => __( 'Login Background Image', 'easy-custom-branding' ), 'type' => 'file' ],
                    [ 'uid' => 'login_form_bg_color', 'label' => __( 'Form Background Color', 'easy-custom-branding' ), 'type' => 'color' ],
                    [ 'uid' => 'login_form_text_color', 'label' => __( 'Form Text Color', 'easy-custom-branding' ), 'type' => 'color' ],
                    [ 'uid' => 'login_button_bg_color', 'label' => __( 'Button Background Color', 'easy-custom-branding' ), 'type' => 'color' ],
                    [ 'uid' => 'login_button_text_color', 'label' => __( 'Button Text Color', 'easy-custom-branding' ), 'type' => 'color' ],
                    [ 'uid' => 'custom_login_css', 'label' => __( 'Custom Login CSS', 'easy-custom-branding' ), 'type' => 'textarea', 'placeholder' => "#loginform { background-color: #ffffff; }" ],
                    [ 'uid' => 'login_custom_html', 'label' => __( 'Custom HTML', 'easy-custom-branding' ), 'type' => 'textarea', 'description' => __( 'This content will be displayed below the login form.', 'easy-custom-branding' ) ],
                ],
                'email_section' => [
                    [ 'uid' => 'email_from_name', 'label' => __( 'From Name', 'easy-custom-branding' ), 'type' => 'text', 'placeholder' => get_bloginfo('name') ],
                    [ 'uid' => 'email_from_address', 'label' => __( 'From Email Address', 'easy-custom-branding' ), 'type' => 'text', 'placeholder' => get_option('admin_email') ],
                ],
                'general_section' => [
                    [ 'uid' => 'hide_admin_bar', 'label' => __( 'Hide Admin Bar', 'easy-custom-branding' ), 'type' => 'checkbox', 'description' => __( 'Hide the admin bar for all users except administrators on the frontend.', 'easy-custom-branding' ) ],
                    [ 'uid' => 'clean_header', 'label' => __( 'Cleanup Header', 'easy-custom-branding' ), 'type' => 'checkbox', 'description' => __( 'Remove unnecessary meta tags from the site header (e.g., WP version, RSD link).', 'easy-custom-branding' ) ],
                    [ 'uid' => 'remove_password_strength', 'label' => __( 'Disable Password Strength Meter', 'easy-custom-branding' ), 'type' => 'checkbox', 'description' => __( 'Disable the password strength meter on WooCommerce forms.', 'easy-custom-branding' ) ],
                ]
            ];

            foreach ($fields as $section => $field_group) {
                foreach ($field_group as $field) {
                    add_settings_field(
                        'ecb_' . $field['uid'],
                        $field['label'],
                        [ $this, 'render_field' ],
                        'easy_custom_branding_page',
                        'easy_custom_branding_' . $section,
                        [
                            'type' => $field['type'],
                            'name' => 'ecb_' . $field['uid'],
                            'value' => isset($this->options[$field['uid']]) ? $this->options[$field['uid']] : ($field['type'] === 'multicheckbox' ? [] : ''),
                            'placeholder' => isset($field['placeholder']) ? $field['placeholder'] : '',
                            'description' => isset($field['description']) ? $field['description'] : '',
                            'options' => isset($field['options']) ? $field['options'] : []
                        ]
                    );
                }
            }
        }
        
        public function render_field( $args ) {
            switch ( $args['type'] ) {
                case 'text':
                    printf(
                        '<input type="text" id="%s" name="easy_custom_branding_settings[%s]" value="%s" placeholder="%s" class="regular-text">',
                        esc_attr($args['name']),
                        esc_attr(str_replace('ecb_', '', $args['name'])),
                        esc_attr($args['value']),
                        esc_attr($args['placeholder'])
                    );
                    break;
                case 'color':
                    printf(
                        '<input type="text" id="%s" name="easy_custom_branding_settings[%s]" value="%s" class="color-picker">',
                        esc_attr($args['name']),
                        esc_attr(str_replace('ecb_', '', $args['name'])),
                        esc_attr($args['value'])
                    );
                    break;
                case 'textarea':
                    printf(
                        '<textarea id="%s" name="easy_custom_branding_settings[%s]" rows="5" class="large-text code">%s</textarea>',
                        esc_attr($args['name']),
                        esc_attr(str_replace('ecb_', '', $args['name'])),
                        esc_textarea($args['value'])
                    );
                    break;
                case 'checkbox':
                    printf(
                        '<input type="checkbox" id="%s" name="easy_custom_branding_settings[%s]" value="1" %s>',
                        esc_attr($args['name']),
                        esc_attr(str_replace('ecb_', '', $args['name'])),
                        checked(1, $args['value'], false)
                    );
                    break;
                case 'multicheckbox':
                    foreach ($args['options'] as $value => $label) {
                        $checked = isset($args['value'][$value]) && $args['value'][$value] ? 'checked' : '';
                        printf(
                            '<label><input type="checkbox" name="easy_custom_branding_settings[%1$s][%2$s]" value="1" %3$s> %4$s</label><br>',
                            esc_attr(str_replace('ecb_', '', $args['name'])),
                            esc_attr($value),
                            $checked,
                            esc_html($label)
                        );
                    }
                    break;
                case 'file':
                    printf(
                        '<div class="image-uploader-wrapper"><input type="text" id="%s" name="easy_custom_branding_settings[%s]" value="%s" class="regular-text image-url-input" readonly> <button class="button upload_image_button">%s</button> <button class="button remove_image_button" style="%s">%s</button></div>',
                        esc_attr($args['name']),
                        esc_attr(str_replace('ecb_', '', $args['name'])),
                        esc_attr($args['value']),
                        __('Upload Image', 'easy-custom-branding'),
                        empty($args['value']) ? 'display:none;' : '',
                        __('Remove', 'easy-custom-branding')
                    );
                    break;
            }
            if (!empty($args['description'])) {
                printf('<p class="description">%s</p>', wp_kses_post($args['description']));
            }
        }
        
        public function sanitize_settings( $input ) {
            $sanitized_input = [];
             if ( empty( $input ) ) {
                return $sanitized_input;
            }
            foreach ($input as $key => $value) {
                if ( is_array( $value ) ) {
                    $sanitized_input[$key] = $value;
                } else {
                    $sanitized_input[$key] = sanitize_text_field($value);
                }
            }
            return $sanitized_input;
        }

        public function options_page_html() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            ?>
            <div class="wrap easy-custom-branding-wrap">
                <div class="cb-settings-header">
                    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
                    <div class="cb-settings-links">
                        <a href="https://wordpress.org/plugins/easy-custom-branding" target="_blank" rel="noopener noreferrer" class="button button-secondary">
                            <?php _e( 'WordPress.org Plugin Page', 'easy-custom-branding' ); ?>
                        </a>
                        <a href="https://github.com/snowbedding/easy-custom-branding" target="_blank" rel="noopener noreferrer" class="button button-secondary">
                            <?php _e( 'GitHub Repository', 'easy-custom-branding' ); ?>
                        </a>
                    </div>
                </div>
                <form action="options.php" method="post" enctype="multipart/form-data">
                    <?php
                    settings_fields( 'easy_custom_branding_page' );
                    do_settings_sections( 'easy_custom_branding_page' );
                    submit_button( __( 'Save Settings', 'easy-custom-branding' ) );
                    ?>
                </form>
            </div>
            <?php
        }

        private function get_admin_menu_items() {
            global $menu;
            $menu_items = [];
            if ( ! is_array( $menu ) ) {
                return $menu_items;
            }
            foreach ( $menu as $item ) {
                if ( ! empty( $item[0] ) ) {
                    // Remove notification bubbles from menu titles
                    $title = preg_replace( '/\s*<span.*<\/span>\s*/', '', $item[0] );
                    $menu_items[ $item[2] ] = $title;
                }
            }
            return $menu_items;
        }

        // Methods from Custom_Branding_Admin
        public function remove_admin_menu_items() {
            if ( current_user_can( 'manage_options' ) ) {
                return;
            }
            $items_to_hide = isset( $this->options['hide_admin_menu_items'] ) ? $this->options['hide_admin_menu_items'] : [];
            if ( ! is_array( $items_to_hide ) ) {
                return;
            }
            foreach ( array_keys( $items_to_hide ) as $menu_slug ) {
                remove_menu_page( $menu_slug );
            }
        }
        
        public function remove_dashboard_widgets() {
            global $wp_meta_boxes;
            $widgets_to_remove = isset($this->options['hide_dashboard_widgets']) ? $this->options['hide_dashboard_widgets'] : [];
            if ( ! is_array( $widgets_to_remove ) ) {
                return;
            }
            $locations = [ 'normal', 'side', 'column3', 'column4' ];
            foreach ( $locations as $location ) {
                if ( isset( $wp_meta_boxes['dashboard'][$location]['core'] ) ) {
                    foreach ( $wp_meta_boxes['dashboard'][$location]['core'] as $widget_id => $widget_data ) {
                        if ( isset( $widgets_to_remove[$widget_id] ) && $widgets_to_remove[$widget_id] ) {
                            unset( $wp_meta_boxes['dashboard'][$location]['core'][$widget_id] );
                        }
                    }
                }
            }
        }
        
        public function remove_welcome_panel() {
            remove_action( 'welcome_panel', 'wp_welcome_panel' );
        }
        
        public function custom_admin_footer_text() {
            return wp_kses_post( $this->options['admin_footer_text'] );
        }

        public function custom_admin_version_text() {
            return wp_kses_post( $this->options['admin_version_text'] );
        }

        public function hide_help_tabs() {
            $screen = get_current_screen();
            $screen->remove_help_tabs();
        }

        public function hide_screen_options() {
            echo '<style>#screen-options-link-wrap { display: none; }</style>';
        }

        public function custom_admin_head_styles() {
            $admin_logo_url = ! empty( $this->options['admin_logo'] ) ? esc_url( $this->options['admin_logo'] ) : '';
            $custom_admin_css = ! empty( $this->options['custom_admin_css'] ) ? trim( $this->options['custom_admin_css'] ) : '';
    ?>
    <style type="text/css">
                <?php if ($admin_logo_url) : ?>
        #wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon::before {
                    content: '';
                    background-image: url(<?php echo $admin_logo_url; ?>) !important;
                    background-size: contain;
            background-position: center center;
            background-repeat: no-repeat;
                    width: 20px;
                    height: 20px;
            display: inline-block;
            vertical-align: middle;
                    margin-top: -2px;
                }
                <?php endif; ?>
                <?php
                if ( $custom_admin_css ) {
                    echo '/* Custom Admin CSS */' . "\n";
                    echo wp_strip_all_tags( $custom_admin_css );
                }
                ?>
    </style>
    <?php
}

        // Methods from Custom_Branding_General
        public function remove_password_strength() {
            if ( wp_script_is( 'wc-password-strength-meter', 'enqueued' ) ) {
                wp_dequeue_script( 'wc-password-strength-meter' );
            }
        }

        public function remove_admin_bar() {
            if (!current_user_can('administrator') && !is_admin()) {
                show_admin_bar(false);
            }
        }

        public function custom_from_name( $original_email_from ) {
            return esc_attr( $this->options['email_from_name'] );
        }

        public function custom_from_email( $original_email_address ) {
            return sanitize_email( $this->options['email_from_address'] );
        }

        // Methods from Custom_Branding_Login
        public function custom_login_styles() {
            $login_logo_url    = ! empty( $this->options['login_logo'] ) ? esc_url( $this->options['login_logo'] ) : '';
            $login_logo_width  = ! empty( $this->options['login_logo_width'] ) ? esc_attr( $this->options['login_logo_width'] ) : 'auto';
            $login_logo_height = ! empty( $this->options['login_logo_height'] ) ? esc_attr( $this->options['login_logo_height'] ) : 'auto';
            $login_bg_url      = ! empty( $this->options['login_bg'] ) ? esc_url( $this->options['login_bg'] ) : '';
            $custom_login_css  = ! empty( $this->options['custom_login_css'] ) ? trim( $this->options['custom_login_css'] ) : '';
            $form_bg_color = ! empty( $this->options['login_form_bg_color'] ) ? esc_attr( $this->options['login_form_bg_color'] ) : '';
            $form_text_color = ! empty( $this->options['login_form_text_color'] ) ? esc_attr( $this->options['login_form_text_color'] ) : '';
            $button_bg_color = ! empty( $this->options['login_button_bg_color'] ) ? esc_attr( $this->options['login_button_bg_color'] ) : '';
            $button_text_color = ! empty( $this->options['login_button_text_color'] ) ? esc_attr( $this->options['login_button_text_color'] ) : '';
    ?>
    <style type="text/css">
                .custom-login-footer-html {
                    text-align: center;
                }
                <?php if ( $login_logo_url ) : ?>
        body.login div#login h1 a {
                    background-image: url(<?php echo $login_logo_url; ?>);
                    width: <?php echo $login_logo_width; ?>;
                    height: <?php echo $login_logo_height; ?>;
                    background-size: contain;
            background-position: center center;
                    margin-bottom: 25px;
        }
                <?php endif; ?>
                <?php if ( $login_bg_url ) : ?>
        body.login {
                    background-image: url(<?php echo $login_bg_url; ?>) !important;
            background-size: cover !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
                    background-attachment: fixed;
                }
                <?php endif; ?>
                <?php if ( $form_bg_color ) : ?>
                #loginform {
                    background-color: <?php echo $form_bg_color; ?>;
                }
                <?php endif; ?>
                <?php if ( $form_text_color ) : ?>
                .login label, .login #nav a, .login #backtoblog a {
                    color: <?php echo $form_text_color; ?>;
                }
                <?php endif; ?>
                <?php if ( $button_bg_color ) : ?>
                .wp-core-ui .button-primary {
                    background: <?php echo $button_bg_color; ?> !important;
                    border-color: <?php echo $button_bg_color; ?> !important;
                }
                <?php endif; ?>
                <?php if ( $button_text_color ) : ?>
                .wp-core-ui .button-primary {
                    color: <?php echo $button_text_color; ?>;
                }
                <?php endif; ?>
                <?php
                if ( $custom_login_css ) {
                    echo '/* Custom Login CSS */' . "\n";
                    echo wp_strip_all_tags( $custom_login_css );
                }
                ?>
    </style>
    <?php
}

        public function login_logo_link() {
            if ( ! empty( $this->options['login_logo_link'] ) ) {
                return esc_url( $this->options['login_logo_link'] );
            }
            return home_url( '/' );
        }

        public function login_logo_sr_text() {
            return esc_attr( $this->options['login_logo_sr_text'] );
        }

        public function custom_login_footer_html() {
            echo '<div class="custom-login-footer-html">' . wp_kses_post( $this->options['login_custom_html'] ) . '</div>';
        }
    }
}
