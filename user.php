<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT fullname, email, phone, address, password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "Không tìm thấy thông tin tài khoản.";
    exit();
}

// Gán mặc định rỗng nếu dữ liệu bị null
$user['phone'] = $user['phone'] ?? '';
$user['address'] = $user['address'] ?? '';
$user['fullname'] = $user['fullname'] ?? '';
$user['email'] = $user['email'] ?? '';
$user['password'] = $user['password'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_info'])) {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $sql = "UPDATE users SET fullname=?, email=?, phone=?, address=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $fullname, $email, $phone, $address, $user_id);

    if ($stmt->execute()) {
        echo "<script>alert('Cập nhật thông tin thành công!'); window.location.href='user.php';</script>";
    } else {
        echo "<script>alert('Cập nhật thông tin thất bại!');</script>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!empty($user['password']) && password_verify($old_password, $user['password'])) {
        if ($new_password !== $confirm_password) {
            echo "<script>alert('Mật khẩu mới và xác nhận không khớp!');</script>";
        } else {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $new_hashed_password, $user_id);
            if ($stmt->execute()) {
                echo "<script>alert('Đổi mật khẩu thành công!'); window.location.href='user.php';</script>";
            } else {
                echo "<script>alert('Đổi mật khẩu thất bại!');</script>";
            }
        }
    } else {
        echo "<script>alert('Mật khẩu cũ không đúng hoặc chưa được thiết lập!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông Tin Tài Khoản - FastFood</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            padding: 20px;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #ff6b35 0%, #ff8c42 100%);
            padding: 40px 20px;
            text-align: center;
            color: #fff;
            border-radius: 20px;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
            animation: fadeInDown 0.6s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.95;
        }

        /* Container */
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .forms-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        /* Form Cards */
        .form-card {
            background: #fff;
            padding: 35px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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

        .form-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .form-card h3 {
            color: #ff6b35;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid #ffe0d6;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background-color: #f8f9fa;
            color: #333;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #ff6b35;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }

        input:disabled {
            background-color: #f0f0f0;
            cursor: not-allowed;
        }

        /* Buttons */
        button {
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            border: none;
            color: #fff;
            padding: 14px 30px;
            width: 100%;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.4);
        }

        button:active {
            transform: translateY(0);
        }

        /* Back Link */
        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #ff6b35, #ff8c42);
            color: #fff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }

        .back-link a:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }

        /* Icons */
        .icon {
            font-size: 1.5rem;
        }

        /* Password Strength Indicator */
        .password-hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .forms-wrapper {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .page-header h2 {
                font-size: 2rem;
            }

            .form-card {
                padding: 25px;
            }

            .form-card h3 {
                font-size: 1.5rem;
            }
        }

        /* Loading State */
        button.loading {
            position: relative;
            color: transparent;
        }

        button.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 3px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Success Message */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }

        /* Error Message */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <!-- Page Header -->
    <div class="page-header">
        <h2>👤 Thông Tin Tài Khoản</h2>
        <p>Quản lý thông tin cá nhân và bảo mật tài khoản của bạn</p>
    </div>

    <!-- Container -->
    <div class="container">
        <div class="forms-wrapper">
            <!-- Form 1: Thông tin cá nhân -->
            <div class="form-card">
                <h3><span class="icon">📝</span> Thông Tin Cá Nhân</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="fullname">Họ và Tên</label>
                        <input 
                            type="text" 
                            id="fullname" 
                            name="fullname" 
                            value="<?php echo htmlspecialchars($user['fullname']); ?>" 
                            required
                            placeholder="Nhập họ và tên"
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($user['email']); ?>" 
                            required
                            placeholder="email@example.com"
                        >
                    </div>

                    <div class="form-group">
                        <label for="phone">Số Điện Thoại</label>
                        <input 
                            type="text" 
                            id="phone" 
                            name="phone" 
                            value="<?php echo htmlspecialchars($user['phone']); ?>" 
                            required
                            placeholder="0123456789"
                        >
                    </div>

                    <div class="form-group">
                        <label for="address">Địa Chỉ</label>
                        <input 
                            type="text" 
                            id="address" 
                            name="address" 
                            value="<?php echo htmlspecialchars($user['address']); ?>" 
                            required
                            placeholder="Nhập địa chỉ của bạn"
                        >
                    </div>

                    <button type="submit" name="update_info">💾 Lưu Thay Đổi</button>
                </form>
            </div>

            <!-- Form 2: Đổi mật khẩu -->
            <div class="form-card">
                <h3><span class="icon">🔐</span> Đổi Mật Khẩu</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="old_password">Mật Khẩu Cũ</label>
                        <input 
                            type="password" 
                            id="old_password" 
                            name="old_password" 
                            required
                            placeholder="Nhập mật khẩu cũ"
                        >
                    </div>

                    <div class="form-group">
                        <label for="new_password">Mật Khẩu Mới</label>
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            required
                            placeholder="Nhập mật khẩu mới"
                        >
                        <div class="password-hint">
                            💡 Mật khẩu nên có ít nhất 8 ký tự
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Xác Nhận Mật Khẩu Mới</label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                            placeholder="Nhập lại mật khẩu mới"
                        >
                    </div>

                    <button type="submit" name="change_password">🔄 Đổi Mật Khẩu</button>
                </form>
            </div>
        </div>

        <!-- Back Link -->
        <div class="back-link">
            <a href="index.php">
                🏠 Quay lại Trang Chủ
            </a>
        </div>
    </div>
</body>
</html>