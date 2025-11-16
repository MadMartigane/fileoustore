import Table from "cli-table3";
import pc from "picocolors";
import { db } from "../src/config/database";

type TableInfo = {
  cid: number;
  name: string;
  type: string;
  notnull: number;
  dflt_value: string | null;
  pk: number;
};

type TableDescription = {
  [key: string]: string;
};

const tableDescriptions: Record<string, TableDescription> = {
  users: {
    id: "Unique user identifier (UUID)",
    username: "Unique username for login",
    password: "Password hashed with bcrypt",
    role: "User role (admin/user/guest)",
  },
  datasets: {
    id: "Unique dataset identifier (UUID)",
    ownerId: "Reference to the owner user",
    name: "Descriptive dataset name",
    data: "JSON content of the dataset",
  },
  permissions: {
    datasetId: "Reference to the concerned dataset",
    userId: "Reference to the authorized user",
    canRead: "Read permission (0=no, 1=yes)",
    canWrite: "Write permission (0=no, 1=yes)",
  },
};

function getTableInfo(tableName: string): TableInfo[] {
  const stmt = db.prepare(`PRAGMA table_info(${tableName})`);
  return stmt.all() as TableInfo[];
}

function getRowCount(tableName: string): number {
  const stmt = db.prepare(`SELECT COUNT(*) as count FROM ${tableName}`);
  const result = stmt.get() as { count: number };
  return result.count;
}

function formatConstraints(info: TableInfo): string {
  const constraints: string[] = [];

  if (info.pk) {
    constraints.push("PRIMARY KEY");
  }
  if (info.notnull) {
    constraints.push("NOT NULL");
  }
  if (info.dflt_value !== null) {
    constraints.push(`DEFAULT ${info.dflt_value}`);
  }

  return constraints.join(", ") || "-";
}

function displayTableStatus(tableName: string): void {
  console.log(
    pc.bold(pc.blue(`\n┏━━━━━━━━━━━━━━━ Table: ${tableName} ━━━━━━━━━━━━━━━┓`))
  );

  const tableInfo = getTableInfo(tableName);
  const rowCount = getRowCount(tableName);

  const table = new Table({
    head: [
      pc.cyan("Field"),
      pc.cyan("Type"),
      pc.cyan("Constraints"),
      pc.cyan("Description"),
    ],
    style: {
      head: [],
      border: ["grey"],
    },
    colWidths: [15, 12, 25, 50],
  });

  for (const info of tableInfo) {
    const description =
      tableDescriptions[tableName]?.[info.name] || "Not documented";

    table.push([
      pc.white(info.name),
      pc.yellow(info.type),
      pc.magenta(formatConstraints(info)),
      pc.gray(description),
    ]);
  }

  console.log(table.toString());

  const countColor = rowCount > 0 ? pc.green : pc.yellow;
  console.log(countColor(`✓ ${rowCount} record${rowCount !== 1 ? "s" : ""}`));
}

function main(): void {
  try {
    console.log(pc.bold(pc.cyan("═".repeat(60))));
    console.log(pc.bold(pc.cyan("              📊 DATABASE STATUS")));
    console.log(pc.bold(pc.cyan("═".repeat(60))));

    const tables = ["users", "datasets", "permissions"];

    for (const tableName of tables) {
      displayTableStatus(tableName);
    }

    console.log(pc.bold(pc.green("\n              ✅ Analysis completed")));
  } catch (error) {
    console.error(pc.red("❌ Error during database analysis:"), error);
    process.exit(1);
  }
}

main();
