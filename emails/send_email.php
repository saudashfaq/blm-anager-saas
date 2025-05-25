<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

class MailService
{
    protected $mailer;
    protected $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/config.php';
        $this->mailer = new PHPMailer(true);
        $this->setupMailer();
    }

    protected function setupMailer()
    {
        $method = $this->config['use'];
        $settings = $this->config[$method] ?? [];

        if ($method === 'default') {
            $this->mailer->isMail();
        } else {
            $this->mailer->isSMTP();
            $this->mailer->Host       = $settings['host'];
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $settings['username'];
            $this->mailer->Password   = $settings['password'];
            $this->mailer->SMTPSecure = $settings['encryption'];
            $this->mailer->Port       = $settings['port'];
        }

        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->setFrom(
            $settings['from_email'] ?? 'no-reply@domain.com',
            $settings['from_name'] ?? 'No Reply'
        );
    }

    public function send($to, $subject, $body, $isHtml = true, $cc = null, $from = null)
    {
        try {
            $to = is_array($to) ? $to : [$to];
            foreach ($to as $recipient) {
                $this->mailer->addAddress($recipient);
            }

            if ($cc) {
                $cc = is_array($cc) ? $cc : [$cc];
                foreach ($cc as $ccEmail) {
                    $this->mailer->addCC($ccEmail);
                }
            }

            if ($from && isset($from['email'])) {
                $this->mailer->setFrom(
                    $from['email'],
                    $from['name'] ?? 'No Reply'
                );
            }

            $this->mailer->isHTML($isHtml);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;
            if (!$isHtml) {
                $this->mailer->AltBody = strip_tags($body);
            }

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
}
