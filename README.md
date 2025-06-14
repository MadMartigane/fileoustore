# FileouStore - Laravel File Storage API with SQLite

FileouStore is a simple PHP API for storing and managing files, built with Laravel and using SQLite as the database.

## Features

- **User Management**: Registration, login, password recovery
- **File Operations**: Upload, download, update, delete files
- **Permissions**: Share files with other users with specific permissions (read, write, delete)
- **Admin Access**: Admin users have full access to all files

## Requirements

- PHP 8.2+
- Composer
- Laravel 12+

## Installation

1. Clone the repository:

```bash
git clone https://github.com/yourusername/fileoustore.git
cd fileoustore
```

2. Install dependencies:

```bash
composer install
```

The post-install script will:
- Create necessary directories
- Set proper permissions
- Create symbolic link for storage

3. Copy the environment file and set your configuration:

```bash
cp .env.example .env
php artisan key:generate
```

4. Run the migrations to set up the SQLite database:

```bash
php artisan migrate
```

5. Seed the database with an admin user:

```bash
composer seed
```

6. Start the development server:

```bash
composer start
```

## Migration from SleekDB

If you previously used a version of FileouStore that used SleekDB for storage, you can migrate your data to SQLite with:

```bash
# First make sure you have SQLite tables set up
php artisan migrate

# Then run the migration command
php artisan migrate:from-sleekdb
```

This will:
1. Migrate users from SleekDB to SQLite
2. Migrate file records from SleekDB to SQLite
3. Migrate password reset tokens

Note: The actual file content stored on disk will not be moved, only the database records.

## API Endpoints

### Authentication

- `POST /api/register` - Register a new user
- `POST /api/login` - Login and get API token
- `POST /api/logout` - Logout (requires authentication)
- `POST /api/forgot-password` - Request password reset
- `POST /api/reset-password` - Reset password with token

### Users

- `GET /api/users` - List all users (admin only)
- `POST /api/users` - Create a user (admin only)
- `GET /api/users/{id}` - Get user details
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user (admin only)

### Files

- `GET /api/files` - List all user's files
- `POST /api/files` - Upload a new file
- `GET /api/files/{id}` - Get file details
- `GET /api/files/{id}/download` - Download a file
- `PUT /api/files/{id}` - Update file metadata
- `DELETE /api/files/{id}` - Delete a file
- `POST /api/files/{id}/share` - Share a file with another user
- `DELETE /api/files/{id}/share/{userId}` - Remove file sharing

## Usage Examples

### Upload a File

```bash
curl -X POST -H "Authorization: Bearer {your_token}" -F "file=@/path/to/file.jpg" http://localhost:8000/api/files
```

### Share a File

```bash
curl -X POST -H "Authorization: Bearer {your_token}" -H "Content-Type: application/json" -d '{"user_id": "user_123", "permissions": ["read", "write"]}' http://localhost:8000/api/files/{file_id}/share
```

### List User's Files

```bash
curl -X GET -H "Authorization: Bearer {your_token}" http://localhost:8000/api/files
```

## API Documentation

For more detailed API documentation, see [API_DOCUMENTATION.md](API_DOCUMENTATION.md).

## Testing

To run the tests:

```bash
composer test
```

## Deployment

Deploying FileouStore to a production environment requires a few additional steps to ensure security and performance.

### Server Requirements

- **Web Server**: Nginx or Apache is recommended.
- **PHP**: Ensure your production server has the correct PHP version installed (as specified in `composer.json`) with necessary extensions (e.g., `pdo_sqlite`, `mbstring`, `openssl`, `tokenizer`, `xml`).
- **Composer**: For installing dependencies.

### Environment Configuration

1.  **`.env` File**:
    *   Ensure your `.env` file is correctly configured for your production environment.
    *   **CRITICAL**: Set `APP_ENV=production` and `APP_DEBUG=false`.
    *   Configure `APP_URL` to your production domain.
    *   Set up your production database connection details (even if it's SQLite, ensure the path is correct and writable by the web server).
    *   Generate a new `APP_KEY` specifically for the production environment using `php artisan key:generate`. **Do not use your development key.**

2.  **Permissions**:
    *   Ensure the web server has write permissions to the `storage` and `bootstrap/cache` directories.
    *   Example: `sudo chown -R www-data:www-data storage bootstrap/cache`
    *   Example: `sudo chmod -R 775 storage bootstrap/cache`

### Optimization

Laravel provides several commands to optimize your application for production:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache # If you are using events extensively
```

It's good practice to run these commands as part of your deployment script. To clear these caches during an update, you can use:

```bash
php artisan optimize:clear
```

Then re-run the caching commands.

### Web Server Configuration

**Nginx Example:**

A basic Nginx configuration might look like this (ensure you adapt it to your server setup):

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/your/fileoustore/public; # Point to the public directory

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.x-fpm.sock; # Adjust to your PHP-FPM version
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Apache Example:**

Ensure `mod_rewrite` is enabled. Your `.htaccess` file in the `public` directory (Laravel includes one by default) should handle most of the work. You might need to configure your Apache VirtualHost:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /path/to/your/fileoustore/public

    <Directory /path/to/your/fileoustore/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

### Running the Application

- **Queue Worker (If Applicable)**: If your application uses queues for background tasks (e.g., sending emails, processing jobs), you'll need to set up a process manager like Supervisor to keep the queue worker running:
  `php artisan queue:work --daemon`

  Example Supervisor configuration (`/etc/supervisor/conf.d/fileoustore-worker.conf`):

  ```ini
  [program:fileoustore-worker]
  process_name=%(program_name)s_%(process_num)02d
  command=php /path/to/your/fileoustore/artisan queue:work --sleep=3 --tries=3 --daemon
  autostart=true
  autorestart=true
  user=your_server_user ; or www-data
  numprocs=1 ; Adjust as needed
  redirect_stderr=true
  stdout_logfile=/path/to/your/fileoustore/storage/logs/worker.log
  stopwaitsecs=3600
  ```
  Remember to run `sudo supervisorctl reread` and `sudo supervisorctl update` after creating/modifying the configuration.

- **Task Scheduling**: If you have scheduled tasks defined in `app/Console/Kernel.php`, set up a cron job to run the Laravel scheduler once per minute:

  ```cron
  * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
  ```

### Security Considerations

*   **HTTPS**: Always use HTTPS in production. Configure SSL certificates (e.g., using Let's Encrypt).
*   **Permissions**: Be strict with file and directory permissions. The web server should only have write access to directories it absolutely needs (e.g., `storage`, `bootstrap/cache`).
*   **Disable Directory Listing**: Ensure your web server configuration prevents directory listing.
*   **Regular Updates**: Keep your server, PHP, Laravel, and other dependencies updated with security patches.
*   **Backup**: Implement a regular backup strategy for your files and database.

This section provides a general guide. Specific deployment steps may vary based on your hosting provider and server setup.

## Storage Configuration

Files are stored in the `storage/app/files` directory by default. This can be configured in the `config/filesystems.php` file.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).