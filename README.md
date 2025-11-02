
# Dataset API

## Purpose

This project implements a RESTful API for managing user datasets. It allows users to authenticate via JWT tokens, create, read, update, and delete datasets containing arbitrary JSON data, while controlling access permissions (read/write) for other users on a per-dataset basis.

The API is designed for scenarios where users need to store and share structured data securely, such as in collaborative tools or data-sharing platforms.

## How It Works

The application is built using Node.js with the following key technologies:

- **Express.js**: Handles HTTP requests and provides the RESTful API endpoints.
- **SQLite**: Used as the database backend via the `better-sqlite3` package for lightweight, file-based data storage.
- **JWT (JSON Web Tokens)**: Manages user authentication and session persistence.
- **Zod**: Validates incoming data against predefined schemas to ensure data integrity.
- **UUID**: Generates unique identifiers for users and datasets.

### Database Schema

Three main tables are used:

- `users`: Stores user credentials (id, username, password).
- `datasets`: Stores dataset metadata and JSON data (id, ownerId, name, data).
- `permissions`: Manages access rights per user per dataset (datasetId, userId, canRead, canWrite).

### Authentication Flow

Users must register or login to obtain a JWT token. This token is required in the Authorization header for protected endpoints.

### API Endpoints

- `POST /register`: Creates a new user account.
- `POST /login`: Authenticates a user and returns a JWT token.
- `POST /datasets`: Creates a new dataset (authenticated).
- `GET /datasets/:id`: Retrieves a dataset if the user has read permission.
- `PUT /datasets/:id`: Updates a dataset if the user has write permission.
- `DELETE /datasets/:id`: Deletes a dataset (owner only).
- `PATCH /datasets/:id/permissions`: Modifies permissions for a dataset (owner only).

### Commands and Usage

Ensure `pnpm` is installed (or use `npm` if preferred).

1. **Install dependencies**:
   ```
   pnpm install
   ```

2. **Run in development mode** (with auto-restart):
   ```
   pnpm run dev
   ```

3. **Run in production mode**:
   ```
   pnpm run start
   ```

4. **Format code**:
   ```
   pnpm run format
   ```

5. **Lint code**:
   ```
   pnpm run lint
   ```

The server starts on port 3000 by default (configurable via `PORT` environment variable).

## What’s Missing to Finalize the Project

While the core functionality is implemented, the following aspects need attention for production readiness:

- **Security Enhancements**:
  - Passwords are stored in plain text; implement hashing (e.g., using `bcrypt`).
  - Use proper environment variables for `JWT_SECRET` without fallbacks.
  - Add rate limiting and input sanitization to prevent common vulnerabilities.

- **Testing**:
  - No unit tests, integration tests, or end-to-end tests. Add testing framework like Jest or Mocha.

- **Documentation**:
  - API documentation (e.g., OpenAPI/Swagger specs) for easy integration.
  - Detailed setup instructions, especially for environment configuration.

- **Error Handling and Logging**:
  - Improve error responses and add logging (e.g., using `winston` or `morgan`).

- **Additional Features**:
  - Endpoints for user management (e.g., change password, delete account).
  - CORS configuration if serving a frontend.
  - Database migrations for version control of schema changes.
  - Pagination for listing datasets if user-owned lists are added.

- **Configuration**:
  - Add a `.env.example` file to guide environment setup.
  - Support for production databases (e.g., PostgreSQL instead of SQLite).

- **Deployment**:
  - Docker setup for containerization.
  - CI/CD pipeline configuration.

Addressing these will make the project robust and ready for deployment.