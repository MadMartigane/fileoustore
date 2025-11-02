import express from "express";

const app = express();

// Middleware for parsing JSON bodies
app.use(express.json());

export default app;
