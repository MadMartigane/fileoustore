import { db } from "../config/database";

type Permission = {
  datasetId: string;
  userId: string;
  canRead: number;
  canWrite: number;
};

const insertPermissionStmt = db.prepare(
  "INSERT INTO permissions (datasetId, userId, canRead, canWrite) VALUES (?, ?, ?, ?)"
);

const getPermissionStmt = db.prepare(
  "SELECT * FROM permissions WHERE datasetId = ? AND userId = ?"
);

const getPermissionsStmt = db.prepare(
  "SELECT userId, canRead, canWrite FROM permissions WHERE datasetId = ?"
);

const updatePermissionStmt = db.prepare(`
  UPDATE permissions
  SET canRead = ?, canWrite = ?
  WHERE datasetId = ? AND userId = ?
`);

const deletePermissionsStmt = db.prepare(
  "DELETE FROM permissions WHERE datasetId = ?"
);

const checkUserExistsStmt = db.prepare("SELECT id FROM users WHERE id = ?");

const checkDatasetExistsStmt = db.prepare(
  "SELECT id FROM datasets WHERE id = ?"
);

export const createPermission = (
  datasetId: string,
  userId: string,
  canRead: boolean,
  canWrite: boolean
): void => {
  insertPermissionStmt.run(
    datasetId,
    userId,
    canRead ? 1 : 0,
    canWrite ? 1 : 0
  );
};

export const getPermission = (
  datasetId: string,
  userId: string
): Permission | undefined =>
  getPermissionStmt.get(datasetId, userId) as Permission | undefined;

export const getPermissionsByDatasetId = (
  datasetId: string
): Pick<Permission, "userId" | "canRead" | "canWrite">[] =>
  getPermissionsStmt.all(datasetId) as Pick<
    Permission,
    "userId" | "canRead" | "canWrite"
  >[];

export const updatePermission = (
  datasetId: string,
  userId: string,
  canRead?: boolean,
  canWrite?: boolean
): void => {
  const existing = getPermission(datasetId, userId);
  if (!existing) {
    throw new Error("Permission not found");
  }
  const newCanRead = canRead !== undefined ? canRead : !!existing.canRead;
  const newCanWrite = canWrite !== undefined ? canWrite : !!existing.canWrite;
  updatePermissionStmt.run(
    newCanRead ? 1 : 0,
    newCanWrite ? 1 : 0,
    datasetId,
    userId
  );
};

export const userExists = (userId: string): boolean =>
  !!checkUserExistsStmt.get(userId);

export const datasetExists = (datasetId: string): boolean =>
  !!checkDatasetExistsStmt.get(datasetId);

export const deletePermissionsByDatasetId = (datasetId: string): void => {
  deletePermissionsStmt.run(datasetId);
};
