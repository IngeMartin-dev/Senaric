<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Cliente HTTP ligero para la API REST de Supabase.
 *
 * IMPORTANTE DE SEGURIDAD:
 *   - La SERVICE_ROLE_KEY SOLO se usa aquí (backend).
 *   - Esta clase NUNCA debe incluirse desde archivos servidos
 *     directamente al navegador (public/...).
 *   - Para operaciones "públicas" usa la PUBLISHABLE_KEY.
 *
 * La clase expone dos instancias:
 *   - Supabase::admin()  → service role (lectura/escritura sin RLS)
 *   - Supabase::public() → anon key (respeta RLS)
 */
final class Supabase
{
    private string $baseUrl;
    private string $apiKey;
    private string $bearer;
    private int $timeout;

    private function __construct(string $apiKey, string $label)
    {
        $url = (string) Env::get('SUPABASE_URL', '');
        if ($url === '') {
            throw new RuntimeException('SUPABASE_URL no está configurada.');
        }
        if ($apiKey === '') {
            // Permitimos construir el cliente; las llamadas fallarán luego con
            // un mensaje claro si se intenta usar sin haber rellenado el .env.
            $apiKey = 'missing-' . $label;
        }

        $this->baseUrl = rtrim($url, '/') . '/rest/v1';
        $this->apiKey = $apiKey;
        $this->bearer = $apiKey;
        $this->timeout = 15;
    }

    public static function admin(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self((string) Env::get('SUPABASE_SERVICE_ROLE_KEY', ''), 'service role');
        }
        return $instance;
    }

    public static function public(): self
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self((string) Env::get('SUPABASE_PUBLISHABLE_KEY', ''), 'publishable');
        }
        return $instance;
    }

    /**
     * SELECT — equivalente a SELECT ... FROM tabla
     * @param array $opts ['select'=>..., 'eq'=>['col'=>'val'], 'order'=>'col.asc', 'limit'=>int, 'offset'=>int]
     */
    public function select(string $table, array $opts = []): array
    {
        $url = $this->baseUrl . '/' . rawurlencode($table);
        $query = [];

        $select = $opts['select'] ?? '*';
        $query['select'] = $select;

        foreach (['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'like', 'ilike', 'in'] as $op) {
            if (!empty($opts[$op]) && is_array($opts[$op])) {
                foreach ($opts[$op] as $col => $val) {
                    $key = $col . '.' . $op;
                    $query[$key] = is_array($val) ? '(' . implode(',', array_map('strval', $val)) . ')' : (string) $val;
                }
            }
        }

        if (!empty($opts['order'])) {
            $query['order'] = (string) $opts['order'];
        }
        if (isset($opts['limit'])) {
            $query['limit'] = (string) max(1, min(1000, (int) $opts['limit']));
        }
        if (isset($opts['offset'])) {
            $query['offset'] = (string) max(0, (int) $opts['offset']);
        }
        if (!empty($opts['or'])) {
            $query['or'] = (string) $opts['or'];
        }

        $result = $this->request('GET', $url . '?' . http_build_query($query));
        return is_array($result) ? $result : [];
    }

    /**
     * SELECT por PK.
     */
    public function find(string $table, string|int $id, string $select = '*'): ?array
    {
        $rows = $this->select($table, [
            'select' => $select,
            'eq' => ['id' => $id],
            'limit' => 1,
        ]);
        return $rows[0] ?? null;
    }

    /**
     * INSERT uno o varios registros.
     *
     * @param array|array[] $rows
     * @param bool $returning devolver filas insertadas (necesario para conocer el id)
     */
    public function insert(string $table, array $rows, bool $returning = true): array
    {
        $payload = isset($rows[0]) ? $rows : [$rows];

        $headers = ['Content-Type: application/json'];
        if ($returning) {
            $headers[] = 'Prefer: return=representation';
        }

        $result = $this->request('POST', $this->baseUrl . '/' . rawurlencode($table), $payload, $headers);
        return is_array($result) ? $result : [];
    }

    /**
     * UPDATE por filtro.
     * @param array $data columnas a modificar
     * @param array $filters ['eq'=>['col'=>'val']]
     */
    public function update(string $table, array $data, array $filters): array
    {
        $url = $this->baseUrl . '/' . rawurlencode($table);
        $query = [];
        foreach (['eq', 'neq'] as $op) {
            if (!empty($filters[$op]) && is_array($filters[$op])) {
                foreach ($filters[$op] as $col => $val) {
                    $query[$col . '.' . $op] = (string) $val;
                }
            }
        }
        $headers = [
            'Content-Type: application/json',
            'Prefer: return=representation',
        ];

        $url .= $query ? ('?' . http_build_query($query)) : '';
        $result = $this->request('PATCH', $url, $data, $headers);
        return is_array($result) ? $result : [];
    }

    /**
     * DELETE por filtro.
     */
    public function delete(string $table, array $filters): array
    {
        $url = $this->baseUrl . '/' . rawurlencode($table);
        $query = [];
        foreach (['eq'] as $op) {
            if (!empty($filters[$op]) && is_array($filters[$op])) {
                foreach ($filters[$op] as $col => $val) {
                    $query[$col . '.' . $op] = (string) $val;
                }
            }
        }
        $headers = ['Prefer: return=representation'];
        $url .= $query ? ('?' . http_build_query($query)) : '';
        $result = $this->request('DELETE', $url, null, $headers);
        return is_array($result) ? $result : [];
    }

    /**
     * RPC — invoca una función/edge function definida en Supabase.
     */
    public function rpc(string $name, array $args = []): array
    {
        $url = $this->baseUrl . '/rpc/' . rawurlencode($name);
        $result = $this->request('POST', $url, $args, ['Content-Type: application/json']);
        return is_array($result) ? $result : [];
    }

    /**
     * Storage: URL pública para acceder a un archivo en un bucket.
     */
    public static function storageUrl(string $bucket, string $path): string
    {
        $base = rtrim((string) Env::get('SUPABASE_URL', ''), '/');
        return $base . '/storage/v1/object/public/' . rawurlencode($bucket) . '/' . ltrim($path, '/');
    }

    /**
     * Ejecuta una petición HTTP a la API REST.
     *
     * @return mixed array decodificado o string
     */
    private function request(string $method, string $url, mixed $body = null, array $extraHeaders = [])
    {
        $ch = curl_init();

        $headers = array_merge([
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->bearer,
            'Accept: application/json',
        ], $extraHeaders);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => false,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0 || $response === false) {
            throw new RuntimeException('Error de red al consultar Supabase.');
        }

        $decoded = json_decode((string) $response, true);

        if ($code >= 400) {
            $message = is_array($decoded) ? ($decoded['message'] ?? $decoded['error_description'] ?? 'Error Supabase') : 'Error Supabase';
            throw new RuntimeException((string) $message, $code);
        }

        return $decoded ?? [];
    }
}