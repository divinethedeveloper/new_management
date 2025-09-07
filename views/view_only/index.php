<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockLens - Ministry of Finance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #dbeafe;
            --secondary: #64748b;
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
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out forwards;
            animation-delay: 0.2s;
        }
        
        .card {
            opacity: 0;
            animation-delay: calc(0.1s * var(--index));
        }
        
        .table-row {
            opacity: 0;
            animation-delay: calc(0.05s * var(--row-index));
        }
        
        /* Loading Skeleton */
        .skeleton {
            background-color: #e2e8f0;
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }
        
        /* Hover Effects */
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px -8px rgba(0, 0, 0, 0.1);
        }
        
        /* Status Indicators */
        .status-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-ok {
            background-color: var(--success);
        }
        
        .status-watch {
            background-color: var(--warning);
        }
        
        .status-low {
            background-color: var(--danger);
        }
        
        /* Custom checkbox */
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
            display: inline-block;
            vertical-align: text-top;
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
        
        /* Table styling */
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
        
        /* Chart container */
        .chart-container {
            position: relative;
            height: 250px;
            width: 100%;
        }
        
        /* Custom Scrollbar */
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
        
        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                background: white;
            }
            
            .card, table {
                box-shadow: none;
                border: 1px solid #e2e8f0;
            }
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
                    <h1 class="text-xl font-bold text-gray-800">Ministry of Finance – StockLens</h1>
                    <p class="text-sm text-gray-500">View-only inventory dashboard</p>
                </div>
            </div>
            
            <div class="flex flex-col md:flex-row items-start md:items-center space-y-4 md:space-y-0 md:space-x-4">
                <div class="flex items-center space-x-2">
                    <label for="from-date" class="text-gray-600 text-sm">FROM</label>
                    <input type="date" id="from-date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <label for="to-date" class="text-gray-600 text-sm ml-2">TO</label>
                    <input type="date" id="to-date" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="relative">
                    <input type="text" id="search-input" placeholder="Search by Item Code or Description" class="border border-gray-300 rounded-lg pl-10 pr-4 py-2 w-full md:w-64 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
                <button id="apply-filters" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm flex items-center transition">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
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
                    <li><button data-filter="recent_issued" class="w-full text-left px-4 py-2.5 rounded-lg transition flex items-center text-gray-600 hover:bg-gray-50"><i class="fas fa-arrow-up mr-3 text-green-500"></i> Recently Issued</button></li>
                    <li><button data-filter="recent_received" class="w-full text-left px-4 py-2.5 rounded-lg transition flex items-center text-gray-600 hover:bg-gray-50"><i class="fas fa-arrow-down mr-3 text-purple-500"></i> Recently Received</button></li>
                </ul>
                
                <h2 class="text-lg font-semibold mb-5 text-gray-700 flex items-center">
                    <i class="fas fa-chart-pie mr-2 text-blue-500"></i> Stock Summary
                </h2>
                
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-600">Normal Stock</span>
                            <span id="normal-pct" class="text-sm font-medium text-gray-600">Loading...</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="normal-bar" class="bg-green-500 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-600">Watch Items</span>
                            <span id="watch-pct" class="text-sm font-medium text-gray-600">Loading...</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="watch-bar" class="bg-yellow-500 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-gray-600">Low Stock</span>
                            <span id="low-pct" class="text-sm font-medium text-gray-600">Loading...</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div id="low-bar" class="bg-red-500 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <button id="export-report" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2.5 rounded-lg text-sm flex items-center justify-center transition">
                        <i class="fas fa-file-export mr-2"></i> Export Report
                    </button>
                </div>
            </aside>

            <!-- Main Section -->
            <section class="w-full lg:w-4/5">
                <!-- Stock Overview Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
                    <div class="card bg-white shadow-lg rounded-xl p-5 hover-lift animate-fade-in" style="--index: 1;">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Total Items</p>
                                <h2 id="total-items" class="text-3xl font-bold text-gray-800 mt-1">Loading...</h2>
                            </div>
                            <div class="bg-blue-100 p-3 rounded-lg">
                                <i class="fas fa-cube text-blue-600 text-lg"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center">
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">+0%</span>
                            <span class="text-gray-500 text-xs ml-2">from last month</span>
                        </div>
                    </div>
                    
                    <div class="card bg-white shadow-lg rounded-xl p-5 hover-lift animate-fade-in" style="--index: 2;">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Total Stock on Hand</p>
                                <h2 id="total-stock" class="text-3xl font-bold text-gray-800 mt-1">Loading...</h2>
                            </div>
                            <div class="bg-green-100 p-3 rounded-lg">
                                <i class="fas fa-boxes text-green-600 text-lg"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center">
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">+0%</span>
                            <span class="text-gray-500 text-xs ml-2">from last month</span>
                        </div>
                    </div>
                    
                    <div class="card bg-white shadow-lg rounded-xl p-5 hover-lift animate-fade-in" style="--index: 3;">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Low Stock Alerts</p>
                                <h2 id="low-alerts" class="text-3xl font-bold text-gray-800 mt-1">Loading...</h2>
                            </div>
                            <div class="bg-red-100 p-3 rounded-lg">
                                <i class="fas fa-exclamation-circle text-red-600 text-lg"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center">
                            <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">-0%</span>
                            <span class="text-gray-500 text-xs ml-2">from last month</span>
                        </div>
                    </div>
                    
                    <div class="card bg-white shadow-lg rounded-xl p-5 hover-lift animate-fade-in" style="--index: 4;">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-500 text-sm">Last Updated</p>
                                <h2 id="last-updated" class="text-2xl font-bold text-gray-800 mt-1">Loading...</h2>
                                <p class="text-gray-400 text-xs mt-1">Loading...</p>
                            </div>
                            <div class="bg-purple-100 p-3 rounded-lg">
                                <i class="fas fa-sync-alt text-purple-600 text-lg"></i>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <button id="refresh-data" class="text-blue-600 text-xs flex items-center">
                                <i class="fas fa-redo-alt mr-1"></i> Refresh Data
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Stock Trend Chart -->
                <div class="bg-white shadow-lg rounded-xl p-5 mb-6 animate-fade-in">
                    <div class="flex justify-between items-center mb-5">
                        <h2 class="text-lg font-semibold text-gray-700">Stock Trend Overview</h2>
                        <div class="flex space-x-2">
                            <button class="period-btn text-xs px-3 py-1 bg-blue-100 text-blue-700 rounded-full">Monthly</button>
                            <button class="period-btn text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded-full">Quarterly</button>
                            <button class="period-btn text-xs px-3 py-1 bg-gray-100 text-gray-700 rounded-full">Yearly</button>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="stockTrendChart"></canvas>
                    </div>
                </div>

                <!-- Stock Report Table -->
                <div class="bg-white shadow-lg rounded-xl overflow-hidden animate-fade-in">
                    <div class="p-5 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-700">Stock Details</h2>
                        <div class="flex items-center space-x-3">
                            <div class="flex items-center text-sm">
                                <input type="checkbox" id="low-stock-toggle" class="custom-checkbox">
                                <label for="low-stock-toggle">Show low stock only</label>
                            </div>
                            <button class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-columns"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 sticky-header">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opening</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipts</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock on Hand</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
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
                                <p id="pagination-info" class="text-sm text-gray-700">
                                    Loading...
                                </p>
                            </div>
                            <div>
                                <nav id="pagination-nav" class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <!-- Pagination buttons will be inserted here -->
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t py-4 text-center text-gray-500 text-sm mt-8 no-print">
        <div class="max-w-7xl mx-auto px-4">
            <p>Store Management System – Ministry of Finance © 2025</p>
            <p class="mt-1">Data updated automatically every 30 minutes</p>
        </div>
    </footer>

    <script>
        let stockTrendChart;
        let currentPage = 1;
        const limit = 20;
        let currentFilter = 'all';
        let currentFrom;
        let currentTo;
        let currentSearch = '';
        let currentPeriod = 'monthly';

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dates
            const today = new Date('2025-09-05');
            const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            document.getElementById('from-date').value = firstDay.toISOString().split('T')[0];
            document.getElementById('to-date').value = today.toISOString().split('T')[0];
            currentFrom = document.getElementById('from-date').value;
            currentTo = document.getElementById('to-date').value;

            // Initialize chart with empty data
            const ctx = document.getElementById('stockTrendChart').getContext('2d');
            stockTrendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Stock Received',
                            data: [],
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Stock Issued',
                            data: [],
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Load data
            loadOverview();
            loadTableData();
            loadTrendData();

            // Event listeners
            document.getElementById('apply-filters').addEventListener('click', () => {
                currentFrom = document.getElementById('from-date').value;
                currentTo = document.getElementById('to-date').value;
                currentSearch = document.getElementById('search-input').value;
                currentPage = 1;
                loadOverview();
                loadTableData();
                loadTrendData();
            });

            document.getElementById('refresh-data').addEventListener('click', () => {
                loadOverview();
                loadTableData();
                loadTrendData();
            });

            document.querySelectorAll('.quick-filters button').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.quick-filters button').forEach(b => {
                        b.classList.remove('text-blue-600', 'bg-blue-50', 'border', 'border-blue-100');
                        b.classList.add('text-gray-600', 'hover:bg-gray-50');
                    });
                    btn.classList.add('text-blue-600', 'bg-blue-50', 'border', 'border-blue-100');
                    btn.classList.remove('text-gray-600', 'hover:bg-gray-50');
                    currentFilter = btn.dataset.filter;
                    currentPage = 1;
                    loadTableData();
                    // Sync checkbox
                    document.getElementById('low-stock-toggle').checked = (currentFilter === 'low');
                });
            });

            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.period-btn').forEach(b => {
                        b.classList.remove('bg-blue-100', 'text-blue-700');
                        b.classList.add('bg-gray-100', 'text-gray-700');
                    });
                    btn.classList.add('bg-blue-100', 'text-blue-700');
                    btn.classList.remove('bg-gray-100', 'text-gray-700');
                    currentPeriod = btn.textContent.toLowerCase();
                    loadTrendData();
                });
            });

            document.getElementById('low-stock-toggle').addEventListener('change', function() {
                currentFilter = this.checked ? 'low' : 'all';
                currentPage = 1;
                loadTableData();
                // Sync quick filter buttons
                document.querySelectorAll('.quick-filters button').forEach(b => {
                    if (b.dataset.filter === currentFilter) {
                        b.click();
                    }
                });
            });

            document.getElementById('export-report').addEventListener('click', exportToCSV);
        });

        function showSkeletonLoading(type) {
            if (type === 'table') {
                const tbody = document.getElementById('table-body');
                tbody.innerHTML = '';
                for (let i = 0; i < 10; i++) {
                    const row = `<tr class="table-row">
                        <td class="px-6 py-4"><div class="skeleton h-4 w-20"></div></td>
                        <td class="px-6 py-4"><div class="skeleton h-4 w-48"></div></td>
                        <td class="px-6 py-4"><div class="skeleton h-4 w-16"></div></td>
                        <td class="px-6 py-4"><div class="skeleton h-4 w-12"></div></td>
                        <td class="px-6 py-4"><div class="skeleton h-4 w-12"></div></td>
                        <td class="px-6 py-4"><div class="skeleton h-4 w-12"></div></td>
                        <td class="px-6 py-4"><div class="skeleton h-4 w-12"></div></td>
                        <td class="px-6 py-4"><div class="skeleton h-4 w-12"></div></td>
                        <td class="px-6 py-4"><div class="skeleton h-4 w-20"></div></td>
                    </tr>`;
                    tbody.insertAdjacentHTML('beforeend', row);
                }
            } else if (type === 'chart') {
                stockTrendChart.data.labels = ['Loading', 'Loading', 'Loading'];
                stockTrendChart.data.datasets[0].data = [0, 0, 0];
                stockTrendChart.data.datasets[1].data = [0, 0, 0];
                stockTrendChart.update();
            }
        }

        function loadOverview() {
            fetch(`../../backend/api.php?endpoint=overview&from=${currentFrom}&to=${currentTo}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('total-items').textContent = data.total_items || 0;
                    document.getElementById('total-stock').textContent = data.total_stock || 0;
                    document.getElementById('low-alerts').textContent = data.low_alerts || 0;
                    document.getElementById('last-updated').textContent = data.last_updated || 'N/A';

                    document.getElementById('normal-pct').textContent = data.normal_pct + '%';
                    document.getElementById('watch-pct').textContent = data.watch_pct + '%';
                    document.getElementById('low-pct').textContent = data.low_pct + '%';

                    document.getElementById('normal-bar').style.width = data.normal_pct + '%';
                    document.getElementById('watch-bar').style.width = data.watch_pct + '%';
                    document.getElementById('low-bar').style.width = data.low_pct + '%';
                })
                .catch(() => {
                    document.getElementById('total-items').textContent = 'Error';
                    // Handle error
                });
        }

        function loadTableData() {
            showSkeletonLoading('table');
            fetch(`../../backend/api.php?endpoint=table_data&from=${currentFrom}&to=${currentTo}&search=${encodeURIComponent(currentSearch)}&filter=${currentFilter}&page=${currentPage}&limit=${limit}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('table-body');
                    tbody.innerHTML = '';
                    (data.data || []).forEach((item, index) => {
                        const statusClass = item.Status === 'OK' ? 'bg-green-100 text-green-800 status-ok' : item.Status === 'Watch' ? 'bg-yellow-100 text-yellow-800 status-watch' : 'bg-red-100 text-red-800 status-low';
                        const row = `
                            <tr class="table-row animate-fade-in hover:bg-gray-50 transition" style="--row-index: ${index + 1};">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900">${item.ItemCode || ''}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">${item.ItemDescription || ''}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${item.Unit || ''}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.OpeningStockPeriod || 0}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.TotalReceipts || 0}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.TotalIssues || 0}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${item.StockOnHand || 0}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.ReorderLevel || 0}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                                        <span class="status-indicator"></span> ${item.Status || 'N/A'}
                                    </span>
                                </td>
                            </tr>`;
                        tbody.insertAdjacentHTML('beforeend', row);
                    });

                    document.getElementById('pagination-info').textContent = `Showing ${(currentPage - 1) * limit + 1} to ${Math.min(currentPage * limit, data.total || 0)} of ${data.total || 0} results`;

                    // Update pagination nav
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
                            loadTableData();
                        });
                    });
                })
                .catch(() => {
                    document.getElementById('table-body').innerHTML = '<tr><td colspan="9" class="text-center py-4">Error loading data</td></tr>';
                });
        }

        function loadTrendData() {
            showSkeletonLoading('chart');
            fetch(`../../backend/api.php?endpoint=trend_data&from=${currentFrom}&to=${currentTo}&period=${currentPeriod}`)
                .then(response => response.json())
                .then(data => {
                    stockTrendChart.data.labels = data.labels || [];
                    stockTrendChart.data.datasets[0].data = data.received || [];
                    stockTrendChart.data.datasets[1].data = data.issued || [];
                    stockTrendChart.update();
                })
                .catch(() => {
                    stockTrendChart.data.labels = ['Error'];
                    stockTrendChart.update();
                });
        }

        function exportToCSV() {
            fetch(`../../backend/api.php?endpoint=table_data&from=${currentFrom}&to=${currentTo}&search=${encodeURIComponent(currentSearch)}&filter=${currentFilter}&page=1&limit=10000`)
                .then(response => response.json())
                .then(data => {
                    const csv = [
                        ['Item Code', 'Item Description', 'Unit', 'Opening', 'Receipts', 'Issues', 'Stock on Hand', 'Reorder Level', 'Status'].join(','),
                        ...(data.data || []).map(item => [
                            item.ItemCode || '',
                            `"${(item.ItemDescription || '').replace(/"/g, '""')}"`,
                            item.Unit || '',
                            item.OpeningStockPeriod || 0,
                            item.TotalReceipts || 0,
                            item.TotalIssues || 0,
                            item.StockOnHand || 0,
                            item.ReorderLevel || 0,
                            item.Status || ''
                        ].join(','))
                    ].join('\n');

                    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'stock_report.csv';
                    link.click();
                })
                .catch(() => alert('Error exporting data'));
        }
    </script>
</body>
</html>