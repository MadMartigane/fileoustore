import type { Request, Response } from "express";
import { loginUser, registerUser } from "../services/user-service";

export const register = (req: Request, res: Response) => {
  try {
    const { username, password } = req.body;
    const result = registerUser(username, password);
    res.json(result);
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Unknown error";
    res.status(400).json({ error: errorMessage });
  }
};

export const login = (req: Request, res: Response) => {
  try {
    const { username, password } = req.body;
    const result = loginUser(username, password);
    res.json(result);
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Unknown error";
    res.status(401).json({ error: errorMessage });
  }
};
