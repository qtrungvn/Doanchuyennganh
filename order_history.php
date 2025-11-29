<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    echo "Vui lòng đăng nhập để xem lịch sử đơn hàng.";
    exit;
}

$user_id = $_SESSION['user_id'];

// Kiểm tra nếu có yêu cầu hủy đơn
if (isset($_GET['cancel_order_id'])) {
    $cancel_order_id = (int)$_GET['cancel_order_id'];
    
    // Kiểm tra trạng thái đơn hàng
    $sql_check_status = "SELECT status FROM orders WHERE id = ?";
    $stmt_check_status = $conn->prepare($sql_check_status);
    $stmt_check_status->bind_param("i", $cancel_order_id);
    $stmt_check_status->execute();
    $result = $stmt_check_status->get_result();
    $order = $result->fetch_assoc();
    
    if ($order && $order['status'] == 'pending') {
        // Cập nhật trạng thái đơn hàng thành "canceled"
        $sql_update = "UPDATE orders SET status = 'canceled' WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("i", $cancel_order_id);
        
        if ($stmt_update->execute()) {
            echo "<script>alert('Đơn hàng đã được hủy thành công.'); window.location.href='order_history.php';</script>";
        } else {
            echo "Lỗi khi hủy đơn hàng: " . $stmt_update->error;
        }
    } else {
        echo "Không thể hủy đơn hàng này vì đơn hàng không có trạng thái 'Đang chờ'.";
    }
    exit;
}

// Xử lý lọc theo ngày
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Lấy các đơn hàng trong ngày
$sql = "SELECT * FROM orders WHERE user_id = ? AND DATE(created_at) = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $user_id, $filter_date);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Hàm tạo mã đơn hàng ngẫu nhiên (không lặp)
function generateRandomOrderCode($id) {
    $prefix = 'HD';
    $date = date('Ymd');
    $random = substr(md5($id . uniqid()), 0, 6);
    return $prefix . $date . strtoupper($random);
}

// Hàm hiển thị trạng thái đơn hàng
function getStatusDisplay($status) {
    $statuses = [
        'pending' => ['text' => 'Đang chờ', 'color' => '#ffc107', 'icon' => '⏳'],
        'processing' => ['text' => 'Đang xử lý', 'color' => '#17a2b8', 'icon' => '🔄'],
        'completed' => ['text' => 'Hoàn thành', 'color' => '#28a745', 'icon' => '✅'],
        'canceled' => ['text' => 'Đã hủy', 'color' => '#dc3545', 'icon' => '❌']
    ];
    return $statuses[$status] ?? ['text' => $status, 'color' => '#6c757d', 'icon' => '❓'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng - FastFood</title>
    <style>
        /* Reset và Base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding: 20px;
            color: #333;
        }

        /* Header giống trang chủ */
        .page-header {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            padding: 40px 20px;
            text-align: center;
            color: #fff;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
            position: relative;
            z-index: 1;
        }

        .page-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 20px;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Filter Form */
        .filter-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-section label {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        .date-picker {
            padding: 12px 20px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
            background: #fff;
        }

        .date-picker:focus {
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        .btn-filter {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
        }

        /* Table Styles */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
        }

        thead {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
        }

        th {
            padding: 18px 16px;
            text-align: center;
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }

        tbody tr {
            transition: all 0.3s ease;
        }

        tbody tr:hover {
            background-color: #fff3e0;
            transform: scale(1.01);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 13px;
            color: #fff;
        }

        /* Order Code */
        .order-code {
            font-weight: 700;
            color: #ff6b35;
            font-size: 15px;
        }

        /* Price */
        .price {
            font-weight: 700;
            color: #ff6b35;
            font-size: 16px;
        }

        /* Buttons */
        .btn-cancel {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 8px 18px;
            border: none;
            cursor: pointer;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }

        .btn-disabled {
            background: #e0e0e0;
            color: #999;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 13px;
            cursor: not-allowed;
        }

        .btn-back {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 30px;
            text-decoration: none;
            display: inline-block;
            margin: 30px auto;
            text-align: center;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }

        .btn-container {
            text-align: center;
            margin-top: 30px;
        }

        /* Empty State */
        .empty {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .empty h3 {
            color: #666;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .empty p {
            color: #999;
            font-size: 16px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header h2 {
                font-size: 2rem;
            }

            .container {
                padding: 20px;
            }

            .filter-section {
                flex-direction: column;
                gap: 10px;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px 8px;
            }

            .btn-back {
                width: 100%;
            }

            /* Mobile table scroll */
            .table-wrapper {
                overflow-x: scroll;
            }

            table {
                min-width: 600px;
            }
        }

        /* Loading Animation */
        .loading {
            text-align: center;
            padding: 40px;
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #ff6b35;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <h2>📋 Lịch Sử Đơn Hàng</h2>
        <p>Quản lý và theo dõi đơn hàng của bạn</p>
    </div>

    <div class="container">
        <!-- Filter Section -->
        <form method="get" class="filter-section">
            <label for="date">🗓️ Chọn ngày:</label>
            <input 
                type="date" 
                id="date" 
                name="date" 
                class="date-picker" 
                value="<?= htmlspecialchars($filter_date) ?>" 
                max="<?= date('Y-m-d') ?>" 
            />
            <button type="submit" class="btn-filter">🔍 Lọc đơn hàng</button>
        </form>

        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="empty">
                <div class="empty-icon">📦</div>
                <h3>Không có đơn hàng nào</h3>
                <p>Bạn chưa có đơn hàng nào trong ngày <?= date('d/m/Y', strtotime($filter_date)) ?>.</p>
            </div>
        <?php else: ?>
            <!-- Orders Table -->
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Ngày đặt</th>
                            <th>Trạng thái</th>
                            <th>Tổng tiền</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php $statusInfo = getStatusDisplay($order['status']); ?>
                            <tr>
                                <td>
                                    <span class="order-code">
                                        <?= generateRandomOrderCode($order['id']) ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <span class="status-badge" style="background-color: <?= $statusInfo['color'] ?>;">
                                        <?= $statusInfo['icon'] ?> <?= $statusInfo['text'] ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="price">
                                        <?= number_format($order['total_price'], 0, ',', '.') ?> đ
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <a 
                                            href="?cancel_order_id=<?= $order['id'] ?>&date=<?= $filter_date ?>" 
                                            class="btn-cancel" 
                                            onclick="return confirm('Bạn chắc chắn muốn hủy đơn hàng này?')"
                                        >
                                            ❌ Hủy đơn
                                        </a>
                                    <?php else: ?>
                                        <span class="btn-disabled">Không thể hủy</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>

        <!-- Back Button -->
        <div class="btn-container">
            <a href="index.php" class="btn-back">
                🏠 Trở về trang chủ
            </a>
        </div>
    </div>
</body>
</html>