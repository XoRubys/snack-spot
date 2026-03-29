<?php

namespace SnackSpot\Core;

require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mail
{
    public static function send($orderId)
    {
        $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
        try {
            //服务器配置
            $mail->CharSet = "UTF-8";                     //设定邮件编码
            $mail->SMTPDebug = 0;                        // 调试模式输出
            $mail->isSMTP();                             // 使用SMTP
            $mail->Host = 'smtp.163.com';                // SMTP服务器
            $mail->SMTPAuth = true;                      // 允许 SMTP 认证
            $mail->Username = 'youmail@163.com';                // SMTP 用户名  即邮箱的用户名
            $mail->Password = 'youkey';             // SMTP 密码  部分邮箱是授权码(例如163邮箱)
            $mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议
            $mail->Port = 465;                            // 服务器端口 25 或者465 具体要看邮箱服务器支持

            $mail->setFrom('youmail@163.com', '零食铺助手');  //发件人
            $mail->addAddress('youemail2@163.com', '收件人名称');  // 收件人
            $mail->addReplyTo('youmail@163.com', '零食铺助手'); //回复的时候回复给哪个邮箱 建议和发件人不同
            $mail->isHTML(true);
            $mail->Subject = '新订单 - ' . $orderId;
            $mail->Body    = "你有新订单，请前往 https://youdomain.com/pages/admin/order/detail?id={$orderId} 查看详情。";
            $mail->AltBody = "您有新订单，请前往 https://youdomain.com/pages/admin/order/detail?id={$orderId} 查看详情。";

            $mail->send();
            echo '邮件发送成功';
        } catch (Exception $e) {
            echo '邮件发送失败: ', $mail->ErrorInfo;
        }
    }
}
