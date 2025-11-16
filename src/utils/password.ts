import bcrypt from "bcrypt";

const SALT_ROUNDS = 12;

export const hashPassword = (password: string): string =>
  bcrypt.hashSync(password, SALT_ROUNDS);

export const verifyPassword = (
  password: string,
  hashedPassword: string
): boolean => bcrypt.compareSync(password, hashedPassword);
