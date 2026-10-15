
<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php', true, 303);
    exit;
}

$redirect = static function (string $status): void {
    header('Location: contact.php?status=' . rawurlencode($status), true, 303);
    exit;
};

$fields = [
    'name' => $_POST['name'] ?? $_POST['contact_name'] ?? null,
    'email' => $_POST['email'] ?? $_POST['contact_email'] ?? null,
    'subject' => $_POST['subject'] ?? '',
    'message' => $_POST['message'] ?? $_POST['contact_message'] ?? null,
];
$submission = [];
foreach ($fields as $field => $value) {
    if (!is_string($value)) {
        $redirect('invalid');
    }

    $submission[$field] = trim($value);
}

$name = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $submission['name']);
$email = $submission['email'];
$subject = trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $submission['subject']));
if ($subject === '') {
    $subject = 'General enquiry';
}
$message = $submission['message'];

if (
    $name === ''
    || strlen($name) > 200
    || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($email) > 254
    || $subject === ''
    || strlen($subject) > 200
    || $message === ''
    || strlen($message) > 12000
) {
    $redirect('invalid');
}

$recipient = 'carulasanfilomeno@gmail.com';
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$senderAddress = filter_var('website@' . $serverName, FILTER_VALIDATE_EMAIL)
    ? 'website@' . $serverName
    : 'website@localhost.localdomain';
$mailSubject = 'Website enquiry: ' . $subject;
if (function_exists('mb_encode_mimeheader')) {
    $mailSubject = mb_encode_mimeheader($mailSubject, 'UTF-8', 'B', "\r\n");
}
$mailBody = "A new message was sent through the QCS-Phils website.\n\n"
    . "Name: {$name}\n"
    . "Email: {$email}\n"
    . "Subject: {$subject}\n\n"
    . "Message:\n{$message}\n";
$headers = [
    'From: QCS-Phils Website <' . $senderAddress . '>',
    'Reply-To: ' . $email,
    'Content-Type: text/plain; charset=UTF-8',
    'X-Mailer: PHP/' . phpversion(),
];

if (!mail($recipient, $mailSubject, $mailBody, implode("\r\n", $headers))) {
    error_log('QCS-Phils contact email could not be sent by PHP mail().');
    $redirect('error');
}

$redirect('success');
?>
