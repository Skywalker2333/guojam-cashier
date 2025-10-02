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
header("Content-Type: application/json; charset=utf-8");

// 引入配置文件和核心类
require_once("config.php");
require_once("lib/EpayCore.class.php");

// 检查是否已安装
if (!$app_config['installed']) {
    echo json_encode(['status' => 'error', 'message' => '系统未安装']);
    exit;
}

// 验证请求参数
if (empty($_POST['out_trade_no']) || empty($_POST['subject']) || empty($_POST['total_fee'])) {
    echo json_encode(['status' => 'error', 'message' => '参数不完整，需要订单号、商品名称、订单金额']);
    exit;
}

// 连接数据库
try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
        $db_config['user'],
        $db_config['pass']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => '数据库连接失败: ' . $e->getMessage()]);
    exit;
}

// 检查订单号是否已存在
$stmt = $pdo->prepare("SELECT id FROM guojam_orders WHERE out_trade_no = :out_trade_no");
$stmt->execute([':out_trade_no' => $_POST['out_trade_no']]);
if ($stmt->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode(['status' => 'error', 'message' => '订单号已存在']);
    exit;
}

// 保存订单信息
$order_time = date('Y-m-d H:i:s');
try {
    $stmt = $pdo->prepare("INSERT INTO guojam_orders (out_trade_no, subject, total_fee, order_time) 
                          VALUES (:out_trade_no, :subject, :total_fee, :order_time)");
    $stmt->execute([
        ':out_trade_no' => $_POST['out_trade_no'],
        ':subject' => $_POST['subject'],
        ':total_fee' => $_POST['total_fee'],
        ':order_time' => $order_time
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => '保存订单失败: ' . $e->getMessage()]);
    exit;
}

// 构造支付参数
$parameter = [
    "pid" => $epay_config['pid'],
    "type" => "alipay",  // 固定为支付宝支付
    "notify_url" => $app_config['notify_url'],
    "return_url" => $app_config['return_url'],
    "out_trade_no" => $_POST['out_trade_no'],
    "name" => $_POST['subject'],
    "money" => $_POST['total_fee'],
];

// 创建支付链接
$epay = new EpayCore($epay_config);
$pay_url = $epay->getPayLink($parameter);

// 返回结果
echo json_encode([
    'status' => 'success',
    'pay_url' => $pay_url,
    'out_trade_no' => $_POST['out_trade_no']
]);
?>