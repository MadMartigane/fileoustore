import {
	createDataset,
	deleteDataset,
	getDatasetById,
	updateDataset,
} from "../repositories/datasetRepository";
import {
	createPermission,
	datasetExists,
	deletePermissionsByDatasetId,
	getPermission,
	getPermissionsByDatasetId,
	updatePermission,
	userExists,
} from "../repositories/permissionRepository";
import type { DataSet } from "../types";
import { generateUuid } from "../utils/uuid";

type PermissionRow = {
	userId: string;
	canRead: number;
	canWrite: number;
};

export const createNewDataset = (
	userId: string,
	name: string,
	data: Record<string, unknown>,
) => {
	const dataSet: DataSet = {
		id: generateUuid(),
		ownerId: userId,
		name,
		data,
		permissions: { read: [userId], write: [userId] },
	};

	createDataset(dataSet);
	createPermission(dataSet.id, userId, true, true);

	return dataSet;
};

export const getDatasetForUser = (userId: string, datasetId: string) => {
	const datasetRow = getDatasetById(datasetId);
	if (!datasetRow) {
		throw new Error("Dataset not found");
	}

	const permission = getPermission(datasetId, userId);
	if (!permission?.canRead) {
		throw new Error("No read permission");
	}

	const permissions = getPermissionsByDatasetId(datasetId) as PermissionRow[];
	const dataSet: DataSet = {
		id: datasetRow.id,
		ownerId: datasetRow.ownerId,
		name: datasetRow.name,
		data: JSON.parse(datasetRow.data),
		permissions: {
			read: permissions.filter((p) => p.canRead).map((p) => p.userId),
			write: permissions.filter((p) => p.canWrite).map((p) => p.userId),
		},
	};

	return dataSet;
};

export const updateExistingDataset = (
	userId: string,
	datasetId: string,
	name: string,
	data: Record<string, unknown>,
) => {
	const datasetRow = getDatasetById(datasetId);
	if (!datasetRow) {
		throw new Error("Dataset not found");
	}

	const permission = getPermission(datasetId, userId);
	if (!permission?.canWrite) {
		throw new Error("No write permission");
	}

	const changes = updateDataset(datasetId, name, data);
	if (changes === 0) {
		throw new Error("Dataset not found");
	}

	return getDatasetForUser(userId, datasetId);
};

export const deleteExistingDataset = (userId: string, datasetId: string) => {
	const datasetRow = getDatasetById(datasetId);
	if (!datasetRow) {
		throw new Error("Dataset not found");
	}
	if (datasetRow.ownerId !== userId) {
		throw new Error("Only owner can delete");
	}

	// Delete permissions first
	deletePermissionsByDatasetId(datasetId);
	deleteDataset(datasetId);
};

export const modifyPermissions = (
	ownerId: string,
	datasetId: string,
	targetUserId: string,
	canRead?: boolean,
	canWrite?: boolean,
) => {
	const datasetRow = getDatasetById(datasetId);
	if (!datasetRow) {
		throw new Error("Dataset not found");
	}
	if (!datasetExists(datasetId)) {
		throw new Error("Dataset not found");
	}
	if (datasetRow.ownerId !== ownerId) {
		throw new Error("Only owner can modify permissions");
	}

	if (!userExists(targetUserId)) {
		throw new Error("User not found");
	}

	const existingPerm = getPermission(datasetId, targetUserId);
	if (existingPerm) {
		updatePermission(datasetId, targetUserId, canRead, canWrite);
	} else {
		createPermission(
			datasetId,
			targetUserId,
			canRead ?? false,
			canWrite ?? false,
		);
	}

	return getDatasetForUser(ownerId, datasetId);
};
