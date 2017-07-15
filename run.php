<?php
require_once __DIR__ . '/vendor/autoload.php';
$config = spyc_load_file(rootPath() . "/env.yaml");

$mail = new PHPMailer();
$mail->isSMTP();                                      // Set mailer to use SMTP
$mail->Host = $config["email"]["host"];  // Specify main and backup SMTP servers
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = $config["email"]["username"];                 // SMTP username
$mail->Password = $config["email"]["password"];                           // SMTP password
$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
$mail->Port = $config["email"]["port"];                                    // TCP port to connect to
$mail->setFrom($config["email"]["username"]);

$notification = new \App\Notification($config["repoPath"], $config["target"], $mail);
$notification->execute();