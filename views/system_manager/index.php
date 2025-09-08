<?php
session_start();

// Check if user is logged in by verifying session variable
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Redirect to parent directory if not logged in
    header("Location: ../../");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockLens - System Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6; /* Blue-500 */
            --primary-light: #eff6ff; /* Blue-50 */
            --gray-100: #f3f4f6;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
            position: relative;
            overflow-x: hidden;
        }

        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .manager-card {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 1.5rem;
            box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.2), 0 8px 10px -3px rgba(0, 0, 0, 0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 800px;
            border: 1px solid rgba(209, 213, 219, 0.3);
            backdrop-filter: blur(8px);
            transition: transform 0.3s ease;
            z-index: 1;
        }

        .manager-card:hover {
            transform: translateY(-5px);
        }

        .input-field:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3);
            outline: none;
        }

        button[type="submit"] {
            position: relative;
            overflow: hidden;
        }

        button[type="submit"]::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s ease, height 0.6s ease;
        }

        button[type="submit"]:hover::after {
            width: 200px;
            height: 200px;
        }
    </style>
</head>
<body>
    <?php
    require_once '../../backend/functions.php';
    restrict_to_system_manager();

    // Handle password update
    $password_message = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password'])) {
        $password_message = update_manager_password($conn, $_POST['new_password']);
    }

    // Fetch data
    $logged_in_users = get_recent_logins($conn);
    $recent_transactions = get_recent_transactions($conn);
    $all_transactions = get_all_transactions($conn);
    ?>

    <canvas id="constellationCanvas"></canvas>
    <div class="manager-card">
        <header class="text-center mb-8">
            <div class="flex items-center justify-center text-5xl text-blue-500 mb-2">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">System Manager Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">StockLens Inventory System</p>
        </header>

        <main class="space-y-8">
            <!-- Update Store Manager Password -->
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Update Store Manager Password</h2>
                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="input-field w-full border border-gray-300 rounded-lg px-4 py-2.5 text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-blue-500" placeholder="Enter new password" required>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-4 focus:ring-blue-500/50 relative">
                        Update Password
                    </button>
                </form>
                <?php if ($password_message): ?>
                    <p class="text-sm text-center mt-2 <?php echo strpos($password_message, 'Error') === false && strpos($password_message, 'No') === false ? 'text-green-500' : 'text-red-500'; ?>">
                        <?php echo htmlspecialchars($password_message); ?>
                    </p>
                <?php else: ?>
                    <p class="text-sm text-center mt-2 text-green-500 hidden">Store Manager password updated successfully.</p>
                <?php endif; ?>
            </section>

            <!-- Recent Login Logs -->
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Logins</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">User/Email</th>
                                <th class="px-6 py-3">Login Time</th>
                                <th class="px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($logged_in_users && $logged_in_users->num_rows > 0): ?>
                                <?php while ($row = $logged_in_users->fetch_assoc()): ?>
                                    <tr class="bg-white border-b">
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['username_or_email']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['login_time']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $row['status'] == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-gray-500">No recent logins</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Recent Stock Transactions -->
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Stock Transactions</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Transaction ID</th>
                                <th class="px-6 py-3">Item Description</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Quantity</th>
                                <th class="px-6 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_transactions && $recent_transactions->num_rows > 0): ?>
                                <?php while ($row = $recent_transactions->fetch_assoc()): ?>
                                    <tr class="bg-white border-b">
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['TransactionID']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['ItemDescription']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $row['TransactionType'] == 'Receipt' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'; ?>">
                                                <?php echo $row['TransactionType']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['Quantity']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['TransactionDate']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No recent transactions</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- All Stock Transactions -->
            <section>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">All Stock Transactions</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-6 py-3">Transaction ID</th>
                                <th class="px-6 py-3">Item Description</th>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Quantity</th>
                                <th class="px-6 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($all_transactions && $all_transactions->num_rows > 0): ?>
                                <?php while ($row = $all_transactions->fetch_assoc()): ?>
                                    <tr class="bg-white border-b">
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['TransactionID']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['ItemDescription']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $row['TransactionType'] == 'Receipt' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'; ?>">
                                                <?php echo $row['TransactionType']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['Quantity']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($row['TransactionDate']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">No transactions available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Constellation animation
        const canvas = document.getElementById('constellationCanvas');
        const ctx = canvas.getContext('2d');
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;

        const particles = [];
        const numParticles = 50;

        class Particle {
            constructor() {
                this.x = Math.random() * canvas.width;
                this.y = Math.random() * canvas.height;
                this.vx = (Math.random() - 0.5) * 0.5;
                this.vy = (Math.random() - 0.5) * 0.5;
                this.radius = Math.random() * 2 + 1;
            }

            update() {
                this.x += this.vx;
                this.y += this.vy;
                if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
                if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
            }

            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(59, 130, 246, 0.3)';
                ctx.fill();
            }
        }

        for (let i = 0; i < numParticles; i++) {
            particles.push(new Particle());
        }

        function animate() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles.forEach(particle => {
                particle.update();
                particle.draw();
            });

            ctx.strokeStyle = 'rgba(59, 130, 246, 0.1)';
            ctx.lineWidth = 0.5;
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dx = particles[i].x - particles[j].x;
                    const dy = particles[i].y - particles[j].y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    if (distance < 100) {
                        ctx.beginPath();
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        ctx.stroke();
                    }
                }
            }

            requestAnimationFrame(animate);
        }

        animate();

        window.addEventListener('resize', () => {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        });
    </script>
    <?php $conn->close(); ?>
</body>
</html>