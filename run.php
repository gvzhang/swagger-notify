<?php
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($argv[1]) || empty($argv[1])) {
    throw new \InvalidArgumentException("repoPath Error");
}
if (!is_dir($argv[1])) {
    throw new \InvalidArgumentException("repoPath Error");
}
$repoPath = $argv[1];

// 读取配置
$config = spyc_load_file(rootPath() . "/env.yaml");

$mail = new PHPMailer();
$mail->CharSet = 'UTF-8';
$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = $config["email"]["host"];  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = $config["email"]["username"];                 // SMTP username
$mail->Password = $config["email"]["password"];                           // SMTP password
$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = $config["email"]["port"];                                    // TCP port to connect to
$mail->setFrom($config["email"]["username"]);

$repository = new \App\Repository();
$notification = new \App\Notification($repoPath, $config["target"], $mail, $repository);
$notification->execute();