import { db } from "../config/database";
import type { User } from "../types";

const createUserStmt = db.prepare(
  "INSERT INTO users (id, username, password, role) VALUES (?, ?, ?, ?)"
);

const getUserByCredentialsStmt = db.prepare(
  "SELECT * FROM users WHERE username = ? AND password = ?"
);

export const createUser = (user: User): void => {
  try {
    createUserStmt.run(user.id, user.username, user.password, user.role);
  } catch (_error) {
    throw new Error("Username already exists");
  }
};

export const getUserByCredentials = (
  username: string,
  password: string
): User | undefined =>
  getUserByCredentialsStmt.get(username, password) as User | undefined;
