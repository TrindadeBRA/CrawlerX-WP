<?php

/**
 * Plugin Name: CrawlerX WP
 * Plugin URI: https://lucastrindade.dev
 * Description:  O Plugin CrawlerX WP integra o CrawlerX ao WordPress, permitindo a postagem automática de conteúdos gerados. Com ele, você recebe uma API Key exclusiva, um URL base e documentação completa com Swagger, facilitando o acesso e uso das APIs. Simplifique a gestão de conteúdo no seu site WordPress com esta integração prática e segura.
 * Version: 1.0.0
 * Author: Lucas Trindade
 * Author URI: https://lucastrindade.dev
 */

if (!defined('ABSPATH')) {
    exit; // Evita acesso direto ao arquivo
}

// Definir constante do diretório do plugin
define('CRAWLERX_PLUGIN_DIR', plugin_dir_path(__FILE__));

// Incluir arquivos necessários
require_once CRAWLERX_PLUGIN_DIR . 'includes/api.php';
require_once CRAWLERX_PLUGIN_DIR . 'includes/helpers.php';
require_once CRAWLERX_PLUGIN_DIR . 'includes/swagger-page.php';

// Adicionar hook de ativação
register_activation_hook(__FILE__, 'crawlerx_generate_api_key');

// Criar menu no admin
function crawlerx_add_admin_menu()
{
    add_menu_page(
        'CrawlerX WP',
        'CrawlerX WP',
        'manage_options',
        'crawlerx_wp',
        'crawlerx_render_admin_page',
        'dashicons-admin-generic',
        20
    );
}
add_action('admin_menu', 'crawlerx_add_admin_menu');

// Adicionar scripts e estilos do Swagger UI
function crawlerx_enqueue_swagger_assets()
{
    if (isset($_GET['page']) && $_GET['page'] === 'crawlerx_wp') {
        wp_enqueue_style('swagger-ui', 'https://unpkg.com/swagger-ui-dist@5/swagger-ui.css');
        wp_enqueue_script('swagger-ui-bundle', 'https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js', [], false, true);
        wp_enqueue_script('swagger-ui-standalone', 'https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js', [], false, true);
    }
}
add_action('admin_enqueue_scripts', 'crawlerx_enqueue_swagger_assets');

// Adicionar a página do Swagger
function crawlerx_add_swagger_page()
{
    if (isset($_GET['crawlerx_swagger_ui'])) {
        require_once CRAWLERX_PLUGIN_DIR . 'includes/swagger-page.php';
        crawlerx_render_swagger_page();
    }
}
add_action('init', 'crawlerx_add_swagger_page');

// Modificar a função de renderização da página admin
function crawlerx_render_admin_page()
{
    $api_key = get_option('crawlerx_api_key', 'Não gerado');
    $swagger_url = add_query_arg('crawlerx_swagger_ui', '1', site_url());
    $api_base_url = site_url('/wp-json/crawlerx-api/v1/');
?>
    <div class="wrap">
        <h1>CrawlerX WP</h1>
        <div style="margin-bottom: 20px;">
            <h2>API Key</h2>
            <p>Use a seguinte API Key para autenticação:</p>
            <input type="text" value="<?php echo esc_attr($api_key); ?>" readonly style="width: 100%; max-width: 400px;" />
            <button onclick="copyApiKey()" class="button button-primary">Copiar API Key</button>

            <h3 style="margin-top: 20px;">URL Base da API:</h3>
            <input type="text" value="<?php echo esc_attr($api_base_url); ?>" readonly style="width: 100%; max-width: 400px;" />
            <button onclick="copyApiUrl()" class="button button-primary">Copiar URL da API</button>

            <div style="margin-top: 30px; background: #f9f9f9; padding: 20px; border-radius: 5px; border: 1px solid #ddd;">
                <h3>Configuração das Variáveis de Ambiente</h3>
                <p>Para configurar sua aplicação principal, utilize as seguintes variáveis de ambiente:</p>
                <pre style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 3px;">
# API KEY CrawlerX WP
CRAWLERX_WP_API_KEY=<?php echo esc_html($api_key); ?>

CRAWLERX_WP_API_URL=<?php echo esc_html($api_base_url); ?></pre>

                <h4>Descrição das Variáveis:</h4>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><strong>CRAWLERX_WP_API_KEY:</strong> Chave de autenticação necessária para todas as requisições à API</li>
                    <li><strong>CRAWLERX_WP_API_URL:</strong> URL base para todos os endpoints da API</li>
                </ul>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <h2>Documentação da API</h2>
            <a href="<?php echo esc_url($swagger_url); ?>" target="_blank" class="button button-secondary">
                Abrir Documentação
            </a>
        </div>

        <script>
            function copyApiKey() {
                var copyText = document.querySelectorAll('input')[0];
                copyText.select();
                document.execCommand("copy");
                alert("API Key copiada!");
            }

            function copyApiUrl() {
                var copyText = document.querySelectorAll('input')[1];
                copyText.select();
                document.execCommand("copy");
                alert("URL da API copiada!");
            }
        </script>
    </div>
<?php
}

class CrawlerX_API_Security
{
    private static $instance = null;

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function validate_api_key($request)
    {
        // Verifica múltiplos locais para a API key
        $api_key = $this->get_api_key_from_request($request);

        if (empty($api_key)) {
            return new WP_Error(
                'api_key_missing',
                'API Key não fornecida',
                array('status' => 401)
            );
        }

        $stored_key = get_option('crawlerx_api_key');

        if (empty($stored_key)) {
            return new WP_Error(
                'api_key_not_configured',
                'API Key não configurada no sistema',
                array('status' => 500)
            );
        }

        if (!hash_equals($stored_key, $api_key)) {
            return new WP_Error(
                'invalid_api_key',
                'API Key inválida',
                array('status' => 401)
            );
        }

        return true;
    }

    private function get_api_key_from_request($request)
    {
        // Tenta obter do cabeçalho X-API-Key
        $api_key = $request->get_header('X-API-Key');

        // Se não encontrar no cabeçalho, tenta nos parâmetros
        if (empty($api_key)) {
            $api_key = $request->get_param('api_key');
        }

        return $api_key;
    }
}
