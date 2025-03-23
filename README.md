# FileouStore - Laravel File Storage API with SleekDB

FileouStore is a simple PHP API for storing and managing files, built with Laravel and using SleekDB as the database.

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
- Create necessary directories for SleekDB
- Set proper permissions
- Create symbolic link for storage

3. Copy the environment file and set your configuration:

```bash
cp .env.example .env
php artisan key:generate
```

4. Seed the database with an admin user:

```bash
composer seed
```

5. Start the development server:

```bash
composer start
```

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

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).