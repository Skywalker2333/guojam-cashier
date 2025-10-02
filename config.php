<?php
/* *
 * 配置文件
 */

// 数据库配置
$db_config = [
    'host' => 'localhost',      // 数据库主机
    'user' => 'guojamcashier',           // 数据库用户名
    'pass' => 'guojamcashier',               // 数据库密码
    'name' => 'guojamcashier'  // 数据库名
];

// 支付接口配置
$epay_config = [
    'apiurl' => 'https://pay.typecho.cyou/',  // 支付接口地址
    'pid' => '129234094',                    // 商户ID
    'key' => '6O4cjBYq6Cz8JE4TjPo4eKdq8OcsBjBk'  // 商户密钥
];

// 程序配置
$app_config = [
    'installed' => true,       // 是否已安装
    'notify_url' => 'https://gc.guojam.fun/notify_url.php',  // 异步通知地址
    'return_url' => 'https://gc.guojam.fun/return_url.php'    // 同步返回地址
];

// 初始化数据库（如果未安装）
if (!$app_config['installed']) {
    try {
        // 连接数据库
        $pdo = new PDO(
            "mysql:host={$db_config['host']};charset=utf8",
            $db_config['user'],
            $db_config['pass']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 创建数据库
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // 选择数据库
        $pdo->exec("USE `{$db_config['name']}`");
        
        // 创建订单表
        $sql = "CREATE TABLE IF NOT EXISTS `guojam_orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `out_trade_no` varchar(50) NOT NULL COMMENT '商户订单号',
            `subject` varchar(255) NOT NULL COMMENT '商品名称',
            `total_fee` decimal(10,2) NOT NULL COMMENT '订单金额',
            `order_time` datetime NOT NULL COMMENT '订单时间',
            `trade_status` varchar(20) DEFAULT 'WAIT_PAY' COMMENT '订单状态',
            `trade_no` varchar(50) DEFAULT NULL COMMENT '支付平台交易号',
            PRIMARY KEY (`id`),
            UNIQUE KEY `out_trade_no` (`out_trade_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='订单表'";
        
        $pdo->exec($sql);
        
        // 更新为已安装状态（实际使用时应改为true并重新上传）
        // $app_config['installed'] = true;
        echo "数据库初始化成功，请将config.php中的installed改为true";
        exit;
    } catch (PDOException $e) {
        die("数据库初始化失败: " . $e->getMessage());
    }
}
?>