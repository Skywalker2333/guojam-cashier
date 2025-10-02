<?php
// 引入配置文件和核心类
require_once("config.php");
require_once("lib/EpayCore.class.php");
?>
<!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>支付结果通知</title>
</head>
<body>
<?php
// 计算得出通知验证结果
$epay = new EpayCore($epay_config);
$verify_result = $epay->verifyReturn();

if ($verify_result) { // 验证成功
    // 商户订单号
    $out_trade_no = $_GET['out_trade_no'];
    // 支付平台交易号
    $trade_no = $_GET['trade_no'];
    // 交易状态
    $trade_status = $_GET['trade_status'];

    if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
        // 连接数据库更新订单状态
        try {
            $pdo = new PDO(
                "mysql:host={$db_config['host']};dbname={$db_config['name']};charset=utf8mb4",
                $db_config['user'],
                $db_config['pass']
            );
            
            $stmt = $pdo->prepare("UPDATE guojam_orders SET trade_status = 'TRADE_SUCCESS', trade_no = :trade_no 
                                  WHERE out_trade_no = :out_trade_no");
            $stmt->execute([
                ':trade_no' => $trade_no,
                ':out_trade_no' => $out_trade_no
            ]);
            
            echo "<h3>支付成功</h3>";
            echo "订单号: {$out_trade_no}<br>";
            echo "支付交易号: {$trade_no}<br>";
 echo "<h5>你现在可以返回之前的页面了</h5>";
        } catch (PDOException $e) {
            echo "<h3>支付成功，但订单状态更新失败</h3>";
        }
    } else {
        echo "<h3>支付状态：{$trade_status}</h3>";
    }
} else {
    // 验证失败
    echo "<h3>支付验证失败</h3>";
}
?>
</body>
</html>