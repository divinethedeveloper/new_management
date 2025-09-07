<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockLens - Store Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
            animation-delay: 0.2s;
        }
        .card, .table-row {
            opacity: 0;
        }
        .skeleton {
            background-color: #e2e8f0;
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px -8px rgba(0, 0, 0, 0.1);
        }
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-ok { background-color: var(--success); }
        .status-watch { background-color: var(--warning); }
        .status-low { background-color: var(--danger); }
        .custom-checkbox {
            position: absolute;
            opacity: 0;
        }
        .custom-checkbox + label {
            position: relative;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
        }
        .custom-checkbox + label:before {
            content: '';
            margin-right: 10px;
            width: 18px;
            height: 18px;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 4px;
        }
        .custom-checkbox:checked + label:before {
            background: var(--primary);
            border-color: var(--primary);
        }
        .custom-checkbox:checked + label:after {
            content: '';
            position: absolute;
            left: 5px;
            top: 9px;
            background: white;
            width: 2px;
            height: 2px;
            box-shadow: 
                2px 0 0 white,
                4px 0 0 white,
                4px -2px 0 white,
                4px -4px 0 white,
                4px -6px 0 white,
                4px -8px 0 white;
            transform: rotate(45deg);
        }
        .table-container {
            max-height: 500px;
            overflow-y: auto;
        }
        .sticky-header {
            position: sticky;
            top: 0;
            background-color: white;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #c5c5c5;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            padding: 20px;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .card, table { box-shadow: none; border: 1px solid #e2e8f0; }
        }
        .error-message {
            color: var(--danger);
            font-size: 0.875rem;
            text-align: center;
            padding: 1rem;
        }
        .uneditable-field {
            background-color: #f1f1f1;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-white shadow-sm py-4 px-6 sticky top-0 z-50 no-print">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
            <div class="flex items-center space-x-3">
                <div class="bg-blue-100 p-2 rounded-lg">
                    <i class="fas fa-boxes text-blue-600 text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Ministry of Finance – StockLens Manager</h1>
                    <p class="text-sm text-gray-500">Store Manager Interface</p>
                </div>
            </div>
            <div class="flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-y-0 md:space-x-4">
                <button id="add-item-btn" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center transition">
                    <i class="fas fa-plus mr-2"></i> Add Item
                </button>
                <button id="add-transaction-btn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm flex items-center transition">
                    <i class="fas fa-exchange-alt mr-2"></i> Add Transaction
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 py-6 px-4 md:px-6">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row space-y-6 lg:space-y-0 lg:space-x-6">
            <!-- Side Panel -->
            <aside class="w-full lg:w-1/5 bg-white shadow-lg rounded-xl p-5 no-print">
                <h2 class="text-lg font-semibold mb-5 text-gray-700 flex items-center">
                    <i class="fas fa-sliders-h mr-2 text-blue-500"></i> Quick Filters
                </h2>
                <ul class="space-y-2 mb-6 quick-filters">
                    <li><button data-filter="all" class="w-full text-left px-4 py-2.5 rounded-lg transition flex items-center text-blue-600 bg-blue-50 border border-blue-100"><i class="fas fa-cubes mr-3"></i> All Items</button></li>
                    <li><button data-filter="low" class="w-full text-left px-4 py-2.5 rounded-lg transition flex items-center text-gray-600 hover:bg-gray-50"><i class="fas fa-exclamation-triangle mr-3 text-orange-500"></i> Low Stock Only</button></li>
                    <li><button data-filter="watch" class="w-full text-left px-4 py-2.5 rounded-lg transition flex items-center text-gray-600 hover:bg-gray-50"><i class="fas fa-eye mr-3 text-yellow-500"></i> Watch Items Only</button></li>
                    <li><button data-filter="high" class="w-full text-left px-4 py-2.5 rounded-lg transition flex items-center text-gray-600 hover:bg-gray-50"><i class="fas fa-arrow-circle-up mr-3 text-green-500"></i> High Stock Only</button></li>
                </ul>
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <button id="export-report" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2.5 rounded-lg text-sm flex items-center justify-center transition">
                        <i class="fas fa-file-export mr-2"></i> Export Report
                    </button>
                </div>
            </aside>

            <!-- Main Section -->
            <section class="w-full lg:w-4/5">
                <!-- Stock Report Table -->
                <div class="bg-white shadow-lg rounded-xl overflow-hidden animate-fade-in">
                    <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-700">Inventory Management</h2>
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center text-sm">
                                <input type="checkbox" id="low-stock-toggle" class="custom-checkbox">
                                <label for="low-stock-toggle">Show low stock only</label>
                            </div>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 sticky-header">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock on Hand</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="table-body" class="bg-white divide-y divide-gray-100">
                                <!-- Rows will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                    <div class="px-5 py-3 bg-white border-t border-gray-200 flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p id="pagination-info" class="text-sm text-gray-700">Loading...</p>
                            </div>
                            <div>
                                <nav id="pagination-nav" class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination"></nav>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="bg-white shadow-lg rounded-xl overflow-hidden animate-fade-in mt-6">
                    <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-700">Transaction History</h2>
                    </div>
                    <div class="table-container">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 sticky-header">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="transactions-table-body" class="bg-white divide-y divide-gray-100">
                                <!-- Rows will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Modals -->
    <div id="item-modal" class="modal">
        <div class="modal-content">
            <h2 id="item-modal-title" class="text-lg font-semibold mb-4">Add Item</h2>
            <form id="item-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Item Code</label>
                    <input type="text" id="item-code" name="ItemCode" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <input type="text" id="item-description" name="ItemDescription" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Unit</label>
                    <input type="text" id="item-unit" name="Unit" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Reorder Level</label>
                    <input type="number" id="item-reorder" name="ReorderLevel" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Opening Stock</label>
                    <input type="number" id="item-opening" name="OpeningStock" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="item-modal-close" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div id="transaction-modal" class="modal">
        <div class="modal-content">
            <h2 id="transaction-modal-title" class="text-lg font-semibold mb-4">Add Transaction</h2>
            <form id="transaction-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Item</label>
                    <select id="transaction-item" name="ItemDisplay" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" required>
                        <option value="">Select Item</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Item Code</label>
                    <input type="text" id="transaction-item-code" name="ItemCode" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 uneditable-field" readonly required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Transaction Type</label>
                    <select id="transaction-type" name="TransactionType" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" required>
                        <option value="Receipt">Receipt</option>
                        <option value="Issue">Issue</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Quantity</label>
                    <input type="number" id="transaction-quantity" name="Quantity" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Date</label>
                    <input type="date" id="transaction-date" name="TransactionDate" class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2" required>
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" id="transaction-modal-close" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg">Cancel</button>
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t py-4 text-center text-gray-500 text-sm mt-8 no-print">
        <div class="max-w-7xl mx-auto px-4">
            <p>Store Management System – Ministry of Finance © 2025</p>
            <p class="mt-1">Manager Interface</p>
        </div>
    </footer>

    <script>
        let currentPage = 1;
        const limit = 20;
        let currentFilter = 'all';
        let editingItem = null;
        let editingTransaction = null;
        const today = '2025-09-05'; // Current date

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize modals
            const itemModal = document.getElementById('item-modal');
            const transactionModal = document.getElementById('transaction-modal');
            const itemForm = document.getElementById('item-form');
            const transactionForm = document.getElementById('transaction-form');
            const transactionItemSelect = document.getElementById('transaction-item');
            const transactionItemCode = document.getElementById('transaction-item-code');
            const transactionDate = document.getElementById('transaction-date');

            // Set default date
            transactionDate.value = today;

            // Load initial data
            loadItems();
            loadTransactions();
            loadItemOptions();

            // Event listeners
            document.getElementById('add-item-btn').addEventListener('click', () => {
                editingItem = null;
                document.getElementById('item-modal-title').textContent = 'Add Item';
                itemForm.reset();
                document.getElementById('item-code').disabled = false;
                itemModal.style.display = 'flex';
            });

            document.getElementById('add-transaction-btn').addEventListener('click', () => {
                editingTransaction = null;
                document.getElementById('transaction-modal-title').textContent = 'Add Transaction';
                transactionForm.reset();
                transactionItemSelect.value = '';
                transactionItemCode.value = '';
                transactionDate.value = today;
                transactionModal.style.display = 'flex';
            });

            document.getElementById('item-modal-close').addEventListener('click', () => {
                itemModal.style.display = 'none';
            });

            document.getElementById('transaction-modal-close').addEventListener('click', () => {
                transactionModal.style.display = 'none';
            });

            transactionItemSelect.addEventListener('change', () => {
                const selectedOption = transactionItemSelect.options[transactionItemSelect.selectedIndex];
                transactionItemCode.value = selectedOption ? selectedOption.dataset.itemCode : '';
            });

            document.querySelectorAll('.quick-filters button').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.quick-filters button').forEach(b => {
                        b.classList.remove('text-blue-600', 'bg-blue-50', 'border', 'border-blue-100');
                        b.classList.add('text-gray-600', 'hover:bg-gray-50');
                    });
                    btn.classList.add('text-blue-600', 'bg-blue-50', 'border', 'border-blue-100');
                    currentFilter = btn.dataset.filter;
                    currentPage = 1;
                    loadItems();
                    document.getElementById('low-stock-toggle').checked = (currentFilter === 'low');
                });
            });

            document.getElementById('low-stock-toggle').addEventListener('change', function() {
                currentFilter = this.checked ? 'low' : 'all';
                currentPage = 1;
                loadItems();
                document.querySelectorAll('.quick-filters button').forEach(b => {
                    if (b.dataset.filter === currentFilter) {
                        b.click();
                    }
                });
            });

            itemForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(itemForm);
                const data = Object.fromEntries(formData);
                const endpoint = editingItem ? 'update_item' : 'add_item';
                try {
                    const response = await fetch(`../../backend/manager_api.php?endpoint=${endpoint}${editingItem ? '&ItemCode=' + encodeURIComponent(editingItem) : ''}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        itemModal.style.display = 'none';
                        loadItems();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error submitting item: ' + error.message
                    });
                }
            });

            transactionForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(transactionForm);
                const data = Object.fromEntries(formData);
                // Use ItemCode from the readonly field
                data.ItemCode = transactionItemCode.value;
                delete data.ItemDisplay; // Remove display field
                const endpoint = editingTransaction ? 'update_transaction' : 'add_transaction';
                try {
                    const response = await fetch(`../../backend/manager_api.php?endpoint=${endpoint}${editingTransaction ? '&TransactionID=' + editingTransaction : ''}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: result.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        transactionModal.style.display = 'none';
                        loadItems();
                        loadTransactions();
                        loadItemOptions();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error submitting transaction: ' + error.message
                    });
                }
            });

            document.getElementById('export-report').addEventListener('click', exportToCSV);
        });

        function showSkeletonLoading(type) {
            if (type === 'items') {
                const tbody = document.getElementById('table-body');
                tbody.innerHTML = '';
                for (let i = 0; i < 10; i++) {
                    const row = `<tr class="table-row"><td class="px-6 py-4"><div class="skeleton h-4 w-20"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-48"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-16"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-12"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-12"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-20"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-24"></div></td></tr>`;
                    tbody.insertAdjacentHTML('beforeend', row);
                }
            } else if (type === 'transactions') {
                const tbody = document.getElementById('transactions-table-body');
                tbody.innerHTML = '';
                for (let i = 0; i < 5; i++) {
                    const row = `<tr class="table-row"><td class="px-6 py-4"><div class="skeleton h-4 w-16"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-20"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-16"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-12"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-24"></div></td><td class="px-6 py-4"><div class="skeleton h-4 w-24"></div></td></tr>`;
                    tbody.insertAdjacentHTML('beforeend', row);
                }
            }
        }

        async function loadItems() {
            showSkeletonLoading('items');
            const tbody = document.getElementById('table-body');
            const paginationInfo = document.getElementById('pagination-info');
            try {
                const response = await fetch(`../../backend/manager_api.php?endpoint=items&filter=${currentFilter}&page=${currentPage}&limit=${limit}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load items');
                }
                tbody.innerHTML = '';
                (data.data || []).forEach((item, index) => {
                    const statusClass = item.Status === 'OK' ? 'bg-green-100 text-green-800 status-ok' : item.Status === 'Watch' ? 'bg-yellow-100 text-yellow-800 status-watch' : 'bg-red-100 text-red-800 status-low';
                    const row = `
                        <tr class="table-row animate-fade-in hover:bg-gray-50 transition" style="--row-index: ${index + 1};">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.ItemCode || ''}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.ItemDescription || ''}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.Unit || ''}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.StockOnHand || 0}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.ReorderLevel || 0}</td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}"><span class="status-indicator"></span>${item.Status || 'N/A'}</span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button class="edit-item text-blue-600 hover:text-blue-800 mr-2" data-item='${JSON.stringify(item)}'><i class="fas fa-edit"></i></button>
                                <button class="delete-item text-red-600 hover:text-red-800" data-item-code="${item.ItemCode}"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>`;
                    tbody.insertAdjacentHTML('beforeend', row);
                });

                paginationInfo.textContent = `Showing ${(currentPage - 1) * limit + 1} to ${Math.min(currentPage * limit, data.total || 0)} of ${data.total || 0} results`;

                const paginationNav = document.getElementById('pagination-nav');
                paginationNav.innerHTML = '';
                const totalPages = Math.ceil((data.total || 0) / limit);
                if (currentPage > 1) {
                    paginationNav.insertAdjacentHTML('beforeend', `<a href="#" data-page="${currentPage - 1}" class="pagination-link relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"><i class="fas fa-chevron-left"></i></a>`);
                }
                for (let p = 1; p <= totalPages; p++) {
                    const activeClass = p === currentPage ? 'bg-blue-50 text-blue-600 hover:bg-blue-100' : 'bg-white text-gray-500 hover:bg-gray-50';
                    paginationNav.insertAdjacentHTML('beforeend', `<a href="#" data-page="${p}" class="pagination-link relative inline-flex items-center px-4 py-2 border border-gray-300 ${activeClass} text-sm font-medium">${p}</a>`);
                }
                if (currentPage < totalPages) {
                    paginationNav.insertAdjacentHTML('beforeend', `<a href="#" data-page="${currentPage + 1}" class="pagination-link relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"><i class="fas fa-chevron-right"></i></a>`);
                }

                document.querySelectorAll('.pagination-link').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        currentPage = parseInt(e.target.closest('a').dataset.page);
                        loadItems();
                    });
                });

                document.querySelectorAll('.edit-item').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const item = JSON.parse(btn.dataset.item);
                        editingItem = item.ItemCode;
                        document.getElementById('item-modal-title').textContent = 'Edit Item';
                        document.getElementById('item-code').value = item.ItemCode;
                        document.getElementById('item-code').disabled = true;
                        document.getElementById('item-description').value = item.ItemDescription;
                        document.getElementById('item-unit').value = item.Unit;
                        document.getElementById('item-reorder').value = item.ReorderLevel;
                        document.getElementById('item-opening').value = item.OpeningStock;
                        document.getElementById('item-modal').style.display = 'flex';
                    });
                });

                document.querySelectorAll('.delete-item').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const result = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Do you want to delete this item?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Yes, delete it'
                        });
                        if (result.isConfirmed) {
                            try {
                                const response = await fetch(`../../backend/manager_api.php?endpoint=delete_item&ItemCode=${encodeURIComponent(btn.dataset.itemCode)}`, { method: 'POST' });
                                const result = await response.json();
                                if (result.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted',
                                        text: result.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    loadItems();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: result.message
                                    });
                                }
                            } catch (error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Error deleting item: ' + error.message
                                });
                            }
                        }
                    });
                });
            } catch (error) {
                console.error('Error loading items:', error);
                tbody.innerHTML = `<tr><td colspan="7" class="error-message">Failed to load inventory data: ${error.message}. Please check the server logs or try again later.</td></tr>`;
                paginationInfo.textContent = 'Error loading data';
            }
        }

        async function loadTransactions() {
            showSkeletonLoading('transactions');
            try {
                const response = await fetch(`../../backend/manager_api.php?endpoint=transactions`);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load transactions');
                }
                const tbody = document.getElementById('transactions-table-body');
                tbody.innerHTML = '';
                (data.data || []).forEach((trans, index) => {
                    const row = `
                        <tr class="table-row animate-fade-in hover:bg-gray-50 transition" style="--row-index: ${index + 1};">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${trans.TransactionID || ''}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trans.ItemCode || ''}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${trans.TransactionType || ''}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trans.Quantity || 0}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${trans.TransactionDate || ''}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button class="edit-transaction text-blue-600 hover:text-blue-800 mr-2" data-transaction='${JSON.stringify(trans)}'><i class="fas fa-edit"></i></button>
                                <button class="delete-transaction text-red-600 hover:text-red-800" data-transaction-id="${trans.TransactionID}"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>`;
                    tbody.insertAdjacentHTML('beforeend', row);
                });

                document.querySelectorAll('.edit-transaction').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const trans = JSON.parse(btn.dataset.transaction);
                        editingTransaction = trans.TransactionID;
                        document.getElementById('transaction-modal-title').textContent = 'Edit Transaction';
                        document.getElementById('transaction-item').value = `${trans.ItemCode}|${trans.ItemDescription || ''}`;
                        document.getElementById('transaction-item-code').value = trans.ItemCode;
                        document.getElementById('transaction-type').value = trans.TransactionType;
                        document.getElementById('transaction-quantity').value = trans.Quantity;
                        document.getElementById('transaction-date').value = trans.TransactionDate;
                        document.getElementById('transaction-modal').style.display = 'flex';
                    });
                });

                document.querySelectorAll('.delete-transaction').forEach(btn => {
                    btn.addEventListener('click', async () => {
                        const result = await Swal.fire({
                            title: 'Are you sure?',
                            text: 'Do you want to delete this transaction?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#6b7280',
                            confirmButtonText: 'Yes, delete it'
                        });
                        if (result.isConfirmed) {
                            try {
                                const response = await fetch(`../../backend/manager_api.php?endpoint=delete_transaction&TransactionID=${btn.dataset.transactionId}`, { method: 'POST' });
                                const result = await response.json();
                                if (result.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted',
                                        text: result.message,
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    loadItems();
                                    loadTransactions();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: result.message
                                    });
                                }
                            } catch (error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Error deleting transaction: ' + error.message
                                });
                            }
                        }
                    });
                });
            } catch (error) {
                console.error('Error loading transactions:', error);
                const tbody = document.getElementById('transactions-table-body');
                tbody.innerHTML = `<tr><td colspan="6" class="error-message">Failed to load transaction data: ${error.message}. Please try again later.</td></tr>`;
            }
        }

        async function loadItemOptions() {
            try {
                const response = await fetch(`../../backend/manager_api.php?endpoint=items&filter=all&page=1&limit=1000`);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load items');
                }
                const select = document.getElementById('transaction-item');
                select.innerHTML = '<option value="">Select Item</option>';
                (data.data || []).forEach(item => {
                    const option = document.createElement('option');
                    option.value = `${item.ItemCode}|${item.ItemDescription}`;
                    option.dataset.itemCode = item.ItemCode;
                    option.textContent = `${item.ItemDescription} (${item.ItemCode})`;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Error loading item options:', error);
                const select = document.getElementById('transaction-item');
                select.innerHTML = '<option value="">Error loading items</option>';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load item options: ' + error.message
                });
            }
        }

        async function exportToCSV() {
            try {
                const response = await fetch(`../../backend/manager_api.php?endpoint=items&filter=${currentFilter}&page=1&limit=10000`);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const data = await response.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load items for export');
                }
                const csv = [
                    ['Item Code', 'Description', 'Unit', 'Stock on Hand', 'Reorder Level', 'Status'],
                    ...(data.data || []).map(item => [
                        item.ItemCode || '',
                        `"${(item.ItemDescription || '').replace(/"/g, '""')}"`,
                        item.Unit || '',
                        item.StockOnHand || 0,
                        item.ReorderLevel || 0,
                        item.Status || ''
                    ].join(','))
                ].join('\n');

                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                link.download = 'inventory_report.csv';
                link.click();
                Swal.fire({
                    icon: 'success',
                    title: 'Exported',
                    text: 'Report exported successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error exporting report: ' + error.message
                });
            }
        }
    </script>
</body>
</html>