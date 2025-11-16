import jwt from "jsonwebtoken";

const JWT_SECRET = process.env.JWT_SECRET || "your-secret-key";

export const signToken = (userId: string, role: string): string =>
  jwt.sign({ userId, role }, JWT_SECRET);

export const verifyToken = (token: string): { userId: string; role: string } =>
  jwt.verify(token, JWT_SECRET) as { userId: string; role: string };
