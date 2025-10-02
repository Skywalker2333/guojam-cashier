<?php
session_start();
require_once("config.php");

// 初始化登录状态（解决Undefined index错误）
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = false;
}

// 数据库连接函数
function getDbConnection() {
    global $db_config;
    try {
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
            $db_config['user'],
            $db_config['pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("数据库连接失败: " . $e->getMessage());
    }
}

// 登录验证
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // 验证账号密码
    if ($username === 'guojam' && $password === 'guojam233') {
        $_SESSION['logged_in'] = true;
    } else {
        $error = "账号或密码错误";
    }
}

// 登出处理
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: admin.php");
    exit;
}

// 删除订单处理
if (isset($_POST['delete_order']) && $_SESSION['logged_in']) {
    $orderId = $_POST['order_id'];
    
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("DELETE FROM guojam_orders WHERE id = :id");
        $stmt->execute([':id' => $orderId]);
        $success = "订单已成功删除";
    } catch (PDOException $e) {
        $error = "删除失败: " . $e->getMessage();
    }
}

// 修改订单状态
if (isset($_POST['update_status']) && $_SESSION['logged_in']) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['trade_status'];
    
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("UPDATE guojam_orders SET trade_status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $newStatus,
            ':id' => $orderId
        ]);
        $success = "订单状态已更新";
    } catch (PDOException $e) {
        $error = "更新失败: " . $e->getMessage();
    }
}

// 获取所有订单和统计信息
$orders = [];
$totalPaidAmount = 0;
if ($_SESSION['logged_in']) {  // 这里现在不会报错了，因为已经初始化过
    try {
        $pdo = getDbConnection();
        
        // 查询已支付订单总额
        $stmt = $pdo->query("SELECT SUM(total_fee) as total FROM guojam_orders WHERE trade_status = 'TRADE_SUCCESS'");
        $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalPaidAmount = $totalResult['total'] ?? 0;
        
        // 查询所有订单
        $stmt = $pdo->query("SELECT * FROM guojam_orders ORDER BY order_time DESC");
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "查询订单失败: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>订单管理后台</title>
    <style>
        /* 保持原样式不变 */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-form {
            max-width: 300px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: #dc3545;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8d7da;
            border-radius: 4px;
        }
        .success {
            color: #28a745;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #d4edda;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .status-success {
            color: #28a745;
            font-weight: bold;
        }
        .status-wait {
            color: #ffc107;
            font-weight: bold;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .logout-btn {
            background-color: #dc3545;
        }
        .logout-btn:hover {
            background-color: #c82333;
        }
        .stats {
            background-color: #e9f7fe;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        .delete-btn {
            background-color: #dc3545;
            margin-left: 10px;
            padding: 5px 10px;
            font-size: 14px;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .action-buttons {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <?php if (!$_SESSION['logged_in']): ?>  <!-- 这里也不会报错了 -->
        <!-- 登录表单 -->
        <div class="login-form">
            <h2>管理员登录</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" name="login">登录</button>
            </form>
        </div>
    <?php else: ?>
        <!-- 管理页面 -->
        <div class="container">
            <div class="header">
                <h1>订单管理</h1>
                <button class="logout-btn" onclick="window.location='admin.php?action=logout'">退出登录</button>
            </div>
            
            <!-- 统计信息 -->
            <div class="stats">
                <h3>已支付订单总额: ¥<?php echo number_format($totalPaidAmount, 2); ?></h3>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <table>
                <tr>
                    <th>ID</th>
                    <th>订单号</th>
                    <th>商品名称</th>
                    <th>金额(元)</th>
                    <th>创建时间</th>
                    <th>支付状态</th>
                    <th>支付平台交易号</th>
                    <th>操作</th>
                </tr>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo $order['id']; ?></td>
                    <td><?php echo $order['out_trade_no']; ?></td>
                    <td><?php echo $order['subject']; ?></td>
                    <td><?php echo $order['total_fee']; ?></td>
                    <td><?php echo $order['order_time']; ?></td>
                    <td class="<?php echo $order['trade_status'] == 'TRADE_SUCCESS' ? 'status-success' : 'status-wait'; ?>">
                        <?php echo $order['trade_status'] == 'TRADE_SUCCESS' ? '已支付' : '待支付'; ?>
                    </td>
                    <td><?php echo $order['trade_no'] ?: '-'; ?></td>
                    <td class="action-buttons">
                        <!-- 状态更新表单 -->
                        <form method="post" style="margin:0; display:inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="trade_status" onchange="this.form.submit()">
                                <option value="WAIT_PAY" <?php echo $order['trade_status'] == 'WAIT_PAY' ? 'selected' : ''; ?>>待支付</option>
                                <option value="TRADE_SUCCESS" <?php echo $order['trade_status'] == 'TRADE_SUCCESS' ? 'selected' : ''; ?>>已支付</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                        
                        <!-- 删除订单表单 -->
                        <form method="post" style="margin:0; display:inline;" onsubmit="return confirm('确定要删除此订单吗？');">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <button type="submit" name="delete_order" class="delete-btn">删除</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">暂无订单数据</td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    <?php endif; ?>
</body>
</html>