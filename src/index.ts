import express, { Request, Response } from "express";
import jwt from "jsonwebtoken";
import { z } from "zod";
import { v4 as uuidv4 } from "uuid";

// Types
interface User {
	id: string;
	username: string;
	password: string; // In production, use hashed passwords
}

interface DataSet {
	id: string;
	ownerId: string;
	name: string;
	data: Record<string, any>;
	permissions: {
		read: string[];
		write: string[];
	};
}

// Schemas for validation
const DataSetSchema = z.object({
	name: z.string().min(1),
	data: z.record(z.any()),
});

const PermissionSchema = z.object({
	userId: z.string().uuid(),
	canRead: z.boolean().optional(),
	canWrite: z.boolean().optional(),
});

// In-memory storage (replace with database in production)
const users: User[] = [];
const dataSets: DataSet[] = [];

// JWT Secret (use environment variable in production)
const JWT_SECRET = "your-secret-key";

// Middleware for authentication
const authenticate = (req: Request, res: Response, next: () => void) => {
	const authHeader = req.headers.authorization;
	if (!authHeader) {
		return res.status(401).json({ error: "No token provided" });
	}

	const token = authHeader.split(" ")[1];
	try {
		const decoded = jwt.verify(token, JWT_SECRET) as { userId: string };
		req.user = { id: decoded.userId };
		next();
	} catch (error) {
		res.status(401).json({ error: "Invalid token" });
	}
};

// Express app setup
const app = express();
app.use(express.json());

// User registration (simplified)
app.post("/register", (req: Request, res: Response) => {
	const { username, password } = req.body;
	const user: User = {
		id: uuidv4(),
		username,
		password,
	};
	users.push(user);
	const token = jwt.sign({ userId: user.id }, JWT_SECRET);
	res.json({ token });
});

// User login
app.post("/login", (req: Request, res: Response) => {
	const { username, password } = req.body;
	const user = users.find(
		(u) => u.username === username && u.password === password,
	);
	if (!user) {
		return res.status(401).json({ error: "Invalid credentials" });
	}
	const token = jwt.sign({ userId: user.id }, JWT_SECRET);
	res.json({ token });
});

// Create DataSet
app.post("/datasets", authenticate, (req: Request, res: Response) => {
	const result = DataSetSchema.safeParse(req.body);
	if (!result.success) {
		return res.status(400).json({ error: result.error });
	}

	const dataSet: DataSet = {
		id: uuidv4(),
		ownerId: req.user!.id,
		name: result.data.name,
		data: result.data.data,
		permissions: { read: [req.user!.id], write: [req.user!.id] },
	};
	dataSets.push(dataSet);
	res.status(201).json(dataSet);
});

// Get DataSet
app.get("/datasets/:id", authenticate, (req: Request, res: Response) => {
	const dataSet = dataSets.find((d) => d.id === req.params.id);
	if (!dataSet) {
		return res.status(404).json({ error: "DataSet not found" });
	}
	if (!dataSet.permissions.read.includes(req.user!.id)) {
		return res.status(403).json({ error: "No read permission" });
	}
	res.json(dataSet);
});

// Update DataSet
app.put("/datasets/:id", authenticate, (req: Request, res: Response) => {
	const dataSet = dataSets.find((d) => d.id === req.params.id);
	if (!dataSet) {
		return res.status(404).json({ error: "DataSet not found" });
	}
	if (!dataSet.permissions.write.includes(req.user!.id)) {
		return res.status(403).json({ error: "No write permission" });
	}

	const result = DataSetSchema.safeParse(req.body);
	if (!result.success) {
		return res.status(400).json({ error: result.error });
	}

	dataSet.name = result.data.name;
	dataSet.data = result.data.data;
	res.json(dataSet);
});

// Delete DataSet
app.delete("/datasets/:id", authenticate, (req: Request, res: Response) => {
	const dataSetIndex = dataSets.findIndex((d) => d.id === req.params.id);
	if (dataSetIndex === -1) {
		return res.status(404).json({ error: "DataSet not found" });
	}
	if (dataSets[dataSetIndex].ownerId !== req.user!.id) {
		return res.status(403).json({ error: "Only owner can delete" });
	}
	dataSets.splice(dataSetIndex, 1);
	res.status(204).send();
});

// Update permissions
app.patch(
	"/datasets/:id/permissions",
	authenticate,
	(req: Request, res: Response) => {
		const dataSet = dataSets.find((d) => d.id === req.params.id);
		if (!dataSet) {
			return res.status(404).json({ error: "DataSet not found" });
		}
		if (dataSet.ownerId !== req.user!.id) {
			return res
				.status(403)
				.json({ error: "Only owner can modify permissions" });
		}

		const result = PermissionSchema.safeParse(req.body);
		if (!result.success) {
			return res.status(400).json({ error: result.error });
		}

		const { userId, canRead, canWrite } = result.data;
		if (canRead !== undefined) {
			if (canRead) {
				if (!dataSet.permissions.read.includes(userId)) {
					dataSet.permissions.read.push(userId);
				}
			} else {
				dataSet.permissions.read = dataSet.permissions.read.filter(
					(id) => id !== userId,
				);
			}
		}
		if (canWrite !== undefined) {
			if (canWrite) {
				if (!dataSet.permissions.write.includes(userId)) {
					dataSet.permissions.write.push(userId);
				}
			} else {
				dataSet.permissions.write = dataSet.permissions.write.filter(
					(id) => id !== userId,
				);
			}
		}
		res.json(dataSet);
	},
);

// Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
	console.log(`Server running on port ${PORT}`);
});

// Extend Express Request interface
declare global {
	namespace Express {
		interface Request {
			user?: { id: string };
		}
	}
}
