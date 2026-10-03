<?php
declare(strict_types=1);

require_once __DIR__ . '/../Core/Validator.php';
require_once __DIR__ . '/../Core/Sanitizer.php';
require_once __DIR__ . '/../Core/Logger.php';
require_once __DIR__ . '/../Core/Auth.php';
require_once __DIR__ . '/MailService.php';

final class ContactService
{
    public function __construct(private MailService $mail = new MailService()) {}

    public function submit(array $input): array
    {
        $errors = Validator::check($input, [
            'nombre' => 'required|string|min:2|max:120',
            'email' => 'required|email',
            'telefono' => 'string|max:40',
            'asunto' => 'required|string|min:3|max:120',
            'mensaje' => 'required|string|min:10|max:2000',
        ]);
        if (!empty($errors)) {
            throw new HttpException(422, 'Datos del mensaje inválidos.');
        }

        $payload = [
            'nombre' => Sanitizer::string($input['nombre'], 120),
            'email' => Sanitizer::email($input['email']),
            'telefono' => Sanitizer::string($input['telefono'] ?? '', 40),
            'asunto' => Sanitizer::string($input['asunto'], 120),
            'mensaje' => Sanitizer::string($input['mensaje'], 2000),
        ];

        $body = sprintf(
            "Nuevo mensaje de contacto\n\nNombre: %s\nEmail: %s\nTeléfono: %s\nAsunto: %s\n\nMensaje:\n%s\n",
            $payload['nombre'],
            $payload['email'],
            $payload['telefono'] ?: '—',
            $payload['asunto'],
            $payload['mensaje']
        );

        try {
            $this->mail->send(
                'Nuevo mensaje de contacto: ' . $payload['asunto'],
                $body,
                $payload['email']
            );
        } catch (\Throwable $e) {
            Logger::error('contact_email_failed', ['error' => $e->getMessage()]);
            throw new HttpException(502, 'No se pudo enviar el mensaje. Intenta más tarde.');
        }

        Logger::info('contact_message_received', ['email' => $payload['email']]);
        return ['sent' => true];
    }
}