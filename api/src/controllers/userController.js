const userService = require("../services/userService");

const createNewUser = (req, res) => {
    const { body } = req;
  
    if (
      !body.name ||
      !body.lastName ||
      !body.email ||
      !body.phone
    ) {
      res.status(400).send({
        status: "FAILED",
        data: {
          error:
            "One of the following keys is missing or is empty in request body: 'name', 'lastname', 'email', 'phone'",
        },
      });
    }
  
    const newUser = {
      name: body.name,
      lastName: body.lastName,
      email: body.email,
      phone: body.phone,
    };
  
    try {
      const createduser = userService.createNewUser(newUser);
      res.status(201).send({ status: "OK", data: createduser });
    } catch (error) {
      res
        .status(error?.status || 500)
        .send({ status: "FAILDED", data: { error: error?.message || error } });
    }
};
module.exports = {
    createNewUser,
};