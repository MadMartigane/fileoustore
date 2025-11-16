import { db } from "../src/config/database";

try {
  const stmt = db.prepare("SELECT id, username, role FROM users");
  const users = stmt.all() as Array<{
    id: string;
    username: string;
    role: string;
  }>;

  if (users.length === 0) {
    console.log("No users found.");
  } else {
    console.table(users);
  }
} catch (error) {
  console.error("Failed to list users:", error);
  process.exit(1);
}
