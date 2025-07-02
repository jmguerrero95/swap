const mongoose = require("mongoose");
const validator = require("validator");

const userSchema = mongoose.Schema({
    name: {
        type: String,
        require: true,
    },
    lastName: {
        type: String,
        require: true,
    },
    email: {
        type: String,
        require: true,
        validate: (value) => {
            return validator.isEmail(value)
        }
    },
    phone:{
        type: Number,
        require: true,
    },
    createdAt:{
        type: Date,
    },
    updatedAt:{
        type: Date,
    }
});

module.exports = mongoose.model('user', userSchema)