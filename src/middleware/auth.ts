import type { NextFunction, Request, Response } from "express";
import type { Role } from "../types";
import { verifyToken } from "../utils/jwt";

const authenticate = (req: Request, res: Response, next: NextFunction) => {
  const authHeader = req.headers.authorization;
  if (!authHeader) {
    return res
      .status(401)
      .json({ error: "You are not logged in. Please sign in to continue." });
  }

  const token = authHeader.split(" ")[1];
  try {
    const decoded = verifyToken(token);
    req.user = { id: decoded.userId, role: decoded.role as Role };
    next();
  } catch (_error) {
    res
      .status(401)
      .json({ error: "Your session has expired. Please log in again." });
  }
};

export { authenticate };
