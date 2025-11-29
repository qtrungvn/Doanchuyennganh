<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
date_default_timezone_set('Asia/Ho_Chi_Minh');

require_once "./config_vnpay.php";
require_once "../config.php"; // Kết nối DB

// -------------------------------------------
// 1. LẤY order_id TỪ POST HOẶC URL CỦA BẠN
// -------------------------------------------
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) :
            (isset($_GET['order_id']) ? intval($_GET['order_id']) : 0);

if ($order_id <= 0) {
    die("Thiếu order_id hoặc không hợp lệ.");
}

// -------------------------------------------
// 2. TRUY VẤN ĐƠN HÀNG
// -------------------------------------------
$sql = "SELECT * FROM orders WHERE id = $order_id";
$result = mysqli_query($conn, $sql);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    die("Không tìm thấy đơn hàng.");
}

// -------------------------------------------
// 3. TẠO DỮ LIỆU CHUYỂN SANG VNPAY
// -------------------------------------------
$vnp_TxnRef = $order_id; // RẤT QUAN TRỌNG: Order ID = TxnRef
$vnp_Amount = intval($order['total_price'] * 100);
$vnp_Locale = 'vn';
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
$vnp_BankCode = $_POST['bank_code'] ?? '';
$expire = date('YmdHis', strtotime('+10 minutes'));

$inputData = array(
    "vnp_Version"    => "2.1.0",
    "vnp_TmnCode"    => $vnp_TmnCode,
    "vnp_Amount"     => $vnp_Amount,
    "vnp_Command"    => "pay",
    "vnp_CreateDate" => date('YmdHis'),
    "vnp_CurrCode"   => "VND",
    "vnp_IpAddr"     => $vnp_IpAddr,
    "vnp_Locale"     => $vnp_Locale,
    "vnp_OrderInfo"  => "Thanh toán đơn hàng #$vnp_TxnRef",
    "vnp_OrderType"  => "other",
    "vnp_ReturnUrl"  => $vnp_Returnurl,  // ⚠ return URL PHẢI KHÔNG có query string
    "vnp_TxnRef"     => $vnp_TxnRef,
    "vnp_ExpireDate" => $expire
);

// Thêm bank code nếu user chọn
if (!empty($vnp_BankCode)) {
    $inputData['vnp_BankCode'] = $vnp_BankCode;
}

// -------------------------------------------
// 4. TẠO URL + SecureHash
// -------------------------------------------
ksort($inputData);
$query = "";
$hashdata = "";

$i = 0;
foreach ($inputData as $key => $value) {
    if ($i == 1) $hashdata .= '&';
    $hashdata .= urlencode($key) . "=" . urlencode($value);
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
    $i = 1;
}

$vnp_SecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
$vnp_Url = $vnp_Url . "?" . $query . "vnp_SecureHash=" . $vnp_SecureHash;

// -------------------------------------------
// 5. CHUYỂN HƯỚNG SANG VNPAY
// -------------------------------------------
header("Location: " . $vnp_Url);
exit;

