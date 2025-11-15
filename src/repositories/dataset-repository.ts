import { db } from "../config/database";
import type { DataSet } from "../types";

type DatasetRow = {
  id: string;
  ownerId: string;
  name: string;
  data: string;
};

const createDatasetStmt = db.prepare(
  "INSERT INTO datasets (id, ownerId, name, data) VALUES (?, ?, ?, ?)"
);

const getDatasetStmt = db.prepare("SELECT * FROM datasets WHERE id = ?");

const updateDatasetStmt = db.prepare(
  "UPDATE datasets SET name = ?, data = ? WHERE id = ?"
);

const deleteDatasetStmt = db.prepare("DELETE FROM datasets WHERE id = ?");

export const createDataset = (dataSet: DataSet): void => {
  createDatasetStmt.run(
    dataSet.id,
    dataSet.ownerId,
    dataSet.name,
    JSON.stringify(dataSet.data)
  );
};

export const getDatasetById = (id: string): DatasetRow | undefined =>
  getDatasetStmt.get(id) as DatasetRow | undefined;

export const updateDataset = (
  id: string,
  name: string,
  data: Record<string, unknown>
): number => {
  const result = updateDatasetStmt.run(name, JSON.stringify(data), id);
  return result.changes;
};

export const deleteDataset = (id: string): void => {
  deleteDatasetStmt.run(id);
};
