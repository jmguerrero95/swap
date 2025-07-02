const { v4: uuid } = require("uuid");
const User = require("../database/User");

const createNewUser = (newUser) => {
    const userToInsert = {
      ...newUser,
      createdAt: new Date().toLocaleString("en-US", { timeZone: "UTC" }),
      updatedAt: new Date().toLocaleString("en-US", { timeZone: "UTC" }),
    };
    try {
      const createdUser = User.createNewUser(userToInsert);
      return createdUser;
    } catch (error) {
      throw error;
    }
};

module.exports = {
    createNewUser,
  };