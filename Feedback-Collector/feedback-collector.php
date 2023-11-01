<?php
/*
Plugin Name: Feedback Collector
Description: Collect feedback and save it as a custom post type.
Version: 1.0
Author: Your Name
*/

// Register custom post type for feedback
function register_feedback_post_type() {
    register_post_type('feedback', array(
        'public' => true,
        'label' => 'Feedback',
        'supports' => array('title', 'editor', 'custom-fields', 'thumbnail'),
    ));
}
add_action('init', 'register_feedback_post_type');

// Add a settings page for controlling image field
function feedback_collector_settings() {
    add_menu_page('Feedback Collector Settings', 'Feedback Settings', 'manage_options', 'feedback-collector-settings', 'feedback_collector_settings_page');
}
add_action('admin_menu', 'feedback_collector_settings');

function feedback_collector_settings_page() {
    // Create the settings page content here
}

// Add a shortcode for the feedback form
function feedback_form_shortcode() {
    ob_start();
    // Your form HTML here
    echo '<style>
        .feedback-form {
            max-width: 400px;
            margin: 0 auto;
        }
        .feedback-form input,
        .feedback-form textarea {
            width: 100%;
            padding: 10px;
            margin: 5px 0;
        }
        .feedback-form input[type="file"] {
            margin-top: 5px;
        }
    </style>';
    echo '<div class="feedback-form">';
    echo '<form method="post" enctype="multipart/form-data">';
    echo 'Name: <input type="text" name="feedback_name"><br>';
    echo 'Email: <input type="email" name="feedback_email"><br>';
    echo 'Description: <textarea name="feedback_description"></textarea><br>';
    echo 'Images: <input type="file" name="feedback_images[]" multiple><br>';
    echo '<input type="submit" value="Submit">';
    echo '</form>';
    echo '</div>';
    return ob_get_clean();
}
add_shortcode('feedback_form', 'feedback_form_shortcode');

// Handle form submission and save feedback
function save_feedback() {
    if (isset($_POST['feedback_name'])) {
        $feedback_data = array(
            'post_title' => sanitize_text_field($_POST['feedback_name']),
            'post_content' => sanitize_text_field($_POST['feedback_description']),
            'post_type' => 'feedback',
            'post_status' => 'publish',
        );

        $feedback_id = wp_insert_post($feedback_data);

        // Handle image uploads
        if (!empty($_FILES['feedback_images']['name'][0])) {
            $uploaded_images = feedback_handle_image_uploads($feedback_id);
            update_post_meta($feedback_id, '_feedback_images', $uploaded_images);
        }
    }
}
add_action('init', 'save_feedback');

// Handle image uploads
function feedback_handle_image_uploads($post_id) {
    $uploaded_images = array();

    $uploads_dir = wp_upload_dir();
    
    foreach ($_FILES['feedback_images']['name'] as $key => $image) {
        $image_name = sanitize_file_name($image);
        $image_tmp = $_FILES['feedback_images']['tmp_name'][$key];
        $image_path = $uploads_dir['path'] . '/' . $image_name;

        if (move_uploaded_file($image_tmp, $image_path)) {
            $uploaded_images[] = $uploads_dir['url'] . '/' . $image_name;
        }
    }

    return $uploaded_images;
}

// Display saved images in the backend (custom meta box)
function feedback_images_metabox() {
    add_meta_box('feedback_images', 'Feedback Images', 'display_feedback_images_metabox', 'feedback');
}
add_action('add_meta_boxes', 'feedback_images_metabox');

function display_feedback_images_metabox($post) {
    $images = get_post_meta($post->ID, '_feedback_images', true);

    echo '<div class="feedback-images">';
    if (!empty($images)) {
        foreach ($images as $image_url) {
            echo '<img src="' . $image_url . '" alt="Feedback Image">';
        }
    } else {
        echo 'No images uploaded.';
    }
    echo '</div>';
}
