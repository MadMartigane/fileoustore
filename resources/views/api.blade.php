<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FileouStore API</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2563EB;
            border-bottom: 2px solid #E5E7EB;
            padding-bottom: 10px;
        }
        h2 {
            color: #1D4ED8;
            margin-top: 30px;
        }
        .endpoint {
            background-color: #F3F4F6;
            border-left: 4px solid #2563EB;
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 0 4px 4px 0;
        }
        .method {
            font-weight: bold;
            color: #1D4ED8;
        }
        code {
            background-color: #E5E7EB;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
        }
        footer {
            margin-top: 50px;
            text-align: center;
            color: #6B7280;
            font-size: 0.9rem;
            border-top: 1px solid #E5E7EB;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <h1>FileouStore API</h1>
    <p>Welcome to the FileouStore API - a simple file storage API built with Laravel and SleekDB.</p>
    
    <h2>API Features</h2>
    <ul>
        <li>User management (registration, authentication, password recovery)</li>
        <li>File storage and management</li>
        <li>File sharing with permission control</li>
        <li>Admin and regular user roles</li>
    </ul>
    
    <h2>Getting Started</h2>
    <p>All API endpoints are available at <code>/api</code>. For detailed documentation, please refer to the API documentation.</p>
    
    <h2>Key Endpoints</h2>
    
    <div class="endpoint">
        <span class="method">POST</span> /api/register
    </div>
    <div class="endpoint">
        <span class="method">POST</span> /api/login
    </div>
    <div class="endpoint">
        <span class="method">GET</span> /api/files
    </div>
    <div class="endpoint">
        <span class="method">POST</span> /api/files
    </div>
    
    <h2>Authentication</h2>
    <p>All API requests (except registration and login) require authentication with a Bearer token.</p>
    <p>Example: <code>Authorization: Bearer your-token-here</code></p>
    
    <footer>
        &copy; FileouStore API - {{ date('Y') }}
    </footer>
</body>
</html>