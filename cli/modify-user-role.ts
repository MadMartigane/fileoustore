import inquirer from "inquirer";
import { db } from "../src/config/database";

const validRoles = ["admin", "user", "guest"] as const;

type Role = (typeof validRoles)[number];

async function modifyUserRole() {
  try {
    const { username } = await inquirer.prompt<{ username: string }>({
      type: "input",
      name: "username",
      message: "Enter the username to modify:",
      validate: (input) =>
        input.trim().length > 0 || "Username cannot be empty.",
    });

    // Check if user exists
    const user = db
      .prepare("SELECT id, username, role FROM users WHERE username = ?")
      .get(username) as
      | { id: string; username: string; role: string }
      | undefined;

    if (!user) {
      console.error(`User '${username}' not found.`);
      process.exit(1);
    }

    console.log(`Current role for ${username}: ${user.role}`);

    const { newRole } = await inquirer.prompt<{ newRole: Role }>({
      type: "list",
      name: "newRole",
      message: "Select the new role:",
      choices: validRoles,
      default: user.role,
    });

    if (newRole === user.role) {
      console.log("No change needed. Role remains the same.");
      return;
    }

    // Update the role
    const stmt = db.prepare("UPDATE users SET role = ? WHERE id = ?");
    stmt.run(newRole, user.id);

    console.log(
      `Role for user '${username}' updated to '${newRole}' successfully.`
    );
  } catch (error) {
    console.error("Failed to modify user role:", error);
    process.exit(1);
  }
}

modifyUserRole();
