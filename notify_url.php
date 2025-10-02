<?php
// 引入配置文件和核心类
require_once("config.php");
require_once("lib/EpayCore.class.php");

// 验证是否已安装
if (!$app_config['installed']) {
    exit("fail");
}

// 计算得出通知验证结果
$epay = new EpayCore($epay_config);
$verify_result = $epay->verifyNotify();

if ($verify_result) { // 验证成功
    // 商户订单号
    $out_trade_no = $_GET['out_trade_no'];
    // 支付平台交易号
    $trade_no = $_GET['trade_no'];
    // 交易状态
    $trade_status = $_GET['trade_status'];

    if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
        // 连接数据库
        try {
            $pdo = new PDO(
                "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
                $db_config['user'],
                $db_config['pass']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 更新订单状态
            $stmt = $pdo->prepare("UPDATE guojam_orders SET trade_status = 'TRADE_SUCCESS', trade_no = :trade_no 
                                  WHERE out_trade_no = :out_trade_no");
            $stmt->execute([
                ':trade_no' => $trade_no,
                ':out_trade_no' => $out_trade_no
            ]);
        } catch (PDOException $e) {
            // 记录错误日志
            file_put_contents('notify_error.log', date('Y-m-d H:i:s') . ' ' . $e->getMessage() . "\n", FILE_APPEND);
            exit("fail");
        }
    }

    // 验证成功返回
    echo "success";
} else {
    // 验证失败
    echo "fail";
}
?>