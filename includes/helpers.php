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
