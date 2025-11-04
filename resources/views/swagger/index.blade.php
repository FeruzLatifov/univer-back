<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.0/swagger-ui.css">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .swagger-ui .topbar {
            background-color: #2563eb;
        }
        .swagger-ui .topbar .download-url-wrapper {
            display: none;
        }
        .custom-header {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .custom-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .custom-header p {
            margin: 8px 0 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .info-bar {
            background: #f8fafc;
            padding: 12px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .info-bar .version {
            color: #64748b;
            font-size: 14px;
        }
        .info-bar a {
            color: #2563eb;
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .info-bar a:hover {
            background: #dbeafe;
        }
    </style>
</head>
<body>
    <div class="custom-header">
        <h1>HEMIS University - API Documentation</h1>
        <p>Complete RESTful API Documentation - Single Source of Truth</p>
    </div>

    <div class="info-bar">
        <div class="version">
            Version 1.0.0 | OpenAPI 3.0.3 | Permission-based Access Control
        </div>
        <a href="/">🏠 Home</a>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.0/swagger-ui-bundle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.10.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            const ui = SwaggerUIBundle({
                url: "{{ $yamlUrl }}",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 1,
                docExpansion: "list",
                filter: true,
                showExtensions: true,
                showCommonExtensions: true,
                syntaxHighlight: {
                    activate: true,
                    theme: "monokai"
                },
                tryItOutEnabled: true,
                requestInterceptor: (request) => {
                    // Add custom headers if needed
                    return request;
                },
                onComplete: () => {
                    console.log('Swagger UI loaded successfully');
                }
            });

            window.ui = ui;
        }
    </script>
</body>
</html>
