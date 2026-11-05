<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

header('Content-Type: text/plain; charset=UTF-8');

echo 'PHPMailer loaded successfully';