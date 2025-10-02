<?php
require_once 'config.php';

/**
 * 邮件发送类
 */
class Mailer {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        $this->smtp_host = SMTP_HOST;
        $this->smtp_port = SMTP_PORT;
        $this->smtp_username = SMTP_USERNAME;
        $this->smtp_password = SMTP_PASSWORD;
        $this->from_email = FROM_EMAIL;
        $this->from_name = FROM_NAME;
    }
    
    /**
     * 发送支付成功邮件
     */
    public function sendPaymentSuccessEmail($to_email, $order_info, $card_info) {
        $subject = "【" . SITE_NAME . "】支付成功通知 - 订单号：" . $order_info['order_no'];
        
        // 构建卡密信息HTML
        $card_html = "";
        if (is_array($card_info) && !empty($card_info)) {
            // 单个卡密
            if (isset($card_info['card_number'])) {
                $card_html = $this->buildCardHtml($card_info);
            } 
            // 多个卡密（批量购买）
            else if (isset($card_info[0])) {
                foreach ($card_info as $index => $card) {
                    $card_html .= $this->buildCardHtml($card, $index + 1);
                }
            }
        }
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>支付成功通知</title>
            <style>
                body { font-family: 'Microsoft YaHei', Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .card-info { background: #fff8e1; border: 2px dashed #ffa000; padding: 20px; margin: 20px 0; border-radius: 8px; }
                .order-info { background: #e8f5e8; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #4caf50; }
                .warning-box { background: #ffebee; border: 1px solid #ffcdd2; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .footer { text-align: center; margin-top: 30px; padding: 20px; color: #666; font-size: 12px; background: #f9f9f9; }
                .card-item { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #e0e0e0; }
                .card-number { font-family: 'Courier New', monospace; font-size: 16px; font-weight: bold; color: #d32f2f; }
                .card-password { font-family: 'Courier New', monospace; font-size: 16px; font-weight: bold; color: #1976d2; }
                .copy-hint { color: #ff6d00; font-size: 12px; margin-top: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 支付成功！</h1>
                    <p>感谢您在 " . SITE_NAME . " 的购买</p>
                </div>
                
                <div class='content'>
                    <div class='order-info'>
                        <h3>📦 订单信息</h3>
                        <p><strong>订单号：</strong> {$order_info['order_no']}</p>
                        <p><strong>商品名称：</strong> {$order_info['product_name']}</p>
                        <p><strong>购买数量：</strong> " . ($order_info['quantity'] ?? 1) . "</p>
                        <p><strong>商品单价：</strong> ¥{$order_info['price']}</p>
                        " . (isset($order_info['discount_amount']) && $order_info['discount_amount'] > 0 ? 
                        "<p><strong>优惠金额：</strong> -¥{$order_info['discount_amount']}</p>" : "") . "
                        <p><strong>实付金额：</strong> <span style='color: #e74c3c; font-weight: bold;'>¥{$order_info['final_amount']}</span></p>
                        <p><strong>支付时间：</strong> {$order_info['paid_at']}</p>
                    </div>
                    
                    <div class='card-info'>
                        <h3>🔑 卡密信息</h3>
                        {$card_html}
                        <div class='warning-box'>
                            <strong>⚠️ 重要提示：</strong>
                            <ul style='margin: 10px 0; padding-left: 20px;'>
                                <li>请及时使用卡密，避免过期</li>
                                <li>建议截图保存卡密信息</li>
                                <li>请勿泄露卡密给他人</li>
                                <li>如遇到问题，请联系客服</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                        <h4>💡 使用说明：</h4>
                        <ul style='margin: 10px 0; padding-left: 20px;'>
                            <li>请按照商品说明使用卡密</li>
                            <li>使用过程中遇到问题可联系客服</li>
                            <li>建议立即使用，以免遗忘</li>
                        </ul>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>此邮件由系统自动发送，请勿回复</p>
                    <p>如有问题，请联系客服邮箱：{$this->from_email}</p>
                    <p>&copy; " . date('Y') . " " . SITE_NAME . " 版权所有</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($to_email, $subject, $message);
    }
    
    /**
     * 构建卡密HTML
     */
    private function buildCardHtml($card, $index = null) {
        $title = $index ? "卡密 #{$index}" : "卡密";
        $html = "<div class='card-item'>";
        $html .= "<h4>{$title}</h4>";
        $html .= "<p><strong>卡号：</strong> <span class='card-number'>{$card['card_number']}</span></p>";
        if (!empty($card['card_password'])) {
            $html .= "<p><strong>密码：</strong> <span class='card-password'>{$card['card_password']}</span></p>";
        }
        $html .= "<p class='copy-hint'>📋 点击上方卡号/密码可复制</p>";
        $html .= "</div>";
        return $html;
    }
    
    /**
     * 发送邮件（使用465端口SSL加密）
     */
    private function sendEmail($to, $subject, $message) {
        // 使用465端口需要SSL加密
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: " . $this->from_name . " <" . $this->from_email . ">" . "\r\n";
        $headers .= "Reply-To: " . $this->from_email . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        $headers .= "X-Priority: 1" . "\r\n"; // 高优先级
        
        // 添加返回路径
        $headers .= "Return-Path: " . $this->from_email . "\r\n";
        
        // 对于465端口，使用SSL加密
        ini_set("SMTP", "ssl://" . $this->smtp_host);
        ini_set("smtp_port", $this->smtp_port);
        ini_set("sendmail_from", $this->from_email);
        
        if (mail($to, $subject, $message, $headers, "-f " . $this->from_email)) {
            error_log("邮件发送成功 - 收件人: {$to}, 主题: {$subject}");
            return true;
        } else {
            error_log("邮件发送失败 - 收件人: {$to}, 主题: {$subject}");
            // 记录更详细的错误信息
            $last_error = error_get_last();
            if ($last_error) {
                error_log("邮件发送错误: " . $last_error['message']);
            }
            return false;
        }
    }
    
    /**
     * 测试邮件配置
     */
    public function testEmailConfig($to_email) {
        $subject = "【测试】邮件配置测试 - " . SITE_NAME;
        $message = "
        <html>
        <body style='font-family: Microsoft YaHei, Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #3498db; text-align: center;'>邮件配置测试</h2>
                <p>这是一封测试邮件，用于验证 <strong>" . SITE_NAME . "</strong> 的邮件配置是否正确。</p>
                <div style='background: #e8f4fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <p><strong>发送时间：</strong>" . date('Y-m-d H:i:s') . "</p>
                    <p><strong>发件人：</strong>" . $this->from_name . " &lt;" . $this->from_email . "&gt;</p>
                    <p><strong>SMTP服务器：</strong>" . $this->smtp_host . ":" . $this->smtp_port . "</p>
                </div>
                <p style='color: #27ae60; font-weight: bold;'>如果收到此邮件，说明邮件配置成功！</p>
                <hr>
                <p style='color: #666; font-size: 12px;'>此邮件为系统自动发送，请勿回复。</p>
            </div>
        </body>
        </html>
        ";
        
        return $this->sendEmail($to_email, $subject, $message);
    }
}

// 创建全局邮件实例
$mailer = new Mailer();
?>