const express = require("express");
const cors = require("cors");
const mongoose = require("mongoose")
const apicache = require("apicache");
require("dotenv").config();
const v1SwapRouter = require("./v1/routes/swapRoutes");
const { swaggerDocs: V1SwaggerDocs } = require("./v1/swagger");

const app = express();
app.use(cors());
const PORT = process.env.PORT || 3003;
const cache = apicache.middleware;

app.use(express.json());
app.use(cache("2 minutes"));
app.use("/api/v1/swapRoutes", v1SwapRouter);

mongoose
  .connect(process.env.MONGODB_URI, {
    dbName: 'xeler_swap'
  })
  .then(() => console.log("Connected to MongoDb"))
  .catch((error) => console.error(error))

app.listen(PORT, () => {
  console.log(`🚀 Server listening on port ${PORT}`);
  V1SwaggerDocs(app, PORT);
});