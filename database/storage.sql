-- =====================================================================
-- Storage — Buckets para imágenes de productos y categorías
-- Ejecutar tras crear los buckets en el panel de Supabase o con la API.
-- Los buckets deben ser públicos para lectura y restringidos para escritura.
-- =====================================================================

-- Crear buckets vía SQL no es posible directamente; usar Dashboard:
--   1. Storage → New bucket → "products" (Public: ON)
--   2. Storage → New bucket → "categories" (Public: ON)
--
-- Estas policies se aplican después:

-- Productos
create policy "products_bucket_read"
    on storage.objects for select
    using (bucket_id = 'products');

create policy "products_bucket_admin_write"
    on storage.objects for insert
    with check (
        bucket_id = 'products'
        and coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

create policy "products_bucket_admin_update"
    on storage.objects for update
    using (
        bucket_id = 'products'
        and coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

create policy "products_bucket_admin_delete"
    on storage.objects for delete
    using (
        bucket_id = 'products'
        and coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

-- Categorías
create policy "categories_bucket_read"
    on storage.objects for select
    using (bucket_id = 'categories');

create policy "categories_bucket_admin_write"
    on storage.objects for insert
    with check (
        bucket_id = 'categories'
        and coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

create policy "categories_bucket_admin_update"
    on storage.objects for update
    using (
        bucket_id = 'categories'
        and coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

create policy "categories_bucket_admin_delete"
    on storage.objects for delete
    using (
        bucket_id = 'categories'
        and coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

-- =====================================================================
-- Notas de configuración
-- =====================================================================
-- 1. Habilitar la extensión pg_trgm en Supabase (Database → Extensions)
--    create extension if not exists pg_trgm;
--
-- 2. Crear el primer admin manualmente desde SQL Editor:
--    -- Tras crear el usuario en Authentication → Users:
--    update auth.users
--       set raw_app_meta_data = raw_app_meta_data || '{"is_admin": true}'::jsonb
--     where email = 'admin@tu-dominio.com';
--
-- 3. Configurar el redirect URL en Authentication → URL Configuration:
--    Site URL: https://tu-dominio.com
--    Redirect URLs: https://tu-dominio.com/**, http://localhost:8000/**