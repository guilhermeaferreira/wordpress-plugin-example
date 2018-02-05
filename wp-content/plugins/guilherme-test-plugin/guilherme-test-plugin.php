<?php

/*
Plugin Name: Guilherme Test Plugin
Plugin URI: http://localhost:8080
Description: Guilherme Test Plugin
Author: Guilherme Ferreira
Author URI: http://localhost:8080
Version: 1.0
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// getting the widgets
require_once dirname(__FILE__) . '/classes/widgets.php';

class Guilherme_Test_Plugin_Class {

    private static $instance;

    const POST_PREFIX = 'gtp_';

    // singleton module
    public static function getInstance()
    {
        if (self::$instance == NULL) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        // register post types
        add_action('init', [$this, 'Guilherme_Test_Plugin_Class::register_post_type']);

        // register taxonomies
        add_action('init', [$this, 'Guilherme_Test_Plugin_Class::register_taxonomies']);

        // metabox
        add_action('add_meta_boxes', [$this, 'add_custom_meta_boxes']);

        // bind save_post hook
        add_action('save_post', [$this, 'save_custom_meta_data']);

        // edit form tag
        add_action('post_edit_form_tag', [$this, 'update_edit_form']);

        // return custom column
        add_action('manage_' . self::POST_PREFIX . 'gallery' . '_posts_custom_column', [$this, 'custom_column'], 11, 2);

        // registering the widgets
        add_action('widgets_init', [$this, 'register_widgets']);

        // load custom template
        add_action('template_include', [$this, 'load_template']);

        // filter fixed taxonomies
        add_filter('get_terms', [$this, 'create_default_taxonomies_values'], 10, 3);

        // filter columns to display in the list
        add_filter('manage_' . self::POST_PREFIX . 'gallery' . '_posts_columns', [$this, 'filter_columns']);

    }

    /**
     * Register post type
     */
    static function register_post_type()
    {
        register_post_type(self::POST_PREFIX . 'gallery', [
            'labels' => [
               'name' => __('Test Gallery'),
               'singular_name' => __('Test Gallery'),
            ],
            'description' => __('Test Gallery for rehab'),
            'supports' => [
                'title', 'author'
            ],
            'public' => TRUE,
            'menu_icon' => 'dashicons-format-gallery',
            'menu_position' => 3
        ]);
    }

    /**
     * Register taxonomies
     */
    static function register_taxonomies()
    {
        register_taxonomy(self::POST_PREFIX . 'gallery_type', [self::POST_PREFIX . 'gallery'], [
            'labels' => [
               'name' => __('Gallery Types'),
               'singular_name' => __('Gallery Type'),
            ],
            'public' => TRUE,
            'hierarchical' => TRUE,
            'capabilities' => array(
                'manage_terms' => FALSE,
                'edit_terms'   => FALSE,
                'delete_terms' => FALSE,
                'assign_terms' => 'edit_posts'
            ),
        ]);
    }

    /**
     * Create the fixed taxonomies
     *
     * @param $terms
     * @param $taxonomies
     * @param $args
     * @return array|int|WP_Error
     */
    function create_default_taxonomies_values($terms, $taxonomies, $args)
    {
        if ($args['get'] != 'all') return $terms;
        if (empty($taxonomies[0]) || $taxonomies[0] != self::POST_PREFIX . 'gallery_type') return $terms;

        // default values here:
        $default_values = array('People', 'Cats', 'Other stuff');

        foreach ($default_values as $value) {
            if (!term_exists($value, self::POST_PREFIX . 'gallery_type')) {
                wp_insert_term($value, self::POST_PREFIX . 'gallery_type');
                return get_terms($taxonomies, $args);
            }
        }

        return $terms;
    }

    /**
     * Columns to display
     *
     * @param $columns
     * @return array
     */
    function filter_columns($columns)
    {
        $columns['author'] = 'Author/Owner';
        $columns['title']  = 'Image Name';
        $columns['image']  = 'Image';
        return $columns;
    }

    /**
     * Format custom columns
     *
     * @param $column
     * @param $post
     */
    function custom_column($column, $post)
    {
        switch ($column) {
            case 'image':
                $image = get_post_meta($post, self::POST_PREFIX . 'image', true);
                if (empty($image)) {
                    echo 'No image yet';
                    break;
                }

                echo sprintf('<img src="%s" width="50" height="50"/>', $image['url']);
                break;
        }
    }

    /**
     * Add meta boxes
     */
    function add_custom_meta_boxes()
    {
        add_meta_box(
            self::POST_PREFIX . 'image',
            'Image File',
            [$this, 'markup_metabox_image'],
            self::POST_PREFIX . 'gallery',
            'normal'
        );

        add_meta_box(
            self::POST_PREFIX . 'visibility',
            'Visibility',
            [$this, 'markup_metabox_visibility'],
            self::POST_PREFIX . 'gallery',
            'normal'
        );

    }

    /**
     * Display metabox for image
     *
     * @param $post
     */
    function markup_metabox_image($post)
    {
        wp_nonce_field(plugin_basename(__FILE__), 'image_nonce');

        $image = get_post_meta( $post->ID, self::POST_PREFIX . 'image', true);

        $html = '<p class="description">';
        $html .= 'Select your image file.';
        $html .= '</p>';
        $html .= sprintf(
            '<input type="file" id="%s" name="%s" value="" size="25" />',
            self::POST_PREFIX . 'image',
            self::POST_PREFIX . 'image'
        );

        if (!empty($image)) {
            $html .= '<p>';
            $html .= 'Current Image: <br/>';
            $html .= sprintf('<img src="%s" width="150" height="150" />', $image['url']);
            $html .= '</p>';
        }

        echo $html;
    }

    /**
     * Display metabox for visibility
     *
     * @param $post
     */
    function markup_metabox_visibility($post)
    {
        wp_nonce_field(plugin_basename(__FILE__), 'visibility_nonce');

        $visibility = get_post_meta( $post->ID, self::POST_PREFIX . 'visibility', true);

        $html = '<p class="description">';
        $html .= 'Select the visibility.';
        $html .= '</p>';
        $html .= sprintf(
            '<select id="%s" name="%s" value="">',
            self::POST_PREFIX . 'visibility',
            self::POST_PREFIX . 'visibility'
        );

        foreach (['public', 'private'] as $visibility_types) {
            $html .= sprintf('<option value="%s" %s>%s</option>', $visibility_types, (($visibility==$visibility_types)?'selected':''), $visibility_types);
        }

        $html .= '</select>';

        echo $html;
    }

    /**
     * Save custom fields
     *
     * @param $post
     * @return mixed
     */
    function save_custom_meta_data($post)
    {
        if (!wp_verify_nonce($_POST['image_nonce'], plugin_basename(__FILE__))) {
            return $post;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post;
        }

        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post)) {
                return $post;
            }
        } else {
            if (!current_user_can('edit_page', $post)) {
                return $post;
            }
        }

        $field_image = $_FILES[self::POST_PREFIX . 'image'];
        if (!empty($field_image['name'])) {
            self::save_image($field_image, $post);
        }

        // save visibility
        self::save_visibility($_POST[self::POST_PREFIX . 'visibility'], $post);
    }

    /**
     * Save image
     *
     * @param $field_image
     * @param $post
     */
    static function save_image($field_image, $post)
    {
        if (empty($field_image)) {
            return;
        }

        $supported_types = [
            'image/gif',
            'image/png',
            'image/jpeg',
            'image/bmp',
            'image/webp'
        ];

        $arr_file_type = wp_check_filetype(basename($field_image['name']));
        $uploaded_type = $arr_file_type['type'];

        if(in_array($uploaded_type, $supported_types)) {

            self::check_image_size($field_image);

            // Use the WordPress API to upload the file
            $upload = wp_upload_bits($field_image['name'], null, file_get_contents($field_image['tmp_name']));

            if (isset($upload['error']) && $upload['error'] != 0) {
                wp_die('There was an error uploading your file. The error is: ' . $upload['error']);

            } else {
                add_post_meta($post, self::POST_PREFIX . 'image', $upload);
                update_post_meta($post, self::POST_PREFIX . 'image', $upload);

            }

        } else {
            wp_die("The file type that you've uploaded is not a valid image.");

        }
    }

    /**
     * Check the image size
     *
     * @param $field_image
     */
    static function check_image_size($field_image)
    {
        $size = filesize($field_image['tmp_name']);
        if (($size/1024) > 50) {
            wp_die("The image size is bigger than 50kb.");
        }
    }

    /**
     * Save visibility
     *
     * @param $field_visibility
     * @param $post
     */
    static function save_visibility($field_visibility, $post)
    {
        if (empty($field_visibility) || !in_array($field_visibility, ['public', 'private'])) {
            return;
        }

        add_post_meta($post, self::POST_PREFIX . 'visibility', $field_visibility);
        update_post_meta($post, self::POST_PREFIX . 'visibility', $field_visibility);
    }

    function update_edit_form()
    {
        echo ' enctype="multipart/form-data"';
    }

    /**
     * @see widgets in classes/widgets.php
     */
    function register_widgets()
    {
        register_widget('Guilherme_Test_Plugin_Widget_Recent_Posts');
    }

    /**
     * Load a custom template
     */
    function load_template($template)
    {
        if (get_post_type() === self::POST_PREFIX . 'gallery') {
            return plugin_dir_path(__FILE__) . '/template/custom-template.php';
        }

        return $template;
    }

    /**
     * When the module is activated
     */
    static function activate()
    {
        self::register_post_type();
        self::register_taxonomies();
        flush_rewrite_rules();
    }
}

Guilherme_Test_Plugin_Class::getInstance();

register_deactivation_hook( __FILE__ , 'flush_rewrite_rules' );
register_activation_hook( __FILE__ , 'Guilherme_Test_Plugin_Class::activate' );
