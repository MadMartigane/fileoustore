export type User = {
	id: string;
	username: string;
	password: string; // In production, use hashed passwords
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

// Extend Express Request interface
declare global {
	namespace Express {
		interface Request {
			user?: { id: string };
		}
	}
}
