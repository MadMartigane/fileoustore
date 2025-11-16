import inquirer from "inquirer";
import { db } from "../src/config/database";

async function deleteUser() {
  try {
    const stmt = db.prepare("SELECT id, username, role FROM users");
    const users = stmt.all() as Array<{
      id: string;
      username: string;
      role: string;
    }>;

    if (users.length === 0) {
      console.log("No users found.");
      return;
    }

    const { selectedUser } = await inquirer.prompt<{
      selectedUser: { id: string; username: string; role: string };
    }>({
      type: "list",
      name: "selectedUser",
      message: "Select a user to delete:",
      choices: users.map((user) => ({
        name: `${user.username} (${user.role})`,
        value: user,
      })),
    });

    const { confirm } = await inquirer.prompt<{ confirm: boolean }>({
      type: "confirm",
      name: "confirm",
      message: `Are you sure you want to delete user '${selectedUser.username}'? This action cannot be undone.`,
      default: false,
    });

    if (!confirm) {
      console.log("Deletion cancelled.");
      return;
    }

    const deleteStmt = db.prepare("DELETE FROM users WHERE id = ?");
    deleteStmt.run(selectedUser.id);

    console.log(`User '${selectedUser.username}' deleted successfully.`);
  } catch (error) {
    console.error("Failed to delete user:", error);
    process.exit(1);
  }
}

deleteUser();
