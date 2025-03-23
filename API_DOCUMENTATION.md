# FileouStore API Documentation

This document provides detailed information about the FileouStore API endpoints and how to use them.

## Authentication

All API requests (except registration, login, and password reset) require authentication using a bearer token.

To authenticate, include the following header in your requests:
```
Authorization: Bearer {your_token}
```

### Register a New User

```
POST /api/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "user": {
    "id": "user_6123871623",
    "name": "John Doe",
    "email": "john@example.com",
    "isAdmin": false,
    "createdAt": "2023-04-10 12:00:00",
    "updatedAt": "2023-04-10 12:00:00"
  }
}
```

### Login

```
POST /api/login
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "user": {
    "id": "user_6123871623",
    "name": "John Doe",
    "email": "john@example.com",
    "isAdmin": false,
    "createdAt": "2023-04-10 12:00:00",
    "updatedAt": "2023-04-10 12:00:00"
  },
  "token": "your-api-token"
}
```

### Logout

```
POST /api/logout
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

### Forgot Password

```
POST /api/forgot-password
```

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Response:**
```json
{
  "message": "Password reset link sent",
  "token": "reset-token" // In production, this would be sent via email
}
```

### Reset Password

```
POST /api/reset-password
```

**Request Body:**
```json
{
  "email": "john@example.com",
  "token": "reset-token",
  "password": "new-password123",
  "password_confirmation": "new-password123"
}
```

**Response:**
```json
{
  "message": "Password reset successfully"
}
```

## User Management

### List All Users (Admin Only)

```
GET /api/users
```

**Response:**
```json
{
  "users": [
    {
      "id": "user_6123871623",
      "name": "John Doe",
      "email": "john@example.com",
      "isAdmin": false,
      "createdAt": "2023-04-10 12:00:00",
      "updatedAt": "2023-04-10 12:00:00"
    },
    {
      "id": "user_7123871624",
      "name": "Admin User",
      "email": "admin@example.com",
      "isAdmin": true,
      "createdAt": "2023-04-10 12:00:00",
      "updatedAt": "2023-04-10 12:00:00"
    }
  ]
}
```

### Create a User (Admin Only)

```
POST /api/users
```

**Request Body:**
```json
{
  "name": "New User",
  "email": "newuser@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "is_admin": false
}
```

**Response:**
```json
{
  "user": {
    "id": "user_8123871625",
    "name": "New User",
    "email": "newuser@example.com",
    "isAdmin": false,
    "createdAt": "2023-04-10 12:00:00",
    "updatedAt": "2023-04-10 12:00:00"
  }
}
```

### Get User Details

```
GET /api/users/{user}
```

> Note: Regular users can only view their own profile. Admins can view any user's profile.

**Response:**
```json
{
  "user": {
    "id": "user_6123871623",
    "name": "John Doe",
    "email": "john@example.com",
    "isAdmin": false,
    "createdAt": "2023-04-10 12:00:00",
    "updatedAt": "2023-04-10 12:00:00"
  }
}
```

### Update User

```
PUT /api/users/{user}
```

> Note: Regular users can only update their own profile. Admins can update any user.

**Request Body:**
```json
{
  "name": "John Updated",
  "email": "john.updated@example.com",
  "password": "new-password123",
  "password_confirmation": "new-password123"
}
```

**Response:**
```json
{
  "user": {
    "id": "user_6123871623",
    "name": "John Updated",
    "email": "john.updated@example.com",
    "isAdmin": false,
    "createdAt": "2023-04-10 12:00:00",
    "updatedAt": "2023-04-10 14:00:00"
  }
}
```

### Delete User (Admin Only)

```
DELETE /api/users/{user}
```

**Response:**
```json
{
  "message": "User deleted successfully"
}
```

## File Management

### List User's Files

```
GET /api/files
```

**Response:**
```json
{
  "ownedFiles": [
    {
      "id": "file_9123871626",
      "name": "document.pdf",
      "path": "files/document_9123871626.pdf",
      "mimeType": "application/pdf",
      "size": 1024567,
      "ownerId": "user_6123871623",
      "sharedWith": {
        "user_7123871624": ["read", "write"]
      },
      "createdAt": "2023-04-10 12:00:00",
      "updatedAt": "2023-04-10 12:00:00"
    }
  ],
  "sharedFiles": [
    {
      "id": "file_8123871625",
      "name": "shared-document.docx",
      "path": "files/shared-document_8123871625.docx",
      "mimeType": "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
      "size": 2048567,
      "ownerId": "user_7123871624",
      "sharedWith": {
        "user_6123871623": ["read"]
      },
      "createdAt": "2023-04-10 12:00:00",
      "updatedAt": "2023-04-10 12:00:00"
    }
  ]
}
```

### Upload a File

```
POST /api/files
```

This endpoint accepts multipart/form-data.

**Request:**
- Form field: `file` (the file to upload)

**Response:**
```json
{
  "id": "file_9123871626",
  "name": "document.pdf",
  "path": "files/document_9123871626.pdf",
  "mimeType": "application/pdf",
  "size": 1024567,
  "ownerId": "user_6123871623",
  "sharedWith": {},
  "createdAt": "2023-04-10 12:00:00",
  "updatedAt": "2023-04-10 12:00:00"
}
```

### Get File Details

```
GET /api/files/{file}
```

> Note: Users can only view files they own or have been shared with. Admins can view any file.

**Response:**
```json
{
  "id": "file_9123871626",
  "name": "document.pdf",
  "path": "files/document_9123871626.pdf",
  "mimeType": "application/pdf",
  "size": 1024567,
  "ownerId": "user_6123871623",
  "sharedWith": {
    "user_7123871624": ["read", "write"]
  },
  "createdAt": "2023-04-10 12:00:00",
  "updatedAt": "2023-04-10 12:00:00"
}
```

### Download a File

```
GET /api/files/{id}/download
```

> Note: Users can only download files they own or have been shared with. Admins can download any file.

**Response:**
The file will be downloaded with appropriate headers.

### Update File Metadata

```
PUT /api/files/{file}
```

> Note: Users can only update files they own or have write permission for. Admins can update any file.

**Request Body:**
```json
{
  "name": "renamed-document.pdf"
}
```

**Response:**
```json
{
  "id": "file_9123871626",
  "name": "renamed-document.pdf",
  "path": "files/document_9123871626.pdf",
  "mimeType": "application/pdf",
  "size": 1024567,
  "ownerId": "user_6123871623",
  "sharedWith": {
    "user_7123871624": ["read", "write"]
  },
  "createdAt": "2023-04-10 12:00:00",
  "updatedAt": "2023-04-10 14:00:00"
}
```

### Delete a File

```
DELETE /api/files/{file}
```

> Note: Users can only delete files they own or have delete permission for. Admins can delete any file.

**Response:**
```json
{
  "message": "File deleted successfully"
}
```

### Share a File with a User

```
POST /api/files/{id}/share
```

> Note: Only the file owner or admin can share files.

**Request Body:**
```json
{
  "user_id": "user_8123871625",
  "permissions": ["read", "write", "delete"]
}
```

**Response:**
```json
{
  "message": "File shared successfully"
}
```

### Remove File Sharing

```
DELETE /api/files/{id}/share/{userId}
```

> Note: Only the file owner or admin can remove file sharing.

**Response:**
```json
{
  "message": "Sharing removed successfully"
}
```

## Test Endpoint

### Test API Connection

```
GET /api/test
```

**Response:**
```json
{
  "message": "API is working!"
}
```

## Error Responses

The API returns appropriate HTTP status codes along with error messages.

### Common Error Responses

#### Validation Error (422)
```json
{
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

#### Unauthorized (401)
```json
{
  "error": "Unauthenticated",
  "message": "You need to be logged in to access this resource"
}
```

#### Forbidden (403)
```json
{
  "message": "Unauthorized"
}
```

#### Not Found (404)
```json
{
  "message": "File not found"
}
```

#### Server Error (500)
```json
{
  "message": "Failed to perform operation",
  "debugTrace": "Error details..."
}
```

## Storage Implementation Note

This API uses SQLite as the database storage engine. All user data, file metadata, and authentication tokens are stored in the SQLite database. The physical files are stored on the server's file system and referenced in the database.

The authentication system uses Laravel's built-in authentication with Sanctum for API token management.