const express = require("express");
const operationController = require("../../controllers/operationController");

const router = express.Router();

router
  .post("/Operation", operationController.createNewOperation)
  .post("/OperationComplete", operationController.operationComplete)
  .get("/FindOperation/:id", operationController.findOperation)
  .get("/OperationComplete/:id", operationController.editOperation)
  .post("/comprobar", operationController.comprobar)
  .post("/ipn", operationController.ipn)
  .post("/webhook", operationController.webhook)

module.exports = router;
