const mongoose = require("mongoose");

const operation = mongoose.Schema({
    cantidad: {
        type: String,
        require: true,
    },
    crypto: {
        type: String,
        require: true,
    },
    ip: {
        type: String,
        require: true,
    },
    direccion: {
        type: String,
        require: true,
    },
    metodo:{
        type: String,
        require: true,
    },
    pagado:{
        type: Boolean,
        default: 0
    },
    confirmado:{
        type: Boolean,
        default: 0
    },
    referencia:{
        type: String,
        default: 0
    },
    idPago:{
        type: String,
        default: 0
    },
    txId:{
        type: String,
        default: 0
    },
    Idcrypto:{
        type: String,
        default: 0
    },
    createdAt:{
        type: Date,
    },
    updatedAt:{
        type: Date,
    }
});

module.exports = mongoose.model('operation', operation)