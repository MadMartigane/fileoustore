import app from "./app";
import { auth, datasets } from "./routes";

app.use("/auth", auth);
app.use("/datasets", datasets);

// Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
	console.log(`Server running on port ${PORT}`);
});
