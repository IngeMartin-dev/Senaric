# Tienda de Artesanías

Tienda online de artesanías locales con **PHP puro + Supabase (Postgres + Auth + Storage)**.
Diseño profesional, seguro, mantenible y preparado para producción.

---

## ⚙️ Stack

| Capa | Tecnología |
|------|------------|
| Backend | PHP 8+ (sin framework) |
| Frontend | HTML5 + CSS3 + JavaScript vanilla (sin dependencias) |
| DB / Auth / Storage | Supabase (PostgreSQL) |
| Servidor web | Apache con `mod_rewrite` (XAMPP, WAMP, etc.) |

> No se usa framework PHP. La arquitectura interna (Router, Services, Repositories,
> Core) sigue el mismo patrón que cualquier framework moderno pero sin coste de
> dependencias, manteniendo compatibilidad plena con hosting PHP corriente.

---

## 📁 Estructura

```
tienda-artesanias/
├── index.php                # Front controller del sitio
├── .env / .env.example       # Configuración sensible (URLs, claves)
├── .htaccess                 # Rutas + cabeceras de seguridad
├── assets/                   # CSS, JS, imágenes
│   ├── css/
│   ├── js/
│   └── img/
├── partials/                 # Header/footer compartidos
├── admin/                    # Panel de administración (UI)
├── api/                      # API REST (backend)
│   ├── index.php             # Front controller API
│   └── admin/index.php       # Endpoints admin
├── src/                      # Código fuente backend
│   ├── Config/               # env, cliente Supabase
│   ├── Core/                 # Router, Request, Response, Session, CSRF, Validator…
│   ├── Services/             # Lógica de negocio
│   ├── Repositories/         # Acceso a datos
│   └── Helpers/              # Funciones globales
├── database/                 # Esquema y políticas
│   ├── schema.sql            # Tablas, índices, ENUM, triggers
│   ├── policies.sql          # Row Level Security
│   ├── storage.sql          # Buckets y políticas de Storage
│   └── seed.sql             # Datos de ejemplo
└── storage/                  # Logs/cache (no-interfaz)
    ├── cache/
    └── logs/
```

---

## 🚦 Puesta en marcha

### 1. Configurar `.env`

```bash
cp .env.example .env
```

Edita `.env` y rellena:

| Variable | Descripción |
|----------|-------------|
| `APP_URL` | URL pública (ej. `https://tienda-artesanias.co` o `http://localhost/tienda-artesanias`) |
| `APP_KEY` | Clave aleatoria de 64 caracteres hex. Genera con: `php -r "echo bin2hex(random_bytes(32));"` |
| `SUPABASE_URL` | Tu URL de Supabase |
| `SUPABASE_PUBLISHABLE_KEY` | La clave anon (pública) |
| `SUPABASE_SERVICE_ROLE_KEY` | La clave service_role (PRIVADA, sólo backend) |
| `ADMIN_EMAILS` | Lista de correos que tendrán rol admin (separados por coma) |

### 2. Crear las tablas y políticas en Supabase

En el **SQL editor** de Supabase ejecuta, en este orden:

1. `database/schema.sql`     — crea tablas, ENUM, índices, triggers y la función `decrement_stock`
2. `database/policies.sql`  — habilita RLS y crea las políticas
3. `database/storage.sql`   — crea políticas para los buckets
4. (opcional) `database/seed.sql` — datos de ejemplo

### 3. Crear los buckets desde Storage:

- `products` (público)
- `categories` (público)

### 4. Crear el primer usuario administrador

En **Authentication → Users** crea el usuario (o regístralo desde la UI).
Luego en SQL editor:

```sql
update auth.users
   set raw_app_meta_data = raw_app_meta_data || '{"is_admin": true}'::jsonb
 where email = 'tu-admin@dominio.com';
```

Y registra ese mismo correo en `ADMIN_EMAILS` del `.env`.

### 5. Despliegue

Coloca la carpeta del proyecto bajo tu `htdocs/` o configúrala como DocumentRoot.
Asegúrate de que Apache tenga `mod_rewrite` activado.

---

## 🔐 Seguridad implementada

- **CSRF** — token de sesión validado en cada POST/PUT/PATCH/DELETE.
- **XSS** — todas las salidas pasan por `htmlspecialchars()` (helper `e()`).
- **SQL Injection** — Supabase REST + `Sanitizer` (no concatenamos strings).
- **Session Fixation / Hijacking** — `HttpOnly`, `Secure`, `SameSite=Lax`,
  `session_regenerate_id()` al cambiar credenciales.
- **Broken Access Control / IDOR** — cada endpoint admin valida rol.
- **Mass Assignment** — `allowlist` en repositorios; sólo columnas válidas.
- **Credenciales** — fuera del repositorio (`.env` + `RedirectMatch 404 /\.env`).
- **Errores** — `display_errors=Off` + handler que devuelve JSON genérico.
- **CSP / HSTS / X-Frame-Options / Referrer-Policy** — vía `.htaccess`.
- **Rate limiting** — por IP y por endpoint (auth, contact).

---

## 🌐 Rutas

| Ruta | Descripción |
|------|-------------|
| `/` | Inicio |
| `/productos.php` | Catálogo con filtros y paginación |
| `/producto.php?slug=…` | Detalle de producto |
| `/carrito.php` | Carrito |
| `/checkout.php` | Checkout |
| `/login.php`, `/registro.php` | Autenticación |
| `/perfil.php` | Perfil + historial de pedidos |
| `/contacto.php` | Formulario de contacto |
| `/admin/` | Panel admin |
| `/admin/productos.php` | CRUD de productos |
| `/admin/categorias.php` | CRUD de categorías |
| `/admin/pedidos.php` | Gestión de pedidos |
| `/api/*` | API REST JSON |

---

## 🧪 API REST (resumen)

```
GET    /api/products               # Catálogo paginado
GET    /api/products/{slug}        # Detalle
GET    /api/categories             # Listado
GET    /api/cart                   # Mi carrito
POST   /api/cart/add               # Agregar
PUT    /api/cart/update            # Actualizar cantidad
DELETE /api/cart/remove            # Quitar
DELETE /api/cart/clear             # Vaciar
POST   /api/checkout               # Crear pedido (auth)
GET    /api/orders/mine            # Mis pedidos (auth)
POST   /api/auth/login             # Iniciar sesión
POST   /api/auth/register          # Crear cuenta
POST   /api/auth/logout            # Cerrar sesión
GET    /api/auth/me                # Mi usuario
POST   /api/contact                # Mensaje de contacto

# Admin (rol admin)
GET    /api/admin/products         # CRUD de productos
POST   /api/admin/products
PATCH  /api/admin/products/{id}
DELETE /api/admin/products/{id}
GET    /api/admin/categories
POST   /api/admin/categories
PATCH  /api/admin/categories/{id}
DELETE /api/admin/categories/{id}
GET    /api/admin/orders
PATCH  /api/admin/orders/{id}      # Cambiar estado
```

Todas las respuestas JSON siguen el formato:

```json
{ "ok": true, "data": { … } }
{ "ok": false, "error": "Mensaje legible" }
```

---

## 📈 Mejoras frente al código original

| Aspecto | Antes | Después |
|---------|-------|---------|
| Arquitectura | Archivos sueltos | Core/Services/Repos/Controllers separados |
| Base de datos | MySQL con SQL Injection | Supabase Postgres con RLS |
| Autenticación | Ninguna | Supabase Auth + JWT + roles |
| Carrito | En JS cliente, perdido al recargar | Sesión PHP + Supabase en checkout |
| Pedidos | 1 tabla mal diseñada | orders + order_items + estado + stock |
| Imágenes | `<img src="img/collar.jpg">` hardcoded | Storage de Supabase con CDN |
| Administración | No existía | CRUD completo con permisos |
| SEO | Solo `<title>` | OG, descripción, semántica, responsive |
| Seguridad | Inyección SQL+XSS+CSRF | Sanitización, CSRF, CSP, HSTS |
| UX/UI | 30 líneas de CSS | Sistema de diseño completo + responsive |
| Responsive | No | Mobile-first con breakpoints |
| Accesibilidad | Sin aria | Roles, skip-links, aria-live |

---

## 📜 Licencia

MIT — uso libre con atribución.