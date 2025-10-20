<?php
require_once 'config.php';

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
    
    public function sendEmail($to, $subject, $body) {
        if (!EMAIL_NOTIFY_ENABLED) {
            return false;
        }
        
        $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
        $headers .= "Reply-To: {$this->from_email}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        return mail($to, $subject, $body, $headers);
    }
    
    public function testEmailConfig($test_email) {
        $subject = '测试邮件 - ' . SITE_NAME;
        $body = '<h3>这是一封测试邮件</h3><p>如果您收到这封邮件，说明邮件配置正确！</p>';
        
        return $this->sendEmail($test_email, $subject, $body);
    }
}
?>