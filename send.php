<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, ['ok' => false, 'error' => 'Dozwolona jest tylko metoda POST.']);
}

if (isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 16384) {
    respond(413, ['ok' => false, 'error' => 'Formularz jest zbyt duży.']);
}

function field(string $name, int $maxLength): string
{
    $value = $_POST[$name] ?? null;
    if (!is_string($value)) {
        respond(422, ['ok' => false, 'error' => "Nieprawidłowe pole: $name."]);
    }

    $value = trim($value);
    if ($value === '' || strlen($value) > $maxLength) {
        respond(422, ['ok' => false, 'error' => "Nieprawidłowa długość pola: $name."]);
    }

    return $value;
}

$name = field('name', 300);
$email = field('email', 254);
$type = field('type', 100);
$message = field('message', 8000);

if (filter_var($email, FILTER_VALIDATE_EMAIL) === false ||
    preg_match('/[\r\n]/', $email)) {
    respond(422, ['ok' => false, 'error' => 'Podaj prawidłowy adres e-mail.']);
}

$allowedTypes = [
    'Pieczątka automatyczna',
    'Datownik',
    'Stempel drewniany',
    'Inna / nie wiem jeszcze',
];

if (!in_array($type, $allowedTypes, true)) {
    respond(422, ['ok' => false, 'error' => 'Nieznany rodzaj pieczątki.']);
}


// Ochrona przed znakami sterującymi w nagłówkach i treści wiadomości.
if (preg_match('/[\r\n]/', $name) || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $name . $message)) {
    respond(422, ['ok' => false, 'error' => 'Niedozwolone znaki w formularzu.']);
}

$configPath = __DIR__ . '/private/smtp-config.php';
if (!is_readable($configPath)) {
    error_log('Brak konfiguracji SMTP.');
    respond(503, ['ok' => false, 'error' => 'Wysyłanie wiadomości jest chwilowo niedostępne.']);
}

try {
    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('Nieprawidłowa konfiguracja SMTP.');
    }

    require_once __DIR__ . '/vendor/autoload.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = (string) $config['host'];
    $mail->Port = (int) $config['port'];
    $mail->SMTPAuth = true;
    $mail->Username = (string) $config['username'];
    $mail->Password = (string) $config['password'];
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 15;

    $mail->setFrom('formularz@stamp-pieczatki.pl', 'STEMPEL — formularz');
    $mail->addAddress('rafal.m4@gmail.com');
    $mail->addReplyTo($email, $name);
    $mail->Subject = 'Zapytanie o pieczątkę — ' . $type;
    $mail->Body = "Imię / firma: $name\nE-mail: $email\nProdukt: $type\n\n$message";
    $mail->send();
} catch (\Throwable $exception) {
    // Nie logujemy pełnego wyjątku: może zawierać szczegóły połączenia SMTP.
    error_log('Wysyłanie formularza nie powiodło się: ' . get_class($exception));
    respond(502, ['ok' => false, 'error' => 'Nie udało się wysłać wiadomości. Spróbuj ponownie później.']);
}

respond(200, ['ok' => true, 'message' => 'Wiadomość została wysłana.']);
