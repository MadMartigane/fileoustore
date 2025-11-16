import Database from "better-sqlite3";
import dotenv from "dotenv";

// Load environment variables from .env file
dotenv.config();

// SQLite Database setup
const db = new Database("database.db");

// Initialize tables
db.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'user'
  );

  CREATE TABLE IF NOT EXISTS datasets (
    id TEXT PRIMARY KEY,
    ownerId TEXT NOT NULL,
    name TEXT NOT NULL,
    data TEXT NOT NULL,
    FOREIGN KEY (ownerId) REFERENCES users(id)
  );

  CREATE TABLE IF NOT EXISTS permissions (
    datasetId TEXT NOT NULL,
    userId TEXT NOT NULL,
    canRead BOOLEAN NOT NULL,
    canWrite BOOLEAN NOT NULL,
    FOREIGN KEY (datasetId) REFERENCES datasets(id),
    FOREIGN KEY (userId) REFERENCES users(id),
    PRIMARY KEY (datasetId, userId)
  );
  `);

try {
  db.exec(`ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'user';`);
} catch (_error) {
  // Column might already exist, ignore
}

export { db };
