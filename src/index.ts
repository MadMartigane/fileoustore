import express, { Request, Response } from "express";
import jwt from "jsonwebtoken";
import { z } from "zod";
import { v4 as uuidv4 } from "uuid";
import Database from "better-sqlite3";
import dotenv from "dotenv";

// Load environment variables from .env file
dotenv.config();

// Types
type User = {
  id: string;
  username: string;
  password: string; // In production, use hashed passwords
};

type DataSet = {
  id: string;
  ownerId: string;
  name: string;
  data: Record<string, any>;
  permissions: {
    read: string[];
    write: string[];
  };
};

// Schemas for validation
const DataSetSchema = z.object({
  name: z.string().min(1),
  data: z.record(z.any()),
});

const PermissionSchema = z.object({
  userId: z.string().uuid(),
  canRead: z.boolean().optional(),
  canWrite: z.boolean().optional(),
});

// SQLite Database setup
const db = new Database("database.db");

// Initialize tables
db.exec(`
  CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL
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

// JWT Secret (use environment variable in production)
const JWT_SECRET = process.env.JWT_SECRET || "your-secret-key";

// Middleware for authentication
const authenticate = (req: Request, res: Response, next: () => void) => {
  const authHeader = req.headers.authorization;
  if (!authHeader) {
    return res.status(401).json({ error: "No token provided" });
  }

  const token = authHeader.split(" ")[1];
  try {
    const decoded = jwt.verify(token, JWT_SECRET) as { userId: string };
    req.user = { id: decoded.userId };
    next();
  } catch (error) {
    res.status(401).json({ error: "Invalid token" });
  }
};

// Express app setup
const app = express();
app.use(express.json());

// User registration
app.post("/register", (req: Request, res: Response) => {
  const { username, password } = req.body;
  const user: User = {
    id: uuidv4(),
    username,
    password,
  };

  try {
    const stmt = db.prepare(
      "INSERT INTO users (id, username, password) VALUES (?, ?, ?)",
    );
    stmt.run(user.id, user.username, user.password);
    const token = jwt.sign({ userId: user.id }, JWT_SECRET);
    res.json({ token });
  } catch (error) {
    res.status(400).json({ error: "Username already exists" });
  }
});

// User login
app.post("/login", (req: Request, res: Response) => {
  const { username, password } = req.body;
  const stmt = db.prepare(
    "SELECT * FROM users WHERE username = ? AND password = ?",
  );
  const user = stmt.get(username, password) as User | undefined;

  if (!user) {
    return res.status(401).json({ error: "Invalid credentials" });
  }
  const token = jwt.sign({ userId: user.id }, JWT_SECRET);
  res.json({ token });
});

// Create DataSet
app.post("/datasets", authenticate, (req: Request, res: Response) => {
  const result = DataSetSchema.safeParse(req.body);
  if (!result.success) {
    return res.status(400).json({ error: result.error });
  }

  const dataSet: DataSet = {
    id: uuidv4(),
    ownerId: req.user!.id,
    name: result.data.name,
    data: result.data.data,
    permissions: { read: [req.user!.id], write: [req.user!.id] },
  };

  const stmt = db.prepare(
    "INSERT INTO datasets (id, ownerId, name, data) VALUES (?, ?, ?, ?)",
  );
  stmt.run(
    dataSet.id,
    dataSet.ownerId,
    dataSet.name,
    JSON.stringify(dataSet.data),
  );

  const permStmt = db.prepare(
    "INSERT INTO permissions (datasetId, userId, canRead, canWrite) VALUES (?, ?, ?, ?)",
  );
  permStmt.run(dataSet.id, req.user!.id, 1, 1);

  res.status(201).json(dataSet);
});

// Get DataSet
app.get("/datasets/:id", authenticate, (req: Request, res: Response) => {
  const stmt = db.prepare(`
    SELECT d.*, p.canRead, p.canWrite
    FROM datasets d
    LEFT JOIN permissions p ON d.id = p.datasetId AND p.userId = ?
    WHERE d.id = ?
  `);
  const row = stmt.get(req.user!.id, req.params.id);

  if (!row) {
    return res.status(404).json({ error: "DataSet not found" });
  }
  if (!row.canRead) {
    return res.status(403).json({ error: "No read permission" });
  }

  const permissionsStmt = db.prepare(
    "SELECT userId, canRead, canWrite FROM permissions WHERE datasetId = ?",
  );
  const permissions = permissionsStmt.all(req.params.id);

  const dataSet: DataSet = {
    id: row.id,
    ownerId: row.ownerId,
    name: row.name,
    data: JSON.parse(row.data),
    permissions: {
      read: permissions.filter((p: any) => p.canRead).map((p: any) => p.userId),
      write: permissions
        .filter((p: any) => p.canWrite)
        .map((p: any) => p.userId),
    },
  };

  res.json(dataSet);
});

// Update DataSet
app.put("/datasets/:id", authenticate, (req: Request, res: Response) => {
  const permStmt = db.prepare(
    "SELECT canWrite FROM permissions WHERE datasetId = ? AND userId = ?",
  );
  const permission = permStmt.get(req.params.id, req.user!.id);

  if (!permission || !permission.canWrite) {
    return res.status(403).json({ error: "No write permission" });
  }

  const result = DataSetSchema.safeParse(req.body);
  if (!result.success) {
    return res.status(400).json({ error: result.error });
  }

  const stmt = db.prepare(
    "UPDATE datasets SET name = ?, data = ? WHERE id = ?",
  );
  const resultUpdate = stmt.run(
    result.data.name,
    JSON.stringify(result.data.data),
    req.params.id,
  );

  if (resultUpdate.changes === 0) {
    return res.status(404).json({ error: "DataSet not found" });
  }

  const dataSetStmt = db.prepare("SELECT * FROM datasets WHERE id = ?");
  const row = dataSetStmt.get(req.params.id);
  const permissionsStmt = db.prepare(
    "SELECT userId, canRead, canWrite FROM permissions WHERE datasetId = ?",
  );
  const permissions = permissionsStmt.all(req.params.id);

  const dataSet: DataSet = {
    id: row.id,
    ownerId: row.ownerId,
    name: row.name,
    data: JSON.parse(row.data),
    permissions: {
      read: permissions.filter((p: any) => p.canRead).map((p: any) => p.userId),
      write: permissions
        .filter((p: any) => p.canWrite)
        .map((p: any) => p.userId),
    },
  };

  res.json(dataSet);
});

// Delete DataSet
app.delete("/datasets/:id", authenticate, (req: Request, res: Response) => {
  const stmt = db.prepare("SELECT ownerId FROM datasets WHERE id = ?");
  const row = stmt.get(req.params.id);

  if (!row) {
    return res.status(404).json({ error: "DataSet not found" });
  }
  if (row.ownerId !== req.user!.id) {
    return res.status(403).json({ error: "Only owner can delete" });
  }

  const deletePermStmt = db.prepare(
    "DELETE FROM permissions WHERE datasetId = ?",
  );
  deletePermStmt.run(req.params.id);

  const deleteStmt = db.prepare("DELETE FROM datasets WHERE id = ?");
  deleteStmt.run(req.params.id);

  res.status(204).send();
});

// Update permissions
app.patch(
  "/datasets/:id/permissions",
  authenticate,
  (req: Request, res: Response) => {
    const stmt = db.prepare("SELECT ownerId FROM datasets WHERE id = ?");
    const row = stmt.get(req.params.id);

    if (!row) {
      return res.status(404).json({ error: "DataSet not found" });
    }
    if (row.ownerId !== req.user!.id) {
      return res
        .status(403)
        .json({ error: "Only owner can modify permissions" });
    }

    const result = PermissionSchema.safeParse(req.body);
    if (!result.success) {
      return res.status(400).json({ error: result.error });
    }

    const { userId, canRead, canWrite } = result.data;

    // Check if user exists
    const userStmt = db.prepare("SELECT id FROM users WHERE id = ?");
    if (!userStmt.get(userId)) {
      return res.status(404).json({ error: "User not found" });
    }

    const permStmt = db.prepare(
      "SELECT * FROM permissions WHERE datasetId = ? AND userId = ?",
    );
    const existingPerm = permStmt.get(req.params.id, userId);

    if (existingPerm) {
      const updateStmt = db.prepare(`
      UPDATE permissions
      SET canRead = ?, canWrite = ?
      WHERE datasetId = ? AND userId = ?
    `);
      updateStmt.run(
        canRead !== undefined ? canRead : existingPerm.canRead,
        canWrite !== undefined ? canWrite : existingPerm.canWrite,
        req.params.id,
        userId,
      );
    } else {
      const insertStmt = db.prepare(`
      INSERT INTO permissions (datasetId, userId, canRead, canWrite)
      VALUES (?, ?, ?, ?)
    `);
      insertStmt.run(
        req.params.id,
        userId,
        canRead !== undefined ? canRead : false,
        canWrite !== undefined ? canWrite : false,
      );
    }

    const dataSetStmt = db.prepare("SELECT * FROM datasets WHERE id = ?");
    const dataSetRow = dataSetStmt.get(req.params.id);
    const permissionsStmt = db.prepare(
      "SELECT userId, canRead, canWrite FROM permissions WHERE datasetId = ?",
    );
    const permissions = permissionsStmt.all(req.params.id);

    const dataSet: DataSet = {
      id: dataSetRow.id,
      ownerId: dataSetRow.ownerId,
      name: dataSetRow.name,
      data: JSON.parse(dataSetRow.data),
      permissions: {
        read: permissions
          .filter((p: any) => p.canRead)
          .map((p: any) => p.userId),
        write: permissions
          .filter((p: any) => p.canWrite)
          .map((p: any) => p.userId),
      },
    };

    res.json(dataSet);
  },
);

// Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});

// Extend Express Request interface
declare global {
  namespace Express {
    interface Request {
      user?: { id: string };
    }
  }
}
