<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/env.php';
require_once __DIR__ . '/../Core/Logger.php';

/**
 * Servicio de envío de correo.
 *
 * Estrategia:
 *   - Producción: usa SMTP autenticado con la función mail() de PHP es
 *     frágil. Aquí se intenta con mail() y se registra el resultado.
 *     Para un entorno real, integre PHPMailer o un servicio como
 *     Resend / Mailgun (configurable vía env MAIL_DRIVER=resend).
 *   - Resend (por ejemplo): POST a https://api.resend.com/emails con
 *     API key desde env.
 *
 * La implementación se mantiene libre de dependencias externas obligatorias.
 */
final class MailService
{
    private string $driver;

    public function __construct()
    {
        $this->driver = strtolower((string) Env::get('MAIL_DRIVER', 'native'));
    }

    public function send(string $subject, string $body, string $replyTo = ''): bool
    {
        return match ($this->driver) {
            'log' => $this->sendViaLog($subject, $body, $replyTo),
            default => $this->sendViaNative($subject, $body, $replyTo),
        };
    }

    private function sendViaLog(string $subject, string $body, string $replyTo): bool
    {
        Logger::info('mail_outbound', [
            'subject' => $subject,
            'body_preview' => substr($body, 0, 200),
            'reply_to' => $replyTo,
            'to' => (string) Env::get('MAIL_TO', ''),
        ]);
        return true;
    }

    private function sendViaNative(string $subject, string $body, string $replyTo): bool
    {
        $to = (string) Env::get('MAIL_TO', '');
        $from = (string) Env::get('MAIL_FROM', 'no-reply@localhost');
        if ($to === '') {
            // No hay destino configurado: degradar a log silencioso.
            return $this->sendViaLog($subject, $body, $replyTo);
        }

        $headers = [
            'From: ' . $from,
            'Reply-To: ' . ($replyTo ?: $from),
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . PHP_VERSION,
        ];

        // mail() puede bloque a entornos sin MTA. Loggear siempre el resultado.
        $sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
        Logger::info('mail_native', ['sent' => $sent, 'to' => $to, 'subject' => $subject]);
        return $sent;
    }
}