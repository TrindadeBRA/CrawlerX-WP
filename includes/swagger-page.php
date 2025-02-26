<?php
if (!defined('ABSPATH')) {
    exit;
}

function crawlerx_render_swagger_page()
{
?>
    <!DOCTYPE html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8">
        <title>CrawlerX API - Documentação</title>
        <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
        <style>
            body {
                margin: 0;
                padding: 0;
            }

            #swagger-ui {
                max-width: 1460px;
                margin: 0 auto;
                padding: 20px;
            }
        </style>
    </head>

    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
        <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
        <script>
            window.onload = function() {
                window.ui = SwaggerUIBundle({
                    url: '<?php echo add_query_arg('crawlerx_swagger', '1', site_url()); ?>',
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIStandalonePreset
                    ],
                    plugins: [
                        SwaggerUIBundle.plugins.DownloadUrl
                    ],
                });
            };
        </script>
    </body>

    </html>
<?php
    exit;
}
