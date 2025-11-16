import { createUser, getUserByUsername } from "../repositories/user-repository";
import type { User } from "../types";
import { signToken } from "../utils/jwt";
import { hashPassword, verifyPassword } from "../utils/password";
import { generateUuid } from "../utils/uuid";

export const registerUser = (username: string, password: string) => {
  const hashedPassword = hashPassword(password);
  const user: User = {
    id: generateUuid(),
    username,
    password: hashedPassword,
    role: "user",
  };
  createUser(user);
  const token = signToken(user.id, user.role);
  return { token };
};

export const loginUser = (username: string, password: string) => {
  const user = getUserByUsername(username);
  if (!user) {
    throw new Error("Invalid username or password. Please try again.");
  }
  const isValidPassword = verifyPassword(password, user.password);
  if (!isValidPassword) {
    throw new Error("Invalid username or password. Please try again.");
  }
  const token = signToken(user.id, user.role);
  return { token };
};
