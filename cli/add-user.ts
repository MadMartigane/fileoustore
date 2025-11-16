import { v4 as uuidv4 } from "uuid";
import { db } from "../src/config/database";
import { hashPassword } from "../src/utils/password";

const [, , username, password, role] = process.argv;

const validRoles = ["admin", "user", "guest"];

const userRole = role || "user";

if (!(username && password && validRoles.includes(userRole))) {
  console.error("Usage: ts-node cli/add-user.ts <username> <password> [role]");
  console.error("Valid roles: admin, user (default), guest");
  process.exit(1);
}

try {
  const id = uuidv4();
  const hashedPassword = hashPassword(password);
  const stmt = db.prepare(
    "INSERT INTO users (id, username, password, role) VALUES (?, ?, ?, ?)"
  );
  stmt.run(id, username, hashedPassword, userRole);
  console.log(`User ${username} added successfully with ID ${id}`);
} catch (error) {
  console.error("Failed to add user:", error);
  process.exit(1);
}
