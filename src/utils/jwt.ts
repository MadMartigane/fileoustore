import jwt from "jsonwebtoken";

const JWT_SECRET = process.env.JWT_SECRET || "your-secret-key";

export const signToken = (userId: string): string =>
  jwt.sign({ userId }, JWT_SECRET);

export const verifyToken = (token: string): { userId: string } =>
  jwt.verify(token, JWT_SECRET) as { userId: string };
