const express = require("express");
const OperationShema = require("./models/operationModel");
const { saveToDatabase } = require("./utils");

const createNewOperation = (newOperation) => {
  try {
      const operation = OperationShema(newOperation);
      operation
          .save()
          .then((data) => data)
          .catch((error) => error);
      return(operation);
  } catch (error) {
    throw { status: 500, message: error?.message || error };
  }
};

const editOperation = (editOperation) => {
  try {
    //const operation = OperationShema();
    const id = editOperation
    OperationShema
      .findByIdAndUpdate(id, { pagado: 1 })
      .then((data) => data)
      .catch((error) => error);
    return(OperationShema);
  } catch (error) {
    throw{ status: 500, message: error?.message || error }
  }
};
const findOperation = (findOperation) => {
  try {
    const operation = OperationShema;
    const id = findOperation
    operation.findById(id).then((data) => data).catch((error) => error);
      return (operation)
    //return(data)
  } catch (error) {
    throw{ status: 500, message: error?.message || error }
  }
};

module.exports = {
  createNewOperation,
  editOperation,
  findOperation
};