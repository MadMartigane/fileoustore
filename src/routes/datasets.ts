import { Router } from "express";
import {
  create,
  delete_,
  get,
  update,
  updatePermissions,
} from "../controllers/datasets-controller";
import { authenticate } from "../middleware/auth";

const router = Router();

router.post("/", authenticate, create);
router.get("/:id", authenticate, get);
router.put("/:id", authenticate, update);
router.delete("/:id", authenticate, delete_);
router.patch("/:id/permissions", authenticate, updatePermissions);

export default router;
