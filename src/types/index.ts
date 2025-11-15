export type Role = "admin" | "user" | "guest";

export type User = {
  id: string;
  username: string;
  password: string; // In production, use hashed passwords
  role: Role;
};

export type DataSet = {
  id: string;
  ownerId: string;
  name: string;
  data: Record<string, unknown>;
  permissions: {
    read: string[];
    write: string[];
  };
};

import "express";

// Extend Express Request interface
declare module "express" {
  // biome-ignore lint/style/useConsistentTypeDefinitions: Module augmentation requires interface
  interface Request {
    user?: { id: string; role: Role };
  }
}
