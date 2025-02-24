<?php

if (!defined('ABSPATH')) {
    exit;
}

// Criar um post via API
function crawlerx_create_post($request) {
    $validation = crawlerx_validate_api_key($request);
    if (is_wp_error($validation)) return $validation;

    $title = sanitize_text_field($request['title']);
    $content = $request['content'];
    $excerpt = sanitize_textarea_field($request['excerpt']);

    if (empty($title)) {
        return new WP_Error('missing_title', 'O título é obrigatório.', ['status' => 400]);
    }

    if (empty($content)) {
        return new WP_Error('missing_content', 'O conteúdo é obrigatório.', ['status' => 400]);
    }

    if (empty($excerpt)) {
        return new WP_Error('missing_excerpt', 'O resumo é obrigatório.', ['status' => 400]);
    }

    $post_id = wp_insert_post([
        'post_title'   => $title,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_status'  => 'publish',
        'post_type'    => 'post',
        'post_author'  => 2
    ]);

    wp_set_post_categories($post_id, [102]);

    $post = get_post($post_id);
    
    return rest_ensure_response([
        'wp_post_id' => $post_id,
        'wp_slug' => $post->post_name,
    ]);
}

// Upload de imagem via URL e definir como imagem destacada
function crawlerx_upload_image($request) {
    $validation = crawlerx_validate_api_key($request);
    if (is_wp_error($validation)) return $validation;

    $post_id = intval($request['post_id']);
    $image_base64 = $request['image_base64'];
    $title = $request['title'];

    if (!get_post($post_id)) {
        return new WP_Error('invalid_post', 'Post ID inválido.', ['status' => 400]);
    }

    if (empty($image_base64) || empty($title)) {
        return new WP_Error('invalid_params', 'Imagem base64 ou título não fornecidos.', ['status' => 400]);
    }

    // Decodifica a imagem base64
    $image_data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image_base64));
    
    if (!$image_data) {
        return new WP_Error('invalid_base64', 'Dados base64 inválidos.', ['status' => 400]);
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $upload_dir = wp_upload_dir();
    $sanitized_title = sanitize_title($title);
    $image_name = $sanitized_title . '_crawlerx_' . wp_generate_password(12, false, false) . '.png';
    $image_path = $upload_dir['path'] . '/' . $image_name;
    file_put_contents($image_path, $image_data);

    $attachment = [
        'tmp_name' => $image_path,
        'name' => $image_name
    ];

    $attachment_id = media_handle_sideload($attachment, $post_id);

    if (is_wp_error($attachment_id)) {
        return new WP_Error('upload_failed', 'Falha ao processar a imagem.', ['status' => 500]);
    }

    // Atualiza o caption da imagem com o título original
    wp_update_post([
        'ID' => $attachment_id,
        'post_excerpt' => $title
    ]);

    set_post_thumbnail($post_id, $attachment_id);

    $image_url = wp_get_attachment_url($attachment_id);

    return rest_ensure_response([
        'wp_image_id' => $attachment_id,
        'wp_image_url' => $image_url,
        'wp_post_id' => $post_id
    ]);
}

// Registrar rotas
function crawlerx_register_api_routes() {
    register_rest_route('crawlerx-api/v1', '/create-post', [
        'methods'  => 'POST',
        'callback' => 'crawlerx_create_post',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('crawlerx-api/v1', '/upload-image', [
        'methods'  => 'POST',
        'callback' => 'crawlerx_upload_image',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'crawlerx_register_api_routes');

// Adicionar rota para servir o arquivo swagger.json
function crawlerx_serve_swagger_json() {
    if (isset($_GET['crawlerx_swagger'])) {
        header('Content-Type: application/json');
        echo file_get_contents(CRAWLERX_PLUGIN_DIR . 'includes/swagger.json');
        exit;
    }
}
add_action('init', 'crawlerx_serve_swagger_json');
