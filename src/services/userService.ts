import {
	createUser,
	getUserByCredentials,
} from "../repositories/userRepository";
import type { User } from "../types";
import { signToken } from "../utils/jwt";
import { generateUuid } from "../utils/uuid";

export const registerUser = (username: string, password: string) => {
	const user: User = {
		id: generateUuid(),
		username,
		password, // Note: In production, hash the password
	};
	createUser(user);
	const token = signToken(user.id);
	return { token };
};

export const loginUser = (username: string, password: string) => {
	const user = getUserByCredentials(username, password);
	if (!user) {
		throw new Error("Invalid credentials");
	}
	const token = signToken(user.id);
	return { token };
};
