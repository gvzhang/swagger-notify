<?php
require_once __DIR__ . '/vendor/autoload.php';
$config = spyc_load_file(rootPath() . "/env.yaml");
$notification = new \App\Notification($config["repoPath"], $config["target"]);
$notification->execute();