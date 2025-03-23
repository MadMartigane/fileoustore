# Migration from SleekDB to SQLite

This document outlines the changes made to migrate the FileouStore application from using SleekDB to SQLite.

## Changes Overview

1. Created migrations for database tables:
   - `users` - Storing user information
   - `files` - Storing file metadata
   - `personal_access_tokens` - For Sanctum authentication
   - `password_reset_tokens` - For password reset functionality

2. Updated models to use Eloquent:
   - Modified `User` model to extend Eloquent's `Authenticatable`
   - Modified `File` model to extend Eloquent's `Model`
   - Added proper model relationships and casts

3. Updated services to use Eloquent:
   - Refactored `UserService` to use Eloquent models
   - Refactored `FileStore` service to use Eloquent models

4. Removed SleekDB-specific code:
   - Removed `SleekDbUserProvider`
   - Updated `AuthServiceProvider` to use standard Laravel authentication
   - Simplified `AppServiceProvider`

5. Updated auth configuration:
   - Modified `auth.php` to use Laravel's standard Eloquent user provider
   - Configured Sanctum for SQLite

6. Added migration tools:
   - Created `MigrateToSqlite` command to help users migrate data
   - Updated `DatabaseSeeder` to create admin user with Eloquent
   - Updated `GenerateApiToken` command to work with Eloquent models

7. Updated documentation:
   - Added migration instructions to README.md
   - Updated API documentation
   - Added this migration notes file

## Migration Commands

Run the following commands to migrate your application from SleekDB to SQLite:

```bash
# Run migrations to create SQLite tables
php artisan migrate

# Migrate data from SleekDB to SQLite (optional)
php artisan migrate:from-sleekdb
```

## Benefits of SQLite

1. **Standard Laravel ORM Support**: Full Eloquent ORM support with relationships, query builder, etc.
2. **Better Query Performance**: SQLite provides better query performance for complex queries
3. **Standard Database Migrations**: Use Laravel's migration system for schema changes
4. **SQL Support**: Full SQL query support
5. **Better Integration**: Better integration with Laravel's standard auth system and Sanctum

## Potential Challenges

1. **Data Migration**: Existing SleekDB data needs to be migrated to SQLite
2. **Schema Changes**: Structure of data in SQLite differs from SleekDB's document-based storage
3. **JSON Fields**: Using JSON fields in SQLite for shared_with data (may have performance implications for complex queries)

## Rollback Plan

If needed, you can roll back to SleekDB by:

1. Reverting the code changes in this commit
2. Ensuring the SleekDB dependency is installed: `composer require rakibtg/sleekdb`
3. Ensuring SleekDB data directories still exist

## Conclusion

This migration significantly improves the FileouStore application by:

1. Following Laravel conventions for database access
2. Improving performance for database operations
3. Making the codebase more maintainable and familiar to Laravel developers
4. Enabling more advanced database features and relationship modeling