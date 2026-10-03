<?php
declare(strict_types=1);

require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Auth.php';

/**
 * Convierte errores y excepciones en respuestas HTTP estructuradas.
 *
 * - En modo debug expone el stack trace.
 * - En producción oculta detalles internos.
 */
final class HttpExceptionHandler
{
    public static function handle(\Throwable $e): Response
    {
        $debug = (bool) Env::get('APP_DEBUG', false);
        $status = $e instanceof HttpException ? $e->getCode() : 500;
        if ($status < 400 || $status > 599) $status = 500;

        Logger::error('http_exception', [
            'status' => $status,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        $payload = [
            'ok' => false,
            'error' => $e->getMessage(),
        ];
        if ($debug) {
            $payload['trace'] = $e->getTraceAsString();
        }

        return Response::json($payload, $status);
    }
}