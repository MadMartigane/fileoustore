import { z } from "zod";

export const DataSetSchema = z.object({
	name: z.string().min(1),
	data: z.record(z.any()),
});

export const PermissionSchema = z.object({
	userId: z.string().uuid(),
	canRead: z.boolean().optional(),
	canWrite: z.boolean().optional(),
});
