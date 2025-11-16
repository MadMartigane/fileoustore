import type { Request, Response } from "express";
import {
  createNewDataset,
  deleteExistingDataset,
  getDatasetForUser,
  modifyPermissions,
  updateExistingDataset,
} from "../services/dataset-service";
import { DataSetSchema, PermissionSchema } from "../validators/schemas";

export const create = (req: Request, res: Response) => {
  const result = DataSetSchema.safeParse(req.body);
  if (!result.success) {
    return res.status(400).json({ error: result.error });
  }

  try {
    const userId = req.user?.id;
    if (!userId) {
      return res.status(401).json({ error: "User not authenticated" });
    }
    if (req.user?.role === "guest") {
      return res.status(403).json({ error: "Guests cannot create datasets" });
    }
    const dataSet = createNewDataset(
      userId,
      result.data.name,
      result.data.data
    );
    res.status(201).json(dataSet);
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Unknown error";
    res.status(400).json({ error: errorMessage });
  }
};

export const get = (req: Request, res: Response) => {
  try {
    const userId = req.user?.id;
    if (!userId) {
      return res.status(401).json({ error: "User not authenticated" });
    }
    const dataSet = getDatasetForUser(userId, req.params.id);
    res.json(dataSet);
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Unknown error";
    if (errorMessage === "Dataset not found") {
      res.status(404).json({ error: errorMessage });
    } else if (errorMessage === "No read permission") {
      res.status(403).json({ error: errorMessage });
    } else {
      res.status(500).json({ error: "Internal server error" });
    }
  }
};

export const update = (req: Request, res: Response) => {
  const result = DataSetSchema.safeParse(req.body);
  if (!result.success) {
    return res.status(400).json({ error: result.error });
  }

  try {
    const userId = req.user?.id;
    if (!userId) {
      return res.status(401).json({ error: "User not authenticated" });
    }
    const dataSet = updateExistingDataset(
      userId,
      req.params.id,
      result.data.name,
      result.data.data
    );
    res.json(dataSet);
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Unknown error";
    if (errorMessage.includes("not found")) {
      res.status(404).json({ error: errorMessage });
    } else if (errorMessage === "No write permission") {
      res.status(403).json({ error: errorMessage });
    } else {
      res.status(400).json({ error: errorMessage });
    }
  }
};

export const delete_ = (req: Request, res: Response) => {
  // Using delete_ since delete is keyword
  try {
    const userId = req.user?.id;
    if (!userId) {
      return res.status(204).send(); // or 401, but since no body, send()
    }
    deleteExistingDataset(userId, req.params.id);
    res.status(204).send();
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Unknown error";
    if (errorMessage === "Dataset not found") {
      res.status(404).json({ error: errorMessage });
    } else if (errorMessage === "Only owner can delete") {
      res.status(403).json({ error: errorMessage });
    } else {
      res.status(400).json({ error: errorMessage });
    }
  }
};

export const updatePermissions = (req: Request, res: Response) => {
  const result = PermissionSchema.safeParse(req.body);
  if (!result.success) {
    return res.status(400).json({ error: result.error });
  }

  try {
    const { userId, canRead, canWrite } = result.data;
    const ownerId = req.user?.id;
    if (!ownerId) {
      return res.status(401).json({ error: "User not authenticated" });
    }
    const dataSet = modifyPermissions(ownerId, req.params.id, userId, {
      canRead,
      canWrite,
    });
    res.json(dataSet);
  } catch (error) {
    const errorMessage =
      error instanceof Error ? error.message : "Unknown error";
    if (errorMessage.includes("not found")) {
      res.status(404).json({ error: errorMessage });
    } else if (errorMessage === "Only owner can modify permissions") {
      res.status(403).json({ error: errorMessage });
    } else {
      res.status(400).json({ error: errorMessage });
    }
  }
};
