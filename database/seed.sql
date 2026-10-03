-- =====================================================================
-- Datos de ejemplo (seed) — opcional, ejecutar tras schema.sql y policies.sql
-- =====================================================================

insert into public.categories (slug, name, description, image_url) values
    ('collares', 'Collares', 'Joyería artesanal en semillas y fibras naturales.', null),
    ('bolsos', 'Bolsos', 'Bolsos tejidos a mano con fibras de fique y algodón.', null),
    ('ceramica', 'Cerámica', 'Piezas únicas en arcilla cocida y esmaltada.', null)
on conflict (slug) do nothing;

insert into public.products (category_id, slug, name, short_description, description, price, stock, featured, status, image_url)
select c.id, 'collar-semillas-cauca', 'Collar de semillas del Cauca', 'Elaborado con semillas naturales recolectadas en Popayán.', 'Cada cuenta está seleccionada a mano y ensartada con hilo encerado. Pieza única que cambia con el uso.', 25000, 12, true, 'active', null
from public.categories c where c.slug = 'collares'
on conflict (slug) do nothing;

insert into public.products (category_id, slug, name, short_description, description, price, stock, featured, status, image_url)
select c.id, 'bolso-fique-natural', 'Bolso en fique natural', 'Tejido en fique por artesanas del Cauca.', 'Bolso resistente y ligero con cierre en cuero.', 80000, 8, true, 'active', null
from public.categories c where c.slug = 'bolsos'
on conflict (slug) do nothing;

insert into public.products (category_id, slug, name, short_description, description, price, stock, featured, status, image_url)
select c.id, 'mug-ceramica-raizal', 'Mug de cerámica raizal', 'Hecho en San Andrés por manos raizales.', 'Esmaltado en tonos del Caribe, apto para microondas.', 35000, 20, false, 'active', null
from public.categories c where c.slug = 'ceramica'
on conflict (slug) do nothing;