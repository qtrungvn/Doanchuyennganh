<?php
session_start();
require_once "config.php";

$order_id = $_GET['order_id'] ?? null;
$order = null;
$order_details = [];

if ($order_id) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
    }
    $stmt->close();

    if ($order) {
        $stmt_detail = $conn->prepare("SELECT od.*, p.name, p.price as product_price FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = ?");
        $stmt_detail->bind_param("i", $order_id);
        $stmt_detail->execute();
        $res_detail = $stmt_detail->get_result();
        while ($row = $res_detail->fetch_assoc()) {
            $order_details[] = $row;
        }
        $stmt_detail->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Đặt hàng thành công</title>
<link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body {
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  background: #f8f9fa;
  color: #333;
  margin: 0;
}
#wrapper { width: 100%; overflow-x: hidden; }
.container { max-width: 1200px; margin: 50px auto; padding: 20px; }

.thank-section {
  background: linear-gradient(135deg, #ff6b35d9 0%, #ff8c42 100%);
  color: #fff;
  border-radius: 20px;
  padding: 60px 30px;
  text-align: center;
  box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.thank-section h1 {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 20px;
}

.thank-section h2 {
  font-size: 1.5rem;
  margin-bottom: 40px;
}

.order-details {
  background: #fff;
  color: #333;
  border-radius: 15px;
  padding: 30px;
  margin: 20px auto;
  max-width: 800px;
  box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.order-details table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}

.order-details th, .order-details td {
  padding: 12px;
  border-bottom: 1px solid #eee;
  text-align: left;
}

.order-details th {
  background: #f8f8f8;
  color: #ff6b35;
}

.total-price {
  text-align: right;
  font-size: 1.2rem;
  font-weight: 700;
  color: #ff6b35;
  margin-top: 10px;
}

.btn-group {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 30px;
}

.btn-home, .btn-vnpay {
  padding: 15px 40px;
  font-size: 16px;
  font-weight: 700;
  border-radius: 30px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.3s ease;
  border: none;
}

.btn-home {
  background: #fff;
  color: #ff6b35;
}

.btn-home:hover {
  background: #ffe0d6;
  transform: translateY(-3px);
}

.btn-vnpay {
  background: #ff6b35;
  color: #fff;
}

.btn-vnpay:hover {
  background: #ff5722;
  transform: translateY(-3px);
}

/* Responsive */
@media (max-width: 768px) {
  .thank-section h1 { font-size: 2.2rem; }
  .thank-section h2 { font-size: 1.2rem; }
  .order-details table th, .order-details table td { font-size: 14px; }
  .btn-group { flex-direction: column; gap: 15px; }
}
</style>
</head>
<body>
<div class="container">
  <div class="thank-section">
    <h1>Cảm ơn bạn đã đặt hàng!</h1>
    <?php if($order): ?>
      <h2>Mã đơn hàng: #<?php echo htmlspecialchars($order['id']); ?></h2>
    <?php endif; ?>
  </div>

  <?php if($order): ?>
  <div class="order-details">
    <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
    <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
    <?php if(!empty($order['note'])): ?>
    <p><strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['note']); ?></p>
    <?php endif; ?>

    <table>
      <thead>
        <tr>
          <th>Sản phẩm</th>
          <th>Số lượng</th>
          <th>Giá</th>
          <th>Thành tiền</th>
        </tr>
      </thead>
      <tbody>
        <?php $total = 0; foreach($order_details as $item):
          $item_total = $item['quantity'] * $item['price']; $total += $item_total;
        ?>
        <tr>
          <td><?php echo htmlspecialchars($item['name']); ?></td>
          <td><?php echo $item['quantity']; ?></td>
          <td><?php echo number_format($item['price'],0,',','.'); ?> đ</td>
          <td><?php echo number_format($item_total,0,',','.'); ?> đ</td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <p class="total-price">Tổng tiền: <?php echo number_format($total,0,',','.'); ?> đ</p>

    <div class="btn-group">
      <a href="index.php" class="btn-home">⬅ Quay về trang chủ</a>
      <form action="vnpay_php/vnpay_create_payment.php" method="GET">
        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
        <button type="submit" class="btn-vnpay">💳 Thanh toán VNPAY</button>
      </form>
    </div>
  </div>
  <?php else: ?>
  <p style="text-align:center; margin-top:30px;">Không tìm thấy đơn hàng.</p>
  <?php endif; ?>
</div>
</body>
</html>
