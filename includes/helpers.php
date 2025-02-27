<?php

if (!defined('ABSPATH')) {
    exit;
}

// Função para gerar API Key na ativação do plugin
function crawlerx_generate_api_key()
{
    if (!get_option('crawlerx_api_key')) {
        $api_key = wp_generate_password(32, false);
        update_option('crawlerx_api_key', $api_key);
    }
}
register_activation_hook(CRAWLERX_PLUGIN_DIR . 'crawlerx-wp.php', 'crawlerx_generate_api_key');

// Função para validar API Key
function crawlerx_validate_api_key($request)
{
    $security = CrawlerX_API_Security::get_instance();
    return $security->validate_api_key($request);
}


/**
 * Process image watermark
 * 
 * @param int $attachment_id The attachment ID
 * @param string|null $watermark_url Optional watermark image URL
 * @return void
 */
function process_image_watermark($attachment_id, $watermark_url = null) {
    // Check if post is an attachment
    if (get_post_type($attachment_id) != 'attachment') {
        error_log("Not an image attachment. Attachment ID: $attachment_id");
        return;
    }

    // Get the file path of the image
    $file = get_attached_file($attachment_id);

    // Check if filename already contains "_watermarked_" to avoid loops
    if (strpos($file, '_watermarked_') !== false) {
        error_log("Image is already a watermarked version. File path: $file");
        return;
    }

    // Check if image was already processed to avoid loops
    $is_processed = get_post_meta($attachment_id, '_image_watermark_processed', true);
    if ($is_processed) {
        error_log("Image already processed. Attachment ID: $attachment_id");
        return;
    }

    $file_type = wp_check_filetype($file);

    // Check if file is an image
    if (strpos($file_type['type'], 'image') === false) {
        error_log("File is not an image. File path: $file");
        return;
    }

    // Load original image based on file type
    $background_image = load_image_by_type($file, $file_type['ext']);
    if (!$background_image) {
        error_log("Failed to load background image. File path: $file");
        return;
    }

    // Default watermark URL if none provided
    if (empty($watermark_url)) {
        $watermark_url = 'https://conteudo.thetrinityweb.com.br/wp-content/uploads/2024/10/TrinityLogo.jpg';
    }

    // Load watermark image
    $watermark_image = load_watermark_image($watermark_url);
    if (!$watermark_image) {
        error_log("Failed to load watermark image. URL: $watermark_url");
        return;
    }

    // Get dimensions
    $bg_width = imagesx($background_image);
    $bg_height = imagesy($background_image);
    $wm_width = imagesx($watermark_image);
    $wm_height = imagesy($watermark_image);

    // Resize watermark image
    $resized_watermark = imagecreatetruecolor($bg_width, $bg_height);
    imagecopyresampled($resized_watermark, $watermark_image, 0, 0, 0, 0, $bg_width, $bg_height, $wm_width, $wm_height);

    // Apply opacity
    $opacity = 80;
    imagecopymerge($background_image, $resized_watermark, 0, 0, 0, 0, $bg_width, $bg_height, $opacity);

    // Generate new filename
    $file_name = pathinfo($file, PATHINFO_FILENAME);
    $file_extension = pathinfo($file, PATHINFO_EXTENSION);
    $timestamp = time();
    $new_filename = $file_name . '_watermarked_' . $timestamp . '.' . $file_extension;

    // Set new image path
    $upload_dir = wp_upload_dir();
    $new_path = $upload_dir['path'] . '/' . $new_filename;

    // Save new image based on file type
    save_image_by_type($background_image, $new_path, $file_type['ext']);

    // Get the last published post
    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => 1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    $latest_post = get_posts($args);

    if (!empty($latest_post)) {
        $latest_post_id = $latest_post[0]->ID;
        $latest_post_title = get_the_title($latest_post_id);
    } else {
        $latest_post_title = 'Watermarked Image';
    }

    // Create attachment for new image
    $attachment = array(
        'guid'           => $upload_dir['url'] . '/' . $new_filename,
        'post_mime_type' => $file_type['type'],
        'post_title'     => $latest_post_title,
        'post_content'   => '',
        'post_status'    => 'inherit',
    );

    // Insert new image into media library
    $new_attachment_id = wp_insert_attachment($attachment, $new_path);
    if ($new_attachment_id) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($new_attachment_id, $new_path);
        wp_update_attachment_metadata($new_attachment_id, $attach_data);

        // Replace the original image with the watermarked version
        if (!empty($latest_post)) {
            // Update featured image to new watermarked version
            set_post_thumbnail($latest_post_id, $new_attachment_id);
        }

        // Delete the original image
        wp_delete_attachment($attachment_id, true);
        
        // Retornar o ID do novo anexo
        return $new_attachment_id;
    }

    // Clean up resources
    imagedestroy($background_image);
    imagedestroy($watermark_image);
    imagedestroy($resized_watermark);

    return $attachment_id; // Retornar o ID original se não houver nova imagem
}

/**
 * Load image based on file type
 * 
 * @param string $file_path Path to the image file
 * @param string $extension File extension
 * @return resource|false
 */
function load_image_by_type($file_path, $extension) {
    switch(strtolower($extension)) {
        case 'jpg':
        case 'jpeg':
            return imagecreatefromjpeg($file_path);
        case 'png':
            return imagecreatefrompng($file_path);
        case 'gif':
            return imagecreatefromgif($file_path);
        default:
            return false;
    }
}

/**
 * Load watermark image from URL
 * 
 * @param string $url URL of the watermark image
 * @return resource|false
 */
function load_watermark_image($url) {
    $extension = pathinfo($url, PATHINFO_EXTENSION);
    
    // Get image content from URL
    $image_data = file_get_contents($url);
    if (!$image_data) {
        return false;
    }
    
    // Create image from string
    return imagecreatefromstring($image_data);
}

/**
 * Save image based on file type
 * 
 * @param resource $image Image resource
 * @param string $path Path to save the image
 * @param string $extension File extension
 * @return bool
 */
function save_image_by_type($image, $path, $extension) {
    switch(strtolower($extension)) {
        case 'jpg':
        case 'jpeg':
            return imagejpeg($image, $path);
        case 'png':
            return imagepng($image, $path);
        case 'gif':
            return imagegif($image, $path);
        default:
            return false;
    }
}
