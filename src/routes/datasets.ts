import { Router } from "express";
import * as datasetsController from "../controllers/datasetsController";
import { authenticate } from "../middleware/auth";

const router = Router();

router.post("/", authenticate, datasetsController.create);
router.get("/:id", authenticate, datasetsController.get);
router.put("/:id", authenticate, datasetsController.update);
router.delete("/:id", authenticate, datasetsController.delete_);
router.patch(
	"/:id/permissions",
	authenticate,
	datasetsController.updatePermissions,
);

export default router;
