<?php
declare(strict_types=1);

require_once __DIR__ . '/Session.php';
require_once __DIR__ . '/../Config/env.php';
require_once __DIR__ . '/../Config/supabase.php';

/**
 * Capa de autenticación.
 *
 * Estrategia:
 *   - Supabase Auth es la fuente de verdad para identidad (email + contraseña).
 *   - El frontend inicia sesión con supabase.auth.signInWithPassword()
 *     y obtiene un access_token JWT.
 *   - El JWT viaja en cada request al backend como Authorization: Bearer ...
 *   - El backend NO confía en la sesión PHP para identificar al usuario:
 *     valida el JWT contra Supabase en cada request.
 *   - Adicionalmente, el backend crea una sesión PHP ligera con el sub (uid)
 *     y el rol, solo para acelerar comprobaciones dentro del mismo proceso.
 *
 * Esto evita problemas de session fixation/hijacking al delegar la identidad
 * a Supabase Auth.
 */
final class Auth
{
    public static function loginWithPassword(string $email, string $password): array
    {
        $url = rtrim((string) Env::get('SUPABASE_URL'), '/') . '/auth/v1/token?grant_type=password';
        $key = (string) Env::get('SUPABASE_PUBLISHABLE_KEY');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $key,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode(['email' => $email, 'password' => $password]),
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || $resp === false) {
            $data = json_decode((string) $resp, true);
            $msg = is_array($data) ? ($data['error_description'] ?? $data['msg'] ?? 'Credenciales inválidas') : 'Credenciales inválidas';
            throw new RuntimeException((string) $msg, $code ?: 401);
        }
        $data = json_decode((string) $resp, true);
        if (!is_array($data) || empty($data['access_token'])) {
            throw new RuntimeException('Respuesta inválida del servidor de autenticación.');
        }
        return $data;
    }

    public static function registerWithPassword(string $email, string $password, array $meta = []): array
    {
        $url = rtrim((string) Env::get('SUPABASE_URL'), '/') . '/auth/v1/signup';
        $key = (string) Env::get('SUPABASE_PUBLISHABLE_KEY');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $key,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'email' => $email,
                'password' => $password,
                'data' => $meta,
            ]),
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 400) {
            $data = json_decode((string) $resp, true);
            $msg = is_array($data) ? ($data['error_description'] ?? $data['msg'] ?? 'No se pudo crear la cuenta') : 'No se pudo crear la cuenta';
            throw new RuntimeException((string) $msg, $code);
        }
        $data = json_decode((string) $resp, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Devuelve el usuario autenticado en la petición actual o null.
     * Valida el JWT contra Supabase Auth /auth/v1/user.
     *
     * Cachea en sesión PHP para reducir latencia durante la misma request.
     */
    public static function user(Request $req): ?array
    {
        $cached = Session::get('auth.user');
        if (is_array($cached) && isset($cached['id'])) {
            return self::enrichRole($cached);
        }

        $authHeader = $req->header('authorization', '');
        if (!is_string($authHeader) || !preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
            return null;
        }
        $token = trim($m[1]);
        if ($token === '') return null;

        $url = rtrim((string) Env::get('SUPABASE_URL'), '/') . '/auth/v1/user';
        $key = (string) Env::get('SUPABASE_PUBLISHABLE_KEY');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'apikey: ' . $key,
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || $resp === false) {
            return null;
        }

        $data = json_decode((string) $resp, true);
        if (!is_array($data) || empty($data['id'])) {
            return null;
        }

        $user = [
            'id' => (string) $data['id'],
            'email' => (string) ($data['email'] ?? ''),
            'phone' => $data['phone'] ?? null,
            'meta' => $data['user_metadata'] ?? [],
            'token' => $token,
        ];

        Session::set('auth.user', $user);

        return self::enrichRole($user);
    }

    /**
     * Enriquece el usuario con su rol (admin o customer) según ADMIN_EMAILS.
     */
    public static function enrichRole(array $user): array
    {
        $adminList = array_filter(array_map('trim', explode(',', (string) Env::get('ADMIN_EMAILS', ''))));
        $user['role'] = in_array(strtolower($user['email'] ?? ''), array_map('strtolower', $adminList), true)
            ? 'admin'
            : 'customer';
        return $user;
    }

    public static function requireUser(Request $req): array
    {
        $user = self::user($req);
        if ($user === null) {
            throw new HttpException(401, 'Necesitas iniciar sesión para realizar esta acción.');
        }
        return $user;
    }

    public static function requireAdmin(Request $req): array
    {
        $user = self::requireUser($req);
        if (($user['role'] ?? '') !== 'admin') {
            throw new HttpException(403, 'No tienes permisos para realizar esta acción.');
        }
        return $user;
    }

    public static function logout(): void
    {
        Session::forget('auth.user');
        Session::flush();
    }
}

/**
 * Excepción HTTP para que el router la traduzca a JSON.
 */
final class HttpException extends RuntimeException
{
    public function __construct(int $status, string $message)
    {
        parent::__construct($message, $status);
    }
}