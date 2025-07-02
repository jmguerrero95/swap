function getParameterByName(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
    results = regex.exec(location.search);
    return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}
const mostrar = (elemento) => {
    elemento.style.display = 'flex';
}
const mostrarBlock = (elemento) => {
    elemento.style.display = 'block';
}
const ocultar = (elemento) => {
    elemento.style.display = 'none';
}
const animacion1 =  (elemento) => {
    elemento.classList.toggle("vacio_visi");
}
const animacion2 =  (elemento) => {
    elemento.classList.toggle("vacio_invi");
}
function continuar(event){
    document.activeElement.blur(); // Evita pérdida de foco en iOS
    event.preventDefault()
    pesos_rev=document.getElementById('monto1').value;
    let div = document.getElementById('tron');
    let contenidoB = document.getElementById('contenidoB');
    let contenidoA = document.getElementById('contenidoA');
    let mensaje = document.getElementById('vacio');
    if( window.getComputedStyle(div).display !== 'flex' ){
        const monto1 = document.getElementById('monto1').value;
        const monto2 = document.getElementById('monto2').value;
        document.getElementById('pesos').innerHTML = monto1
        document.getElementById('recibe').innerHTML = monto2
        if(monto1 == "" || monto2 == "" || monto1 <= 3999 && monto1 <= 50001){
            let mensaje = document.getElementById('vacio');
            const element = document.createElement("strong");
            element.textContent = "Monto debe ser mayor a 4.000 y menor a 50.000";
            mensaje.replaceChildren(element);
            mensaje.style.opacity = 1
            document.getElementById('monto2').value = ""
            /*animacion1(mensaje);
            setTimeout(animacion2, 4000, mensaje);*/
            return false
            animacion1(mensaje);
            setTimeout(animacion2, 4000, mensaje);
            return false
        }else{
            let mensaje = document.getElementById('vacio');
            mensaje.style.opacity = 0

        }

        mostrar(div);
        return false;
    }
    let direccion = document.getElementById('direccion');
    document.getElementById('direccion_env').innerHTML = direccion.value

    





    axios.get('https://trx.coin.space/api/v1/account/'+direccion.value+'/balance', {responseType: 'json'})
        .then(function(res) {
            if(res.status==200) {
            }

            axios.get('https://ipinfo.io/json', {responseType: 'json'})
            .then(function(res) {
                const cantidad = document.getElementById("monto1").value;
                const crypto = document.getElementById("monto2").value;
                localStorage.setItem("crypto", crypto);
                localStorage.setItem("cantidad", cantidad);
                const ip = res.data.ip;
                const direccion = document.getElementById("direccion").value
                localStorage.setItem("direccion", direccion);
                const metodo = "Womppi";
                axios.post("https://api.globalcoins.co/api/v1/swapRoutes/Operation", {
                    cantidad: cantidad,
                    crypto: crypto,
                    ip: ip,
                    direccion: direccion,
                    metodo: metodo
                }).then((response) => {
                    if(response.data.data == 0){
                        const element = document.createElement("strong");
                        element.textContent = "Solo se permite una operacion cada 24 horas";
                        mensaje.replaceChildren(element);
                        animacion1(mensaje);
                        setTimeout(animacion2, 4000, mensaje);
                        return false
                    }
                    const referencia = response.data.data._id
                    localStorage.setItem("referencia", referencia);
                    document.getElementById('ref_env').innerHTML = referencia
                    //let cantidad2 = cantidad.toString().replace(/\./g, '\\.'); // Escapar el punto
                    //let crypto2 = crypto.toString().replace(/\./g, '\\.'); // Escapar el punto
                    

                    ocultar(div);
                    ocultar(contenidoA);
                    mostrarBlock(contenidoB);
                    const monto1 = document.getElementById('monto1').value;
                    /*const ganancia = monto1*0.10*/
                    const total = Number(monto1)

                    var wompi = document.getElementById("wompi");
                    const monto = total*100
                    const moneda = 'COP'
                    const cadenaConcatenada = referencia+''+monto+''+moneda+'prod_integrity_DOeajtSuAbdI1zJYC11zXGEqQP5XmAov'
                    const hash = CryptoJS.SHA256(cadenaConcatenada).toString(CryptoJS.enc.Hex)
                    wompi.setAttribute("src", 'https://checkout.wompi.co/widget.js');
                    wompi.setAttribute("data-render", 'button');
                    wompi.setAttribute("data-public-key", 'pub_prod_8OjxxgoBzknseaJ7Osbxp725dtzTDHai');
                    wompi.setAttribute("data-currency", 'COP');
                    wompi.setAttribute("data-reference", referencia);
                    wompi.setAttribute("data-amount-in-cents", monto);
                    wompi.setAttribute("data-signature:integrity", hash);
                    wompi.setAttribute("data-redirect-url","https://swap.globalcoins.co/")
                });
            })
            .catch(function(err) {
            })
        })
        .catch(function(err) {
            const element = document.createElement("strong");
            element.textContent = "Dirección TRX invalida";
            mensaje.replaceChildren(element);
            animacion1(mensaje);
            setTimeout(animacion2, 4000, mensaje);
            return false
        })
}
async function crearHash(data) {
    const hashBuffer = await crypto.subtle.digest("SHA-256", data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const hashHex = hashArray.map((b) => b.toString(16).padStart(2, "0")).join("");
    return hashHex
}
function prueba(monto){
    if(monto.value >= 4000 && monto.value <= 50000){
        let mensaje = document.getElementById('vacio');
        mensaje.style.opacity = 0
        let dolar_pre = 0;
        dolar_pre = parseFloat(monto.value) / parseFloat(localStorage.pre_dol);
        const dolar = parseFloat(dolar_pre).toFixed(2);

        const Prefinal = dolar / localStorage.pre_tr
        const resta = Prefinal*0.10
        const final = Prefinal - resta
        document.getElementById('monto2').value = final.toFixed(2)



        /*axios.get('https://www.datos.gov.co/resource/32sa-8pi3.json?$limit=1', {responseType: 'json'})
        .then(function(res) {
            //const dolar = monto.value / res.dat[0].valor
            let dolar = 0;
            dolar = parseFloat(monto.value) / parseFloat(res.data[0].valor);
            dolar = parseFloat(dolar).toFixed(2);
            axios.get('https://api.diadata.org/v1/assetInfo/Tron/0x0000000000000000000000000000000000000000', {responseType: 'json'})
            .then(function(res){
                const Prefinal = dolar / res.data.Price
                const resta = Prefinal*0.10
                const final = Prefinal - resta
                document.getElementById('monto2').value = final.toFixed(2)
            })
            .catch(function(res){

            })

        })
        .catch(function(err) {
        })*/
    }else{
        let mensaje = document.getElementById('vacio');
        const element = document.createElement("strong");
        element.textContent = "Monto debe ser mayor a 4.000 y menor a 50.000";
        mensaje.replaceChildren(element);
        mensaje.style.opacity = 1
        document.getElementById('monto2').value = ""
        /*animacion1(mensaje);
        setTimeout(animacion2, 4000, mensaje);*/
        return false
    }
}
function actualizarTel(opcion){
    let phone = document.getElementById('block_phone');
    let boton = document.getElementById('boton');
    if(opcion.value != 0){
        boton.disabled = false; 
    }
    if(opcion.value == 88){
        mostrarBlock(phone)
    }
    else
        ocultar(phone)
}

function convertirCriptoAPesos(criptoInput) {
    // Reemplaza coma por punto para asegurar el formato decimal correcto
    let valorTexto = criptoInput.value.replace(/,/g, '.');
    let cryptoAmount = parseFloat(valorTexto);

    if (!isNaN(cryptoAmount) && cryptoAmount > 0) {
        // Llamada a la API para obtener la tasa de cambio de pesos por dólar
        let conversionRate = parseFloat(localStorage.pre_dol);

        let cryptoPrice = parseFloat(localStorage.pre_tr); // Precio de 1 cripto en dólares
                        
        // Convertir la cantidad de cripto a dólares
        let dollars = cryptoAmount * cryptoPrice;
        // Convertir dólares a pesos
        let pesos = dollars * conversionRate;
        // Restar el 10% de ganancia
        let finalPesos = pesos + (pesos * 0.10);
        // Asignar el resultado formateado a dos decimales
        document.getElementById('monto1').value = finalPesos.toFixed(2);


        /*axios.get('https://www.datos.gov.co/resource/32sa-8pi3.json?$limit=1', { responseType: 'json' })
            .then(function(res) {
                let conversionRate = parseFloat(res.data[0].valor); // Pesos por dólar

                // Llamada a la API para obtener el precio de la cripto en dólares
                axios.get('https://api.diadata.org/v1/assetInfo/Tron/0x0000000000000000000000000000000000000000', { responseType: 'json' })
                    .then(function(res2) {
                        let cryptoPrice = parseFloat(res2.data.Price); // Precio de 1 cripto en dólares
                        
                        // Convertir la cantidad de cripto a dólares
                        let dollars = cryptoAmount * cryptoPrice;
                        // Convertir dólares a pesos
                        let pesos = dollars * conversionRate;
                        // Restar el 10% de ganancia
                        let finalPesos = pesos + (pesos * 0.10);
                        // Asignar el resultado formateado a dos decimales
                        document.getElementById('monto1').value = finalPesos.toFixed(2);
                    })
                    .catch(function(error) {
                        console.error("Error en la API de cripto:", error);
                    });
            })
            .catch(function(err) {
                console.error("Error en la API de conversión:", err);
            });*/
    } else {
        // Si el valor ingresado no es válido, se limpia el campo de resultado
        document.getElementById('monto1').value = "";
    }
}


function pago(){
    Event.preventDefault
    window.location.href("https://swap.globalcoins.co/")
    /*let contenidoA = document.getElementById('contenidoA')
    let pagado = document.getElementById('pagado')
    ocultar(pagado);
    mostrarBlock(contenidoA);*/
}
function revision_pago(){
    let pagando_banc = document.getElementById('pagando_banc');
    let idpagado = document.getElementById('pagado');
    const id = localStorage.getItem("id");
    const crypto = localStorage.getItem("crypto");
    const direccion = localStorage.getItem("direccion");
    axios.get('http://31.220.84.241:3003/api/v1/swapRoutes/FindOperation/'+id, {responseType: 'json'})
        .then(function(res) {
            const pagado = res.data

            if(pagado == 0){

            }
            if(pagado == 1){
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
                        const cantidad = document.getElementById('cantidad');
                        const element = document.createElement("span");
                        element.textContent = final;
                        cantidad.replaceChildren(element);
                        const destino = document.getElementById('destino');
                        const element2 = document.createElement("span");
                        element2.textContent = direccion;
                        destino.replaceChildren(element2);
                        ocultar(pagando_banc);
                        mostrarBlock(idpagado);
                        const idinterval = localStorage.getItem("idinterval");
                        clearInterval(idinterval);


                    })
                    .catch(function(res){

                    })
                })
            }
        })
        .catch(function(err) {

        })
}
window.onload = function(){
    function miFuncion() {
        axios.get('https://www.datos.gov.co/resource/32sa-8pi3.json?$limit=1', {responseType: 'json'})
        .then(function(res) {
            localStorage.pre_dol=res.data[0].valor
            console.log(res.data[0].valor)
            axios.get('https://api.diadata.org/v1/assetInfo/Tron/0x0000000000000000000000000000000000000000', {responseType: 'json'})
            .then(function(res){
                localStorage.pre_tr=res.data.Price
                console.log(res.data.Price)
            })
            .catch(function(res){

            })

        })
        .catch(function(err) {
        })
    }
        console.log("Ejecutando tarea...");
        // Tu lógica aquí

    // Ejecutar la función inmediatamente
    miFuncion();

    // Configurar el intervalo para repetir cada 10 segundos (10000 milisegundos)
    setInterval(miFuncion, 30000);






    const id = getParameterByName('id');
    let contenidoA = document.getElementById('contenidoA')
    let pagando = document.getElementById('contenidoC')
    let pagado = document.getElementById('pagado')
    if(id != ""){
        ocultar(contenidoA);
        mostrarBlock(pagando);
        blockPagando = setInterval(function () {
            axios.post("https://api.globalcoins.co/api/v1/swapRoutes/webhook", {
                id: id,
            }).then((response) => {
                if(response.data.status == 1){
                    clearInterval(blockPagando)
                    direccion = localStorage.direccion;
                    crypto = localStorage.crypto;
                    referencia = localStorage.referencia;
                    cantidad = localStorage.cantidad;
                    document.getElementById('destino').innerHTML = direccion
                    document.getElementById('txid').innerHTML = response.data.data[0].txId
                    document.getElementById('tron').href = "https://tronscan.org/#/transaction/"+response.data.data[0].txId
                    ocultar(pagando);
                    mostrarBlock(pagado);
                    let cantidad2 = cantidad.toString().replace(/\./g, '\\.'); // Escapar el punto
                    let crypto2 = crypto.toString().replace(/\./g, '\\.'); // Escapar el punto
                    axios.get("https://api.telegram.org/bot1147905415:AAHcTAvqOc2X4_hBbZ2p3D-xpeOWQoU6xWI/sendMessage?text=%E2%9C%85%20Nueva%20Orden%0ARefencia:%20"+referencia+"%0AMonto%20pesos:%20"+cantidad2+"%0AMonto%20Cripto:%20"+crypto2+"%0Adireccion:%20`"+direccion+"`&chat_id=-1002437780261&parse_mode=MarkdownV2")
                }
            }).catch(function(err) {
            }) 
        }, 30000);
    }
}