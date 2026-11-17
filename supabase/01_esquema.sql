-- Mappealo en Supabase · 1 de 3: tablas
-- Pegar en Supabase > SQL Editor y ejecutar. Después correr 02_funciones.sql y 03_admin.sql.

create schema if not exists privado;
revoke all on schema privado from public, anon, authenticated;

-- Usuarios: el correo y la contraseña los guarda Supabase Auth (auth.users).
-- Esta tabla solo tiene los datos propios de la app. Nadie la puede modificar desde el navegador.
create table public.usuario (
    id_usuario uuid primary key references auth.users (id) on delete cascade,
    nombre_usuario text not null check (char_length(nombre_usuario) between 1 and 100),
    es_invitado boolean not null default false,
    es_admin boolean not null default false,
    fecha_registro timestamptz not null default now()
);

create table public.tipo_robo (
    id_tipo_robo integer generated always as identity primary key,
    nombre text not null unique,
    peso_mapa numeric(4, 2) not null default 1.00
);

create table public.tipo_incidente (
    id_tipo_incidente integer generated always as identity primary key,
    nombre text not null unique,
    descripcion text
);

create table public.ubicacion (
    id_ubicacion bigint generated always as identity primary key,
    latitud numeric(10, 8) not null check (latitud between -90 and 90),
    longitud numeric(11, 8) not null check (longitud between -180 and 180),
    direccion text not null check (char_length(direccion) <= 255)
);

create table public.reporte (
    id_reporte bigint generated always as identity primary key,
    id_usuario uuid not null references public.usuario (id_usuario) on delete cascade,
    id_ubicacion bigint not null references public.ubicacion (id_ubicacion),
    fecha_reporte timestamptz not null default now(),
    fecha_incidente timestamptz not null,
    descripcion text not null check (char_length(descripcion) between 1 and 1000),
    estado text not null default 'pendiente' check (estado in ('pendiente', 'verificado', 'rechazado')),
    -- Si es anónimo, el nombre del autor no se muestra al público (los administradores sí lo ven)
    es_anonimo boolean not null default false
);
create index on public.reporte (id_usuario, fecha_reporte);

create table public.robo (
    id_reporte bigint primary key references public.reporte (id_reporte) on delete cascade,
    id_tipo_robo integer not null references public.tipo_robo (id_tipo_robo),
    hubo_violencia boolean not null default false,
    hubo_arma boolean not null default false,
    multiples_delincuentes boolean not null default false
);

create table public.incidente_comunitario (
    id_reporte bigint primary key references public.reporte (id_reporte) on delete cascade,
    id_tipo_incidente integer not null references public.tipo_incidente (id_tipo_incidente)
);

create table public.evidencia (
    id_evidencia bigint generated always as identity primary key,
    id_reporte bigint not null references public.reporte (id_reporte) on delete cascade,
    url_imagen text not null,
    fecha timestamptz not null default now()
);

create table public.comentario (
    id_comentario bigint generated always as identity primary key,
    id_reporte bigint not null references public.reporte (id_reporte) on delete cascade,
    id_usuario uuid not null references public.usuario (id_usuario) on delete cascade,
    contenido text not null check (char_length(contenido) between 2 and 500),
    fecha timestamptz not null default now()
);
create index on public.comentario (id_reporte);

create table public.valoracion (
    id_valoracion bigint generated always as identity primary key,
    id_reporte bigint not null references public.reporte (id_reporte) on delete cascade,
    id_usuario uuid not null references public.usuario (id_usuario) on delete cascade,
    valor smallint not null check (valor between 1 and 5),
    fecha timestamptz not null default now(),
    unique (id_reporte, id_usuario)
);

-- Denuncias de reportes
create table public.advertencia (
    id_advertencia bigint generated always as identity primary key,
    id_reporte bigint not null references public.reporte (id_reporte) on delete cascade,
    id_usuario uuid not null references public.usuario (id_usuario) on delete cascade,
    motivo text not null check (char_length(motivo) <= 255),
    fecha timestamptz not null default now(),
    unique (id_reporte, id_usuario)
);

-- Registro de acciones para limitar abusos (cantidad por usuario en una ventana de tiempo)
create table privado.limite_accion (
    id bigint generated always as identity primary key,
    id_usuario uuid not null,
    accion text not null,
    momento timestamptz not null default now()
);
create index on privado.limite_accion (id_usuario, accion, momento);
alter table privado.limite_accion enable row level security;

-- Row Level Security: activada en todas las tablas.
-- Solo los tipos se leen directo; todo lo demás pasa por las funciones de 02_funciones.sql,
-- que controlan quién puede ver o modificar cada cosa.
alter table public.usuario enable row level security;
alter table public.tipo_robo enable row level security;
alter table public.tipo_incidente enable row level security;
alter table public.ubicacion enable row level security;
alter table public.reporte enable row level security;
alter table public.robo enable row level security;
alter table public.incidente_comunitario enable row level security;
alter table public.evidencia enable row level security;
alter table public.comentario enable row level security;
alter table public.valoracion enable row level security;
alter table public.advertencia enable row level security;

revoke all on all tables in schema public from anon, authenticated;
grant select on public.tipo_robo, public.tipo_incidente to authenticated;

create policy "Tipos de robo visibles con sesión" on public.tipo_robo for select to authenticated using (true);
create policy "Tipos de incidente visibles con sesión" on public.tipo_incidente for select to authenticated using (true);

-- Datos fijos
insert into public.tipo_robo (nombre, peso_mapa) values
    ('Robo a mano armada', 3.00),
    ('Hurto / Arrebato en vía pública', 1.50),
    ('Robo de vehículo / Motochorros', 2.50);

insert into public.tipo_incidente (nombre, descripcion) values
    ('Luminaria rota', 'Falta total o parcial de iluminación en la vía pública'),
    ('Bache / Calle anegada', 'Pozo peligroso o calle intransitable'),
    ('Basura acumulada', 'Microbasural o residuos que obstruyen la vereda');

-- Cada cuenta nueva de Supabase Auth (registrada o invitada) crea su fila en usuario.
-- El nombre público sale del correo y nunca es administrador.
create or replace function privado.crear_usuario()
returns trigger
language plpgsql
security definer
set search_path = public, pg_temp
as $$
begin
    insert into public.usuario (id_usuario, nombre_usuario, es_invitado)
    values (
        new.id,
        case
            when new.is_anonymous then 'Invitado'
            else coalesce(nullif(left(regexp_replace(split_part(new.email, '@', 1), '[^A-Za-z0-9._-]', '', 'g'), 100), ''), 'usuario')
        end,
        coalesce(new.is_anonymous, false)
    );
    return new;
end;
$$;

create trigger al_crear_cuenta
    after insert on auth.users
    for each row execute function privado.crear_usuario();

grant usage on schema privado to supabase_auth_admin;
grant execute on function privado.crear_usuario() to supabase_auth_admin;
