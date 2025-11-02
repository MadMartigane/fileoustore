import type { NextFunction, Request, Response } from "express";
import { verifyToken } from "../utils/jwt";

const authenticate = (req: Request, res: Response, next: NextFunction) => {
	const authHeader = req.headers.authorization;
	if (!authHeader) {
		return res.status(401).json({ error: "No token provided" });
	}

	const token = authHeader.split(" ")[1];
	try {
		const decoded = verifyToken(token);
		req.user = { id: decoded.userId };
		next();
	} catch (_error) {
		res.status(401).json({ error: "Invalid token" });
	}
};

export { authenticate };
