<?php
//display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/emails/send_email.php';

try {
    $mailService = new MailService();

    $result = $mailService->send('support@backlinksvalidator.com', 'Test Email', 'This is a test email');

    //display all email related errors
    if (!$result) {
        $error = $mailService->getLastError();
        if ($error) {
            echo nl2br(htmlentities($error));
        } else {
            echo 'Unknown mail error. Check server logs for details.';
        }
    } else {
        echo 'Email sent successfully';
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
