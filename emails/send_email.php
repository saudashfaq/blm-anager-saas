<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

class MailService
{
    protected $mailer;
    protected $config;
    protected $lastError = null;

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
            // Enforce finite connection/IO timeout to avoid hanging on unresponsive hosts
            $timeoutSeconds = (int)(getenv('MAIL_TIMEOUT') ?: 10);
            $this->mailer->Timeout = $timeoutSeconds; // Applies to fsockopen and SMTP reads
            $this->mailer->SMTPKeepAlive = false;
        }

        if (getenv('MAIL_DEBUG') == true) {
            $this->mailer->SMTPDebug = 2;
            $this->mailer->Debugoutput = function ($str, $level) {
                $this->lastError = trim(($this->lastError ? $this->lastError . "\n" : '') . $str);
            };
        }

        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->setFrom(
            $settings['from_email'] ?? 'no-reply@domain.com',
            $settings['from_name'] ?? 'No Reply'
        );
    }

    public function send($to, $subject, $body, $isHtml = true, $cc = null, $from = null, $throwOnError = false)
    {
        try {
            $this->lastError = null;
            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
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
            $hostInfo = '';
            if (isset($this->mailer->Host) && isset($this->mailer->Port)) {
                $hostInfo = 'Host: ' . $this->mailer->Host . ', Port: ' . $this->mailer->Port;
            }
            $timeoutInfo = '';
            if (isset($this->mailer->Timeout)) {
                $timeoutInfo = ' (Timeout: ' . (int)$this->mailer->Timeout . 's)';
            }
            $composed = 'Mailer Error: ' . $this->mailer->ErrorInfo;
            if (!empty($hostInfo)) {
                $composed .= "\n" . $hostInfo . $timeoutInfo;
            }
            if (!empty($e->getMessage())) {
                $composed .= "\nException: " . $e->getMessage();
            }
            $this->lastError = trim($composed);
            error_log($this->lastError);
            if ($throwOnError) {
                throw new Exception($this->lastError);
            }
            return false;
        }
    }

    public function getLastError()
    {
        return $this->lastError;
    }
}
