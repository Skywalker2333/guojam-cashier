<?php
// 允许来自时光邮局域名的跨域请求
header("Access-Control-Allow-Origin: https://timemail.guojam.fun");
// 允许POST和OPTIONS方法（因为创建订单用的是POST）
header("Access-Control-Allow-Methods: POST, OPTIONS");
// 允许请求携带Content-Type等头信息
header("Access-Control-Allow-Headers: Content-Type");
// 允许跨域请求携带Cookie（如果需要的话，不需要可以省略）
// header("Access-Control-Allow-Credentials: true");

// 处理浏览器的预检请求（OPTIONS请求）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 以下是原接口逻辑...
header("Content-Type: application/json; charset=utf-8");

// 引入配置文件和核心类
require_once("config.php");
require_once("lib/EpayCore.class.php");

// 检查是否已安装
if (!$app_config['installed']) {
    echo json_encode(['status' => 'unsuccessful', 'message' => '系统未安装']);
    exit;
}

// 验证请求参数
if (empty($_GET['order'])) {
    echo json_encode(['status' => 'unsuccessful', 'message' => '请提供订单号']);
    exit;
}

$out_trade_no = $_GET['order'];

// 连接数据库
try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'unsuccessful', 'message' => '数据库连接失败']);
    exit;
}

// 查询本地订单状态
$stmt = $pdo->prepare("SELECT trade_status FROM guojam_orders WHERE out_trade_no = :out_trade_no");
$stmt->execute([':out_trade_no' => $out_trade_no]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    echo json_encode(['status' => 'unsuccessful', 'message' => '订单不存在']);
    exit;
}

// 如果本地状态已支付，直接返回成功
if ($order['trade_status'] == 'TRADE_SUCCESS') {
    echo json_encode(['status' => 'success', 'message' => '订单已支付']);
    exit;
}

// 调用支付接口查询最新状态
$epay = new EpayCore($epay_config);
$order_info = $epay->queryOrder($out_trade_no);

// 更新本地订单状态
if ($order_info['status'] == 1) {
    $stmt = $pdo->prepare("UPDATE guojam_orders SET trade_status = 'TRADE_SUCCESS', trade_no = :trade_no 
                          WHERE out_trade_no = :out_trade_no");
    $stmt->execute([
        ':trade_no' => $order_info['trade_no'],
        ':out_trade_no' => $out_trade_no
    ]);
    echo json_encode(['status' => 'success', 'message' => '订单已支付']);
} else {
    echo json_encode(['status' => 'unsuccessful', 'message' => '订单未支付']);
}
?>