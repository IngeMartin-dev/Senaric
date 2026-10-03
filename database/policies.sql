-- =====================================================================
-- Row Level Security (RLS) — Supabase
-- Se aplica la seguridad por defecto: nadie lee/escribe sin policy.
-- =====================================================================

-- Habilitar RLS
alter table public.categories      enable row level security;
alter table public.products        enable row level security;
alter table public.orders          enable row level security;
alter table public.order_items     enable row level security;

-- =====================================================================
-- Categorías
-- Lectura pública solo de categorías activas (todas son visibles).
-- Escritura solo administradores (rol definido via JWT claim 'role').
-- =====================================================================
create policy "categories_public_read"
    on public.categories for select
    using (true);

create policy "categories_admin_write"
    on public.categories for all
    using (
        (auth.jwt() ->> 'role') = 'authenticated'
        and coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    )
    with check (
        (auth.jwt() ->> 'role') = 'authenticated'
        and coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

-- =====================================================================
-- Productos
-- Lectura pública solo de productos activos o destacados.
-- Escritura solo admin.
-- =====================================================================
create policy "products_public_read"
    on public.products for select
    using (status = 'active' or featured = true);

create policy "products_admin_read"
    on public.products for select
    using (
        coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

create policy "products_admin_write"
    on public.products for all
    using (
        coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    )
    with check (
        coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

-- =====================================================================
-- Pedidos
-- - Los clientes pueden ver y crear SUS propios pedidos.
-- - Los admins pueden ver y modificar todos.
-- =====================================================================
create policy "orders_owner_read"
    on public.orders for select
    using (auth.uid() = user_id);

create policy "orders_owner_insert"
    on public.orders for insert
    with check (auth.uid() = user_id);

create policy "orders_owner_update"
    on public.orders for update
    using (auth.uid() = user_id and status = 'pending')
    with check (auth.uid() = user_id and status = 'pending');

create policy "orders_admin_all"
    on public.orders for all
    using (
        coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    )
    with check (
        coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

-- Items del pedido: misma política que la cabecera
create policy "order_items_owner_read"
    on public.order_items for select
    using (
        exists (
            select 1 from public.orders o
             where o.id = order_items.order_id
               and o.user_id = auth.uid()
        )
    );

create policy "order_items_owner_insert"
    on public.order_items for insert
    with check (
        exists (
            select 1 from public.orders o
             where o.id = order_items.order_id
               and o.user_id = auth.uid()
        )
    );

create policy "order_items_admin_all"
    on public.order_items for all
    using (
        coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    )
    with check (
        coalesce(auth.jwt() -> 'app_metadata' ->> 'is_admin', 'false') = 'true'
    );

-- =====================================================================
-- Grants al rol anon y authenticated
-- =====================================================================
grant usage on schema public to anon, authenticated;

grant select on public.categories      to anon, authenticated;
grant select on public.products        to anon, authenticated;
grant select on public.orders          to authenticated;
grant insert, update on public.orders to authenticated;
grant select, insert on public.order_items to authenticated;

-- Permitir que admin modifique todo
grant all on public.categories  to authenticated;
grant all on public.products    to authenticated;
grant all on public.orders      to authenticated;
grant all on public.order_items to authenticated;

-- Secuencias (necesarias para inserts desde cliente si se permite)
grant usage on all sequences in schema public to authenticated;