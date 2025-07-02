const { v4: uuid } = require("uuid");
const Operation = require("../database/Operation");
const moment = require('moment');
var now = moment()

const createNewOperation = (newOperation) => {
    const operationToInsert = {
      ...newOperation,
      createdAt: now,
      updatedAt: now,
    };
    try {
      const createdOperation = Operation.createNewOperation(operationToInsert);
      console.log(createdOperation)
      return createdOperation;
    } catch (error) {
      throw error;
    }
};

const editOperation = (editOperation) => {
  const idOperation = editOperation
  try {
    const editOperation = Operation.editOperation(idOperation);
    console.log(editOperation)
    return editOperation;
  } catch (error) {
    throw error;
  }
};

const findOperation = (findOperation) => {
  const idOperation = findOperation
  try {
    const findOperation = Operation.findOperation(idOperation);
    console.log(findOperation)
    return findOperation;
  } catch (error) {
    throw error;
  }
};

module.exports = {
    createNewOperation,
    editOperation,
    findOperation
  };