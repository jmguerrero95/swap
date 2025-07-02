const operationService = require("../services/operationService");
const OperationShema = require("../database/models/operationModel");
const axios = require('axios');
const moment = require('moment');

const createNewOperation = (req, res) => {
    const { body } = req;

    if (
      !body.cantidad ||
      !body.crypto ||
      !body.ip ||
      !body.direccion ||
      !body.metodo
    ) {
      res.status(400).send({
        status: "FAILED",
        data: {
          error:
            "One of the following keys is missing or is empty in request body: 'name', 'lastname', 'email', 'phone'",
        },
      });
    }
  
    const newOperation = {
      cantidad: body.cantidad,
      ip: body.ip,
      crypto: body.crypto,
      direccion: body.direccion,
      metodo: body.metodo,
    };
    const direccion = body.direccion
    const operation = OperationShema;
    try {
      operation.find({direccion: direccion}).sort({$natural:-1}).limit(1).then((data) => {
        const createdOperation = operationService.createNewOperation(newOperation);
        res.status(201).send({ status: "OK", data: createdOperation });
        let cantidad2 = body.cantidad.toString().replace(/\./g, '\\.'); // Escapar el punto
        let crypto2 = body.crypto.toString().replace(/\./g, '\\.'); // Escapar el punto
        axios.get("https://api.telegram.org/bot1147905415:AAHcTAvqOc2X4_hBbZ2p3D-xpeOWQoU6xWI/sendMessage?text=%E2%9D%95%20Nueva%20Orden%0ARefencia:%20"+createdOperation._id+"%0AMonto%20pesos:%20"+cantidad2+"%0AMonto%20Cripto:%20"+crypto2+"%0Adireccion:%20`"+direccion+"`&chat_id=-1002437780261&parse_mode=MarkdownV2")
        /*const now = moment()
        const fechAnterior = data[0].createdAt
        var resultado = now.diff(moment(fechAnterior), 'hours');
        if(resultado >= 24 || resultado >= "24"){
          const createdOperation = operationService.createNewOperation(newOperation);
          res.status(201).send({ status: "OK", data: createdOperation });
        }else{
          res.status(200).send({data: 0})
        }*/
      })
    } catch (error) {
      res
        .status(error?.status || 500)
        .send({ status: "FAILDED", data: { error: error?.message || error } });
    }
};

const operationComplete = (req, res) => {
  const { body } = req;
  const to = body.to


  const operation = OperationShema;
  operation.find({direccion: to}).then((data) => {
    operation
      .findByIdAndUpdate(data[0]._id, { pagado: 1 })
      .then((data) => {
        res.status(201).send({ status: "OK", data: data});
      })
      .catch((error) => error);
  }).catch((error) => error);
}

const editOperation = (req, res) =>{
  const id = req.params.id

  if (
    !id
  ) {
    res.status(400).send({
      status: "FAILED",
      data: {
        error:
          "One of the following keys is missing or is empty in request body: 'id'",
      },
    });
  }
  try {
    const editOperation = operationService.editOperation(id);
    res.status(201).send({ status: "OK", data: editOperation});
  }catch (error) {
    res
      .status(error?.status || 500)
      .send({ status: "FAILDED", data: { error: error?.message || error } });
  }
};

const findOperation = (req, res) =>{
  const id = req.params.id

  if (
    !id
  ) {
    res.status(400).send({
      status: "FAILED",
      data: {
        error:
          "One of the following keys is missing or is empty in request body: 'id'",
      },
    });
  }
  try {
      const operation = OperationShema;
      operation.findById(id).then((data) => {
        if(data.pagado == true){
          axios.get('https://www.datos.gov.co/resource/32sa-8pi3.json?$limit=1', {responseType: 'json'})
            .then(function(res) {
                //const dolar = monto.value / res.dat[0].valor
                let dolar = 0;
                dolar = parseFloat(res.data.data.cantidad) / parseFloat(res.data[0].valor);
                dolar = parseFloat(dolar).toFixed(2);
                axios.get('https://api.diadata.org/v1/assetInfo/Tron/0x0000000000000000000000000000000000000000', {responseType: 'json'})
                .then(function(res){
                    const Prefinal = dolar / res.data.Price
                    const resta = Prefinal*0.10
                    const final = Prefinal - resta

                      axios.get('https://new.cryptocurrencyapi.net/api/trx/.send?key=11581be2ec-233269bee9-e902374022-481a2e0c24&amount='+final+'&token=USDT',{responseType: 'json'})
                      .then(function(res){
                          res.status(201).send({ status: "OK", data: 1});
                      })
                      .catch(function(err){
                          alert("Error: "+err)
                      })

                })
                .catch(function(res){

                })
            })
        }
        res.status(201).send({ status: "OK", data: 0});
      }).catch((error) => error);
      res
        .status(error?.status || 500)
        .send({ status: "FAILDED", data: { error: error?.message || error } });
    /*const findOperation = operationService.findOperation(id);
    res.status(201).send({ status: "OK", data: findOperation});*/
  }catch (error) {
    res
      .status(error?.status || 500)
      //.send({ status: "FAILDED", data: { error: error?.message || error } });
  }
};
const comprobar = (req, res) =>{
  const idPago = req.body.data.transaction.id
  const referencia = req.body.data.transaction.reference
  if(req.body.data.transaction.status == "APPROVED"){
    const operation = OperationShema;
    operation.findByIdAndUpdate(referencia, { pagado: true, idPago: idPago })
    .then((data) => {
      axios.get('https://new.cryptocurrencyapi.net/api/trx/.send?key=11581be2ec-233269bee9-e902374022-481a2e0c24&address='+data.direccion+'&amount='+data.crypto,{responseType: 'json'})
      .then(function(res2){
        operation.findByIdAndUpdate(referencia, { Idcrypto: res2.data.result })
        .then((data) => {
        }).catch(function(err){
        })
      }).catch(function(err){
      })
      res.status(200).send({ status: "OK", data: data});
    })
    .catch((error) => error);
  }
}
const ipn = (req, res) =>{
  const operation = OperationShema;
  operation.findOneAndUpdate({Idcrypto: req.body.id}, { txId: req.body.txid, confirmado: true }).then((data) => {
  }).catch((err) => {

  })
  res.status(200).send({})
}
const webhook = (req, res) =>{
  const { body } = req;
  const idPago = body.id
  const operation = OperationShema;
  operation.find({idPago: idPago}).then((data) => {
    if(data[0].confirmado == true){
      res.status(200).send({ status: 1, data: data })
    }else{
      res.status(200).send({ status: 0 })
    }
  }).catch((error) => error);
}

module.exports = {
    createNewOperation,
    operationComplete,
    editOperation,
    findOperation,
    comprobar,
    ipn,
    webhook
};