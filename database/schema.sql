-- =====================================================================
-- Tienda de Artesanías — Esquema PostgreSQL (Supabase)
-- Ejecutar en SQL editor de Supabase con rol "postgres".
-- =====================================================================

-- Extensiones necesarias
create extension if not exists pg_trgm;

-- Limpieza idempotente (comentar si ya hay datos en producción)
-- drop table if exists order_items cascade;
-- drop table if exists orders cascade;
-- drop table if exists products cascade;
-- drop table if exists categories cascade;
-- drop function if exists decrement_stock(integer, integer);
-- drop type if exists order_status;
-- drop type if exists product_status;

-- ---------------------------------------------------------------------
-- Tipos enumerados
-- ---------------------------------------------------------------------
create type order_status as enum (
    'pending',
    'confirmed',
    'shipped',
    'delivered',
    'cancelled'
);

create type product_status as enum (
    'active',
    'draft',
    'archived'
);

-- ---------------------------------------------------------------------
-- Categorías
-- ---------------------------------------------------------------------
create table public.categories (
    id            bigserial primary key,
    slug          text not null unique,
    name          text not null,
    description   text,
    image_url     text,
    created_at    timestamptz not null default now(),
    updated_at    timestamptz not null default now()
);
create index categories_slug_idx on public.categories (slug);

-- ---------------------------------------------------------------------
-- Productos
-- ---------------------------------------------------------------------
create table public.products (
    id                bigserial primary key,
    category_id       bigint references public.categories (id) on delete set null,
    slug              text not null unique,
    name              text not null,
    short_description text,
    description       text,
    price             numeric(12, 2) not null default 0 check (price >= 0),
    compare_price     numeric(12, 2) check (compare_price >= 0),
    stock             integer not null default 0 check (stock >= 0),
    featured          boolean not null default false,
    status            product_status not null default 'active',
    image_url         text,
    gallery           jsonb default '[]'::jsonb,
    created_at        timestamptz not null default now(),
    updated_at        timestamptz not null default now()
);
create index products_category_id_idx on public.products (category_id);
create index products_status_idx     on public.products (status);
create index products_featured_idx   on public.products (featured) where featured = true;
create index products_slug_idx       on public.products (slug);
-- Búsqueda por nombre en español (lower + unaccent si la extensión está habilitada)
create index products_name_trgm_idx  on public.products using gin (name gin_trgm_ops);

-- ---------------------------------------------------------------------
-- Pedidos
-- ---------------------------------------------------------------------
create table public.orders (
    id              bigserial primary key,
    user_id         uuid references auth.users (id) on delete set null,
    customer_email  text not null,
    full_name       text not null,
    phone           text not null,
    address         text not null,
    city            text not null,
    department      text not null,
    notes           text,
    subtotal        numeric(12, 2) not null default 0,
    shipping        numeric(12, 2) not null default 0,
    total           numeric(12, 2) not null default 0,
    currency        text not null default 'COP',
    status          order_status not null default 'pending',
    payment_method  text not null default 'cod',
    created_at      timestamptz not null default now(),
    updated_at      timestamptz not null default now()
);
create index orders_user_id_idx  on public.orders (user_id);
create index orders_status_idx   on public.orders (status);
create index orders_created_idx  on public.orders (created_at desc);

-- ---------------------------------------------------------------------
-- Items del pedido
-- ---------------------------------------------------------------------
create table public.order_items (
    id           bigserial primary key,
    order_id     bigint not null references public.orders (id) on delete cascade,
    product_id   bigint references public.products (id) on delete set null,
    product_name text not null,
    unit_price   numeric(12, 2) not null check (unit_price >= 0),
    quantity     integer not null check (quantity > 0),
    subtotal     numeric(12, 2) not null check (subtotal >= 0)
);
create index order_items_order_id_idx   on public.order_items (order_id);
create index order_items_product_id_idx on public.order_items (product_id);

-- ---------------------------------------------------------------------
-- Función: decrementar stock de forma atómica
-- ---------------------------------------------------------------------
create or replace function public.decrement_stock(p_id bigint, p_qty integer)
returns void
language plpgsql
security definer
set search_path = public
as $$
begin
    update public.products
       set stock = greatest(0, stock - p_qty),
           updated_at = now()
     where id = p_id;
end;
$$;

-- ---------------------------------------------------------------------
-- Trigger: updated_at automático
-- ---------------------------------------------------------------------
create or replace function public.touch_updated_at()
returns trigger language plpgsql as $$
begin
    new.updated_at := now();
    return new;
end;
$$;

create trigger products_touch_updated
    before update on public.products
    for each row execute function public.touch_updated_at();

create trigger categories_touch_updated
    before update on public.categories
    for each row execute function public.touch_updated_at();

create trigger orders_touch_updated
    before update on public.orders
    for each row execute function public.touch_updated_at();