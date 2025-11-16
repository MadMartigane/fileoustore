import { db } from "../config/database";
import type { User } from "../types";

const createUserStmt = db.prepare(
  "INSERT INTO users (id, username, password, role) VALUES (?, ?, ?, ?)"
);

const getUserByUsernameStmt = db.prepare(
  "SELECT * FROM users WHERE username = ?"
);

export const createUser = (user: User): void => {
  try {
    createUserStmt.run(user.id, user.username, user.password, user.role);
  } catch (_error) {
    throw new Error("Username already exists");
  }
};

export const getUserByUsername = (username: string): User | undefined =>
  getUserByUsernameStmt.get(username) as User | undefined;
