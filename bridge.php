<?php
// Bloque de Backend (Proxy de API en PHP)
// Este bloque se ejecuta solo si se recibe el parámetro 'action' en la URL.
if (isset($_REQUEST['action'])) {

    // --- CONFIGURACIÓN DE SEGURIDAD Y API ---
    $BRIDGE_API_KEY = 'sk-live-f56afcb1f69ccf0a5ca6e997bf28fb4b'; // ¡REEMPLAZAR EN PRODUCCIÓN!
    $BRIDGE_API_BASE_URL = 'https://api.bridge.xyz/v0';

    // --- CABECERAS Y CORS ---
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *'); // Restringir en producción
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Api-Key, Idempotency-Key');

    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }

    // --- FUNCIÓN DE UTILIDAD PARA ERRORES ---
    function send_error($message, $statusCode = 400) {
        http_response_code($statusCode);
        echo json_encode(['error' => ['message' => $message]]);
        exit;
    }

    // --- LÓGICA PRINCIPAL DEL PROXY ---
    $action = $_REQUEST['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];

    if (empty($action)) {
        send_error('La acción no fue especificada.', 400);
    }

    // --- PREPARACIÓN DE LA SOLICITUD cURL ---
    $ch = curl_init();
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Api-Key: ' . $BRIDGE_API_KEY
    ];

    if (isset($_SERVER['HTTP_IDEMPOTENCY_KEY'])) {
        $headers[] = 'Idempotency-Key: ' . $_SERVER['HTTP_IDEMPOTENCY_KEY'];
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $endpointPath = '';
    $queryParams = [];
    $isPostRequest = false;
    $postData = null;

    // --- RUTEO DE ACCIONES A ENDPOINTS DE LA API (CORREGIDO Y AMPLIADO) ---
    switch ($action) {
        case 'getCustomers':
            if ($method !== 'GET') send_error('Método no permitido.', 405);
            $endpointPath = '/customers';
            break;

        case 'createCustomer':
            if ($method !== 'POST') send_error('Método no permitido.', 405);
            $endpointPath = '/customers';
            $isPostRequest = true;
            break;
            
        // CORRECCIÓN: Se ajusta al endpoint documentado POST /customers/tos_links.
        // El ID del cliente se pasa en el cuerpo, no en la URL.
        case 'getTosLink':
            if ($method !== 'POST') send_error('Método no permitido.', 405);
            $endpointPath = '/customers/tos_links';
            $isPostRequest = true;
            // El cuerpo original enviado desde el frontend se pasará directamente.
            break;

        case 'getKycLink': // Requiere ID de cliente
             if ($method !== 'GET') send_error('Método no permitido.', 405);
             if (empty($_GET['customer_id'])) send_error('customer_id es requerido.', 400);
             $endpointPath = '/customers/' . $_GET['customer_id'] . '/kyc_link';
             break;

        case 'getBalance':
            $endpointPath = '/balances';
            break;
        case 'getAssets':
            $endpointPath = '/assets';
            break;
        case 'getAccounts':
            $endpointPath = '/accounts';
            break;
        
        case 'getTransactions':
            $endpointPath = '/transfers';
            $queryParams = [
                'limit' => $_GET['limit'] ?? 20,
                'sort_by' => $_GET['sort_by'] ?? 'created_at',
                'sort_direction' => $_GET['sort_direction'] ?? 'DESC'
            ];
            break;

        case 'createTransfer':
            $endpointPath = '/transfers';
            $isPostRequest = true;
            break;

        case 'getDepositAddress':
             if (empty($_GET['asset_id'])) send_error('asset_id es requerido.', 400);
             $endpointPath = '/deposit-addresses';
             $queryParams = ['asset_id' => $_GET['asset_id']];
             break;

        default:
            send_error("Acción desconocida: '" . htmlspecialchars($action) . "'", 404);
            break;
    }
    
    // --- MANEJO DE DATOS POST ---
    if ($isPostRequest) {
        if ($postData === null) { // Si no se pre-configuró (como en getTosLink)
            $postData = file_get_contents('php://input');
        }
        if (empty($postData)) { send_error('El cuerpo POST está vacío.', 400); }
        json_decode($postData);
        if (json_last_error() !== JSON_ERROR_NONE) { send_error('El cuerpo POST no es un JSON válido.', 400); }
        
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }

    // --- EJECUCIÓN Y RESPUESTA ---
    $url = $BRIDGE_API_BASE_URL . $endpointPath;
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    curl_setopt($ch, CURLOPT_URL, $url);

    $responseBody = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        send_error("Error interno del proxy: " . $curlError, 502);
    } else {
        http_response_code($httpCode);
        echo $responseBody;
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Completo para Bridge.XYZ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .container { max-width: 900px; }
        .btn { transition: all 0.2s ease-in-out; }
        .btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .tab { cursor: pointer; padding: 10px 15px; border-bottom: 3px solid transparent; }
        .tab.active { border-color: #3b82f6; color: #1e40af; font-weight: 600; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(20px); opacity: 0; transition: all 0.5s ease; z-index: 1000; }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        label { font-weight: 500; }
        input:disabled, select:disabled, button:disabled { background-color: #f3f4f6; cursor: not-allowed; }
    </style>
</head>
<body class="bg-gray-50 flex items-start justify-center min-h-screen p-4 pt-8">

    <div class="container w-full mx-auto bg-white p-6 sm:p-8 rounded-xl shadow-2xl">
        <header class="mb-6 text-center">
            <h1 class="text-3xl font-bold text-gray-800">Dashboard para Bridge.XYZ</h1>
            <p class="text-sm text-red-700 font-bold mt-2">DEMO FUNCIONAL PRIVADA.</p>
        </header>

        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-2 sm:space-x-4 overflow-x-auto" aria-label="Tabs">
                <button class="tab active" data-tab="customers">Clientes</button>
                <button class="tab" data-tab="transfer">Transferir</button>
                <button class="tab" data-tab="withdraw">Retirar</button>
                <button class="tab" data-tab="deposit">Depositar</button>
                <button class="tab" data-tab="assets">Activos</button>
            </nav>
        </div>

        <main>
            <!-- Pestaña Clientes -->
            <div id="customers" class="tab-content active space-y-6">
                <section>
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Crear Nuevo Cliente</h2>
                    <form id="createCustomerForm" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div class="sm:col-span-2">
                            <label for="customerEmail" class="block text-sm text-gray-600 mb-1">Email:</label>
                            <input type="email" id="customerEmail" required class="w-full px-4 py-2 border rounded-lg" placeholder="cliente@ejemplo.com">
                        </div>
                        <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 rounded-lg btn">Crear Cliente</button>
                    </form>
                </section>
                <section>
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Lista de Clientes</h2>
                    <div id="customersList" class="space-y-3 p-3 bg-gray-100 rounded-lg min-h-[80px]"></div>
                </section>
            </div>

            <!-- Contexto del Cliente para otras pestañas -->
            <div class="mb-4 hidden" id="customerContext">
                 <label for="onBehalfOfCustomer" class="block text-sm text-gray-600 mb-1 font-semibold">Operando en nombre del Cliente:</label>
                 <select id="onBehalfOfCustomer" class="w-full px-4 py-2 border rounded-lg bg-yellow-100"></select>
            </div>

            <div id="transfer" class="tab-content space-y-6">
                <section>
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Enviar Fondos (Transferencia Interna)</h2>
                    <form id="sendForm" class="space-y-4">
                        <!-- Selector de Cliente se mostrará aquí por JS -->
                        <div>
                            <label for="sendAssetId" class="block text-sm text-gray-600 mb-1">Activo:</label>
                            <select id="sendAssetId" required class="w-full px-4 py-2 border rounded-lg"></select>
                        </div>
                        <div>
                            <label for="destinationAccountId" class="block text-sm text-gray-600 mb-1">ID de Cuenta de Destino:</label>
                            <input type="text" id="destinationAccountId" required class="w-full px-4 py-2 border rounded-lg" placeholder="acc_...">
                        </div>
                        <div>
                            <label for="sendAmount" class="block text-sm text-gray-600 mb-1">Monto:</label>
                            <input type="number" id="sendAmount" step="any" min="0" required class="w-full px-4 py-2 border rounded-lg" placeholder="0.00">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg btn">Enviar Fondos</button>
                    </form>
                </section>
            </div>
            
            <div id="withdraw" class="tab-content space-y-6">
                 <section>
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Retirar Fondos (a Dirección Externa)</h2>
                    <form id="withdrawForm" class="space-y-4">
                         <!-- Selector de Cliente se mostrará aquí por JS -->
                        <div>
                            <label for="withdrawAssetId" class="block text-sm text-gray-600 mb-1">Activo a Retirar:</label>
                            <select id="withdrawAssetId" required class="w-full px-4 py-2 border rounded-lg"></select>
                        </div>
                        <div>
                            <label for="destinationAddress" class="block text-sm text-gray-600 mb-1">Dirección Blockchain de Destino:</label>
                            <input type="text" id="destinationAddress" required class="w-full px-4 py-2 border rounded-lg" placeholder="0x..., bc1..., sol...">
                        </div>
                        <div>
                            <label for="withdrawAmount" class="block text-sm text-gray-600 mb-1">Monto:</label>
                            <input type="number" id="withdrawAmount" step="any" min="0" required class="w-full px-4 py-2 border rounded-lg" placeholder="0.00">
                        </div>
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 rounded-lg btn">Confirmar Retiro</button>
                    </form>
                </section>
            </div>

            <div id="deposit" class="tab-content space-y-6">
                <section>
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Obtener Dirección de Depósito</h2>
                    <div class="space-y-4" id="depositForm">
                         <!-- Selector de Cliente se mostrará aquí por JS -->
                        <div>
                            <label for="depositAssetId" class="block text-sm text-gray-600 mb-1">Selecciona un Activo:</label>
                            <select id="depositAssetId" class="w-full px-4 py-2 border rounded-lg"></select>
                        </div>
                        <button id="getDepositAddrBtn" class="w-full bg-teal-500 hover:bg-teal-600 text-white font-bold py-2.5 rounded-lg btn">Mostrar Dirección</button>
                        <div id="depositAddressResult" class="mt-4 p-4 bg-gray-100 rounded-lg text-sm text-gray-700 min-h-[50px] font-mono break-all"></div>
                    </div>
                </section>
            </div>
            
            <div id="assets" class="tab-content">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                     <section>
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Activos Disponibles</h2>
                        <div id="assetsList" class="space-y-2 p-3 bg-gray-100 rounded-lg min-h-[80px] max-h-96 overflow-y-auto"></div>
                    </section>
                    <section>
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Saldos</h2>
                        <button id="checkBalanceBtn" class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded-lg btn mb-4">Actualizar Saldos</button>
                        <div id="balanceResult" class="p-3 bg-gray-100 rounded-lg text-sm text-gray-700 min-h-[40px]"></div>
                    </section>
                </div>
                 <hr class="my-8">
                 <section>
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Historial de Transferencias</h2>
                    <button id="fetchTransactionsBtn" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-2 px-4 rounded-lg btn mb-4">Cargar Transferencias</button>
                    <div id="transactionsList" class="space-y-3 max-h-80 overflow-y-auto p-3 bg-gray-100 rounded-lg min-h-[60px]"></div>
                </section>
            </div>
        </main>
        
        <div id="toastNotification" class="toast p-3 rounded-lg shadow-lg text-white font-medium"></div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const PROXY_URL = '<?php echo basename($_SERVER['PHP_SELF']); ?>';
    let assetsData = {}; 
    let customersData = {};

    // --- Elementos del DOM ---
    const allButtons = document.querySelectorAll('button');
    const allInputs = document.querySelectorAll('input');
    const allSelects = document.querySelectorAll('select');
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');
    const toast = document.getElementById('toastNotification');
    
    // Formularios y elementos específicos
    const createCustomerForm = document.getElementById('createCustomerForm');
    const sendForm = document.getElementById('sendForm');
    const withdrawForm = document.getElementById('withdrawForm');
    const depositForm = document.getElementById('depositForm');
    const customerContextDiv = document.getElementById('customerContext');
    
    const checkBalanceBtn = document.getElementById('checkBalanceBtn');
    const fetchTransactionsBtn = document.getElementById('fetchTransactionsBtn');
    const getDepositAddrBtn = document.getElementById('getDepositAddrBtn');
    
    // Contenedores de resultados
    const balanceResult = document.getElementById('balanceResult');
    const transactionsListDiv = document.getElementById('transactionsList');
    const depositAddressResult = document.getElementById('depositAddressResult');
    const customersListDiv = document.getElementById('customersList');
    const assetsList = document.getElementById('assetsList');

    const customerSelector = document.getElementById('onBehalfOfCustomer');
    const assetSelectors = [
        document.getElementById('sendAssetId'),
        document.getElementById('withdrawAssetId'),
        document.getElementById('depositAssetId')
    ];
    
    // --- Lógica de UI ---
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(item => item.classList.remove('active'));
            tab.classList.add('active');
            
            const targetId = tab.dataset.tab;
            const target = document.getElementById(targetId);
            tabContents.forEach(content => content.classList.remove('active'));
            target.classList.add('active');

            // Muestra/Oculta el selector de cliente
            if (['transfer', 'withdraw', 'deposit'].includes(targetId)) {
                customerContextDiv.classList.remove('hidden');
                target.prepend(customerContextDiv);
            } else {
                 customerContextDiv.classList.add('hidden');
            }
        });
    });

    function showToast(message, type = 'info') {
        const typeClasses = { info: 'bg-blue-500', success: 'bg-green-600', error: 'bg-red-600' };
        toast.textContent = message;
        toast.className = `toast p-3 rounded-lg shadow-lg text-white font-medium show ${typeClasses[type] || 'bg-gray-700'}`;
        setTimeout(() => { toast.className = 'toast'; }, 5000);
    }

    function setUiLock(locked) {
        allButtons.forEach(btn => btn.disabled = locked);
        allInputs.forEach(input => input.disabled = locked);
        allSelects.forEach(sel => sel.disabled = locked);
    }
    
    // --- FUNCIÓN CENTRAL DE LLAMADA AL PROXY ---
    async function callProxy(action, method = 'get', data = null, headers = {}) {
        const config = {
            method: method,
            url: `${PROXY_URL}?action=${action}`,
            headers: headers
        };
        if (method.toLowerCase() === 'get' && data) config.params = data;
        else if (method.toLowerCase() === 'post' && data) config.data = data;
        
        try {
            const response = await axios(config);
            return response.data;
        } catch (error) {
            console.error(`Error en la acción '${action}':`, error.response || error);
            const devReason = error.response?.data?.rejection_reasons?.[0]?.developer_reason;
            const apiMessage = error.response?.data?.error?.message;
            throw new Error(devReason || apiMessage || error.message || 'Ocurrió un error desconocido.');
        }
    }

    // --- FUNCIONES DE INICIALIZACIÓN Y CARGA DE DATOS ---
    async function initializeDashboard() {
        setUiLock(true);
        showToast('Cargando datos iniciales...', 'info');
        try {
            await loadCustomers();
            await loadAssets();
            await Promise.all([
                handleCheckBalance(),
                loadTransactions()
            ]);
            showToast('Dashboard listo.', 'success');
        } catch (error) {
            showToast(`Error al inicializar: ${error.message}`, 'error');
        } finally {
            setUiLock(false);
        }
    }
    
    async function loadCustomers() {
        const response = await callProxy('getCustomers');
        const customers = Array.isArray(response) ? response : (response?.data || []);
        
        customersData = {};
        customers.forEach(cust => { customersData[cust.id] = cust; });

        customerSelector.innerHTML = customers.map(c => `<option value="${c.id}">${c.email} (ID: ${c.id})</option>`).join('');
        
        if (customers.length === 0) {
            customersListDiv.innerHTML = '<p class="text-gray-600">No se encontraron clientes. Cree uno para empezar.</p>';
            return;
        }
        
        customersListDiv.innerHTML = customers.map(c => `
            <div class="p-3 bg-white rounded border flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div>
                    <p class="font-semibold">${c.email}</p>
                    <p class="text-xs text-gray-500 font-mono">${c.id}</p>
                    <p class="text-xs text-gray-600">KYC Status: <span class="font-bold">${c.kyc_details.status}</span></p>
                </div>
                <div class="flex gap-2">
                    <button data-customer-id="${c.id}" class="btn-sm get-tos-link bg-gray-200 hover:bg-gray-300 text-xs px-2 py-1 rounded">Get ToS Link</button>
                    <button data-customer-id="${c.id}" class="btn-sm get-kyc-link bg-gray-200 hover:bg-gray-300 text-xs px-2 py-1 rounded">Get KYC Link</button>
                </div>
            </div>
        `).join('');
    }

    async function loadAssets() {
        const response = await callProxy('getAssets');
        const assets = Array.isArray(response) ? response : (response?.data || []);
        
        if (assets.length === 0) {
            showToast('No se pudieron cargar los activos.', 'error');
            return;
        }
        
        assetsData = {};
        assets.forEach(asset => { assetsData[asset.id] = asset; });
        
        assetSelectors.forEach(selector => {
            selector.innerHTML = assets.map(asset => `<option value="${asset.id}">${asset.name} (${asset.asset_code})</option>`).join('');
        });
        
        assetsList.innerHTML = assets.map(asset => `
            <div class="p-2 bg-white rounded border">
                <p class="font-semibold">${asset.name} (${asset.asset_code})</p>
                <p class="text-xs text-gray-500 font-mono">ID: ${asset.id}</p>
                <p class="text-xs text-gray-600">Red: ${asset.network}</p>
            </div>`).join('');
    }

    async function handleCheckBalance() {
        balanceResult.innerHTML = '<p class="text-gray-500">Consultando...</p>';
        try {
            const response = await callProxy('getBalance');
            const balances = Array.isArray(response) ? response : (response?.data || []);
            balanceResult.innerHTML = balances.length > 0
                ? `<ul class="list-disc list-inside space-y-1">${balances.map(bal => 
                    `<li>${bal.asset_code}: <span class="font-semibold">${parseFloat(bal.available_balance).toFixed(6)}</span></li>`
                  ).join('')}</ul>`
                : '<p class="text-gray-600">No se encontraron saldos.</p>';
        } catch (error) {
            balanceResult.innerHTML = `<p class="text-red-500">Error: ${error.message}</p>`;
        }
    }
    
    async function loadTransactions() {
        transactionsListDiv.innerHTML = '<p class="text-gray-500 text-sm">Cargando...</p>';
        try {
            const response = await callProxy('getTransactions', 'get');
            const transactions = Array.isArray(response) ? response : (response?.data || []);
            if (transactions.length === 0) {
                transactionsListDiv.innerHTML = '<p class="text-gray-600 text-sm">No hay transferencias recientes.</p>';
                return;
            }
            transactionsListDiv.innerHTML = transactions.map(tx => {
                const isOutbound = ['WITHDRAWAL', 'INTERNAL_OUTBOUND', 'INTERNAL'].includes(tx.type);
                return `
                <div class="p-3 bg-white border rounded-md shadow-sm">
                    <div class="flex justify-between items-center mb-1">
                        <span class="font-semibold text-sm ${isOutbound ? 'text-red-600' : 'text-green-600'}">${isOutbound ? '↓' : '↑'} ${parseFloat(tx.amount).toFixed(6)} ${tx.asset_code}</span>
                        <span class="text-xs text-gray-500">${new Date(tx.created_at).toLocaleString()}</span>
                    </div>
                    <p class="text-xs text-gray-700">Tipo: ${tx.type} | Estado: <span class="font-medium">${tx.status}</span></p>
                    <p class="text-xs text-gray-400 mt-1 font-mono break-all">ID: ${tx.id}</p>
                </div>`;
            }).join('');
        } catch (error) {
            transactionsListDiv.innerHTML = `<p class="text-red-500 text-sm">Error: ${error.message}</p>`;
        }
    }
    
    // --- MANEJADORES DE ACCIONES ---
    
    async function handleCreateCustomer(e) {
        e.preventDefault();
        setUiLock(true);
        const email = document.getElementById('customerEmail').value;
        const payload = { email: email, type: "individual" };
        showToast('Creando cliente...', 'info');
        try {
            const newCustomer = await callProxy('createCustomer', 'post', payload);
            showToast(`Cliente ${newCustomer.email} creado con éxito.`, 'success');
            await loadCustomers(); // Recargar la lista de clientes
            e.target.reset();
        } catch(error) {
            showToast(`Error al crear cliente: ${error.message}`, 'error');
        } finally {
            setUiLock(false);
        }
    }

    async function handleGetLink(e, type) {
        const customerId = e.target.dataset.customerId;
        if (!customerId) return;
        setUiLock(true);
        showToast(`Obteniendo enlace ${type.toUpperCase()}...`, 'info');
        try {
            let result;
            if (type === 'tos') {
                result = await callProxy('getTosLink', 'post', { customer_id: customerId });
            } else { // kyc
                result = await callProxy('getKycLink', 'get', { customer_id: customerId });
            }
            prompt(`Enlace de ${type.toUpperCase()} para ${customersData[customerId].email}:`, result.link);
        } catch (error) {
            showToast(`Error al obtener enlace: ${error.message}`, 'error');
        } finally {
            setUiLock(false);
        }
    }

    async function handleTransferSubmit(event, type) {
        event.preventDefault();
        setUiLock(true);
        
        const customerId = customerSelector.value;
        if (!customerId) {
            showToast('Por favor, seleccione un cliente.', 'error');
            setUiLock(false);
            return;
        }

        const amount = (type === 'internal') ? document.getElementById('sendAmount').value : document.getElementById('withdrawAmount').value;
        const assetId = (type === 'internal') ? document.getElementById('sendAssetId').value : document.getElementById('withdrawAssetId').value;
        const selectedAsset = assetsData[assetId];
        
        if (!selectedAsset) {
            showToast('Error: Activo seleccionado no es válido.', 'error');
            setUiLock(false);
            return;
        }

        let payload = {
            amount: parseFloat(amount).toFixed(selectedAsset.decimals || 6),
            on_behalf_of: customerId, // CRÍTICO: Contexto del cliente
            source: { payment_rail: "ACCOUNT", currency: selectedAsset.asset_code },
            destination: {}
        };

        if (type === 'internal') {
            payload.destination = {
                payment_rail: "INTERNAL_ACCOUNT",
                currency: selectedAsset.asset_code,
                external_account_id: document.getElementById('destinationAccountId').value
            };
        } else { // withdrawal
            payload.destination = {
                payment_rail: selectedAsset.network, // CRÍTICO: La red es el rail de pago
                currency: selectedAsset.asset_code,
                address: document.getElementById('destinationAddress').value
            };
        }

        const idempotencyKey = crypto.randomUUID();
        showToast('Procesando transferencia...', 'info');
        try {
            const result = await callProxy('createTransfer', 'post', payload, { 'Idempotency-Key': idempotencyKey });
            showToast(`Transferencia iniciada! ID: ${result.id}`, 'success');
            event.target.reset();
            setTimeout(() => { handleCheckBalance(); loadTransactions(); }, 2500);
        } catch (error) {
            showToast(`Error al transferir: ${error.message}`, 'error');
        } finally {
            setUiLock(false);
        }
    }

    async function handleGetDepositAddress() {
        setUiLock(true);
        const assetId = document.getElementById('depositAssetId').value;
        if (!assetId) { setUiLock(false); return; }
        
        depositAddressResult.innerHTML = 'Buscando dirección...';
        try {
            const response = await callProxy('getDepositAddress', 'get', { asset_id: assetId });
            const addressInfo = Array.isArray(response) ? response[0] : (response?.data ? response.data[0] : response);

            if (!addressInfo || !addressInfo.address) {
                 depositAddressResult.innerHTML = 'No se encontró una dirección de depósito para este activo.';
            } else {
                depositAddressResult.innerHTML = `
                    <p><strong>Dirección:</strong> ${addressInfo.address}</p>
                    <p><strong>Red:</strong> ${addressInfo.network}</p>
                    ${addressInfo.destination_tag ? `<p><strong>Memo/Tag:</strong> ${addressInfo.destination_tag}</p>` : ''}
                `;
            }
        } catch (error) {
            depositAddressResult.innerHTML = `<p class="text-red-500">Error: ${error.message}</p>`;
        } finally {
            setUiLock(false);
        }
    }
    
    // --- ASIGNACIÓN DE EVENT LISTENERS ---
    createCustomerForm.addEventListener('submit', handleCreateCustomer);
    customersListDiv.addEventListener('click', e => {
        if (e.target.classList.contains('get-tos-link')) handleGetLink(e, 'tos');
        if (e.target.classList.contains('get-kyc-link')) handleGetLink(e, 'kyc');
    });

    sendForm.addEventListener('submit', (e) => handleTransferSubmit(e, 'internal'));
    withdrawForm.addEventListener('submit', (e) => handleTransferSubmit(e, 'withdrawal'));
    checkBalanceBtn.addEventListener('click', handleCheckBalance);
    fetchTransactionsBtn.addEventListener('click', loadTransactions);
    getDepositAddrBtn.addEventListener('click', handleGetDepositAddress);
    
    // --- INICIALIZACIÓN ---
    initializeDashboard();
});
</script>
</body>
</html>
