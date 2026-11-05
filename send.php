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

// Etap 1: walidacja danych. Wysyłanie SMTP dodamy po testach.
respond(200, [
    'ok' => true,
    'message' => 'Dane formularza są poprawne. Wiadomość nie została wysłana.',
]);
