const express = require("express");
const userSchema = require("./models/userModel");
const { saveToDatabase } = require("./utils");

const createNewUser = (newUser) => {
    try {
        const user = userSchema(newUser);
        user
            .save()
            .then((data) => data)
            .catch((error) => error);
    } catch (error) {
      throw { status: 500, message: error?.message || error };
    }
  };

  module.exports = {
    createNewUser
  };