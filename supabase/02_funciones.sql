-- Mappealo en Supabase · 2 de 3: funciones que usa la app
-- Todas corren con security definer y controlan adentro quién las llama.

-- ---------------------------------------------------------------------------
-- Ayudantes (esquema privado: no se pueden llamar desde el navegador)
-- ---------------------------------------------------------------------------

create or replace function privado.fecha_local(t timestamptz)
returns text language sql immutable as $$
    select to_char(t at time zone 'America/Argentina/Buenos_Aires', 'YYYY-MM-DD HH24:MI:SS')
$$;

-- Usuario registrado (no invitado) que hace la llamada, o error
create or replace function privado.requerir_registrado()
returns uuid language plpgsql stable security definer set search_path = public, pg_temp as $$
declare
    v_id uuid := auth.uid();
begin
    if v_id is null or not exists (select 1 from usuario where id_usuario = v_id and not es_invitado) then
        raise exception 'Tenés que iniciar sesión con una cuenta registrada para hacer esto.' using errcode = 'P0001';
    end if;
    return v_id;
end;
$$;

create or replace function privado.es_admin()
returns boolean language sql stable security definer set search_path = public, pg_temp as $$
    select exists (select 1 from usuario where id_usuario = auth.uid() and es_admin and not es_invitado)
$$;

-- Corta si el usuario hizo la acción demasiadas veces en la ventana de tiempo
create or replace function privado.controlar_limite(p_accion text, p_maximo int, p_segundos int)
returns void language plpgsql security definer set search_path = public, pg_temp as $$
begin
    if (select count(*) from privado.limite_accion
        where id_usuario = auth.uid() and accion = p_accion
          and momento > now() - make_interval(secs => p_segundos)) >= p_maximo then
        raise exception 'Hiciste muchas acciones seguidas. Esperá unos minutos y volvé a intentar.' using errcode = 'P0001';
    end if;

    insert into privado.limite_accion (id_usuario, accion) values (auth.uid(), p_accion);
    delete from privado.limite_accion where momento < now() - interval '1 day' and random() < 0.02;
end;
$$;

-- ---------------------------------------------------------------------------
-- Índice de credibilidad (misma lógica que tenía config/credibilidad.php)
-- ---------------------------------------------------------------------------

-- Calificaciones y denuncias de cada reporte, sin contar las del propio autor
create or replace view privado.estadistica_reporte as
select
    r.id_reporte,
    r.id_usuario,
    r.fecha_reporte,
    u.es_invitado,
    coalesce((select sum(v.valor) from valoracion v where v.id_reporte = r.id_reporte and v.id_usuario <> r.id_usuario), 0)::numeric as suma,
    (select count(*) from valoracion v where v.id_reporte = r.id_reporte and v.id_usuario <> r.id_usuario)::int as calificaciones,
    (select count(distinct a.id_usuario) from advertencia a where a.id_reporte = r.id_reporte and a.id_usuario <> r.id_usuario)::int as denuncias
from reporte r
join usuario u on u.id_usuario = r.id_usuario;

create or replace function privado.cred_aporte(p_suma numeric, p_cantidad int, p_peso int, p_muestra int)
returns int language sql immutable as $$
    select case when p_cantidad = 0 then 0
           else round((p_suma / p_cantidad - 3) * p_peso * least(1, p_cantidad::numeric / p_muestra))::int end
$$;

create or replace function privado.cred_numero(p numeric)
returns text language sql immutable as $$
    select replace(trim_scale(round(p, 1))::text, '.', ',')
$$;

create or replace function privado.cred_resultado(p_factores jsonb)
returns jsonb language sql immutable as $$
    with total as (
        select greatest(0, least(100, round(coalesce(sum((f->>'puntos')::numeric), 0))))::int as puntaje
        from jsonb_array_elements(p_factores) f
    )
    select jsonb_build_object(
        'puntaje', puntaje,
        'nivel', case when puntaje >= 70 then '{"clave":"alta","texto":"Alta"}'::jsonb
                      when puntaje >= 40 then '{"clave":"media","texto":"Media"}'::jsonb
                      else '{"clave":"baja","texto":"Baja"}'::jsonb end,
        'factores', coalesce((select jsonb_agg(f) from jsonb_array_elements(p_factores) f
                              where (f->>'puntos')::numeric <> 0 or (f->>'base')::boolean), '[]'::jsonb)
    )
    from total
$$;

-- Factores del autor: base según tipo de cuenta, calificaciones y denuncias de sus reportes
create or replace function privado.cred_factores_autor(p_usuario uuid, p_excluir bigint)
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
declare
    v_invitado boolean;
    v_suma numeric;
    v_cant int;
    v_den int;
    v_donde text := case when p_excluir is null then 'sus reportes' else 'sus otros reportes' end;
    v_factores jsonb;
begin
    select es_invitado into v_invitado from usuario where id_usuario = p_usuario;

    if coalesce(v_invitado, true) then
        return jsonb_build_array(jsonb_build_object('texto', 'Reporte de invitado (anónimo)', 'puntos', 25, 'base', true));
    end if;

    select coalesce(sum(suma), 0), coalesce(sum(calificaciones), 0), coalesce(sum(denuncias), 0)
      into v_suma, v_cant, v_den
      from privado.estadistica_reporte
     where id_usuario = p_usuario and id_reporte is distinct from p_excluir;

    v_factores := jsonb_build_array(jsonb_build_object('texto', 'Usuario registrado', 'puntos', 50, 'base', true));

    if v_cant > 0 then
        v_factores := v_factores || jsonb_build_object(
            'texto', format('Calificaciones de %s: %s ★ (%s)', v_donde, privado.cred_numero(v_suma / v_cant), v_cant),
            'puntos', privado.cred_aporte(v_suma, v_cant, 10, 5));
    end if;

    if v_den > 0 then
        v_factores := v_factores || jsonb_build_object(
            'texto', format('Denuncias recibidas en %s: %s', v_donde, v_den),
            'puntos', -least(40, v_den * 10));
    end if;

    return v_factores;
end;
$$;

-- Penalidad por muchos reportes en 24 h (más de 3)
create or replace function privado.cred_rafaga(p_usuario uuid, p_momento timestamptz, p_excluir bigint)
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
declare
    v_cant int;
begin
    if (select es_invitado from usuario where id_usuario = p_usuario) is not false then
        return '[]'::jsonb;
    end if;

    select count(*) into v_cant from reporte
     where id_usuario = p_usuario and id_reporte is distinct from p_excluir
       and fecha_reporte between p_momento - interval '24 hours' and p_momento;

    if v_cant - 3 <= 0 then
        return '[]'::jsonb;
    end if;

    return jsonb_build_array(jsonb_build_object(
        'texto', format('Muchos reportes en poco tiempo: %s en 24 h (límite 3)', v_cant),
        'puntos', -least(24, (v_cant - 3) * 8)));
end;
$$;

create or replace function privado.credibilidad_reporte(p_id bigint)
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
declare
    e privado.estadistica_reporte;
    v_factores jsonb;
begin
    select * into e from privado.estadistica_reporte where id_reporte = p_id;
    if not found then
        return null;
    end if;

    v_factores := privado.cred_factores_autor(e.id_usuario, e.id_reporte)
               || privado.cred_rafaga(e.id_usuario, e.fecha_reporte - interval '1 second', e.id_reporte);

    if e.calificaciones > 0 then
        v_factores := v_factores || jsonb_build_object(
            'texto', format('Calificaciones de este reporte: %s ★ (%s)', privado.cred_numero(e.suma / e.calificaciones), e.calificaciones),
            'puntos', privado.cred_aporte(e.suma, e.calificaciones, 12, 3));
    end if;

    if e.denuncias > 0 then
        v_factores := v_factores || jsonb_build_object(
            'texto', format('Denuncias de este reporte: %s', e.denuncias),
            'puntos', -least(60, e.denuncias * 15));
    end if;

    return privado.cred_resultado(v_factores);
end;
$$;

create or replace function privado.credibilidad_usuario(p_usuario uuid)
returns jsonb language sql stable security definer set search_path = public, pg_temp as $$
    select privado.cred_resultado(privado.cred_factores_autor(p_usuario, null) || privado.cred_rafaga(p_usuario, now(), null))
$$;

-- Datos de un reporte con el formato que usa el mapa
create or replace function privado.reporte_json(p_id bigint)
returns jsonb language sql stable security definer set search_path = public, pg_temp as $$
    select jsonb_build_object(
        'id_reporte', r.id_reporte,
        'id_usuario', r.id_usuario,
        'descripcion', r.descripcion,
        'fecha_incidente', privado.fecha_local(r.fecha_incidente),
        'fecha_reporte', privado.fecha_local(r.fecha_reporte),
        'estado', r.estado,
        'nombre_usuario', u.nombre_usuario,
        'es_anonimo', r.es_anonimo,
        'latitud', ub.latitud,
        'longitud', ub.longitud,
        'direccion', ub.direccion,
        'categoria', case when rb.id_reporte is not null then 'delito' when ic.id_reporte is not null then 'comunitario' else 'otro' end,
        'tipo_robo', tr.nombre,
        'tipo_incidente', ti.nombre,
        'tipo', coalesce(tr.nombre, ti.nombre),
        'hubo_violencia', rb.hubo_violencia,
        'hubo_arma', rb.hubo_arma,
        'multiples_delincuentes', rb.multiples_delincuentes,
        'promedio_calificacion', (select round(avg(v.valor), 1) from valoracion v where v.id_reporte = r.id_reporte),
        'cantidad_calificaciones', (select count(*) from valoracion v where v.id_reporte = r.id_reporte),
        'cantidad_comentarios', (select count(*) from comentario c where c.id_reporte = r.id_reporte),
        'cantidad_denuncias', (select count(*) from advertencia a where a.id_reporte = r.id_reporte)
    )
    from reporte r
    join usuario u on u.id_usuario = r.id_usuario
    join ubicacion ub on ub.id_ubicacion = r.id_ubicacion
    left join robo rb on rb.id_reporte = r.id_reporte
    left join tipo_robo tr on tr.id_tipo_robo = rb.id_tipo_robo
    left join incidente_comunitario ic on ic.id_reporte = r.id_reporte
    left join tipo_incidente ti on ti.id_tipo_incidente = ic.id_tipo_incidente
    where r.id_reporte = p_id
$$;

create or replace function privado.con_credibilidad(p_reporte jsonb)
returns jsonb language sql stable as $$
    select p_reporte || jsonb_build_object(
        'credibilidad', (c->>'puntaje')::int,
        'credibilidad_nivel', c->'nivel'->>'clave')
    from (select privado.credibilidad_reporte((p_reporte->>'id_reporte')::bigint) as c) x
$$;

-- En los reportes anónimos, el público ve "Anónimo" en lugar del nombre del autor
create or replace function privado.ocultar_autor(p_reporte jsonb)
returns jsonb language sql immutable as $$
    select case when coalesce((p_reporte->>'es_anonimo')::boolean, false)
                then p_reporte || '{"nombre_usuario": "Anónimo"}'::jsonb
                else p_reporte end
$$;

-- Borra un reporte y su ubicación si nadie más la usa
create or replace function privado.borrar_reporte(p_id bigint)
returns boolean language plpgsql security definer set search_path = public, pg_temp as $$
declare
    v_ubicacion bigint;
begin
    delete from reporte where id_reporte = p_id returning id_ubicacion into v_ubicacion;
    if v_ubicacion is null then
        return false;
    end if;
    if not exists (select 1 from reporte where id_ubicacion = v_ubicacion) then
        delete from ubicacion where id_ubicacion = v_ubicacion;
    end if;
    return true;
end;
$$;

-- ---------------------------------------------------------------------------
-- Funciones públicas (las llama la app con supabase.rpc)
-- ---------------------------------------------------------------------------

-- Mi cuenta: nombre, rol y credibilidad
create or replace function public.mi_perfil()
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
declare
    u usuario;
begin
    select * into u from usuario where id_usuario = auth.uid();
    if not found then
        raise exception 'Sesión no iniciada.' using errcode = 'P0001';
    end if;
    return jsonb_build_object(
        'nombre_usuario', u.nombre_usuario,
        'es_invitado', u.es_invitado,
        'es_admin', u.es_admin and not u.es_invitado,
        'credibilidad', case when u.es_invitado then null else privado.credibilidad_usuario(u.id_usuario) end);
end;
$$;

-- Reportes del mapa (sin los rechazados)
create or replace function public.reportes_mapa()
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
begin
    if auth.uid() is null then
        raise exception 'Sesión no iniciada.' using errcode = 'P0001';
    end if;
    return coalesce((
        select jsonb_agg(privado.ocultar_autor(privado.con_credibilidad(privado.reporte_json(r.id_reporte))) - 'id_usuario'
                         order by r.fecha_reporte desc, r.id_reporte desc)
        from reporte r
        where r.estado <> 'rechazado'
    ), '[]'::jsonb);
end;
$$;

-- Detalle de un reporte: calificaciones, comentarios, credibilidad y lo que hizo quien mira
create or replace function public.reporte_detalle(p_id bigint)
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
declare
    v_yo uuid := auth.uid();
    v_registrado boolean;
    v_reporte jsonb;
begin
    if v_yo is null then
        raise exception 'Sesión no iniciada.' using errcode = 'P0001';
    end if;

    v_registrado := exists (select 1 from usuario where id_usuario = v_yo and not es_invitado);

    if not exists (select 1 from reporte where id_reporte = p_id
                   and (estado <> 'rechazado' or id_usuario = v_yo or privado.es_admin())) then
        raise exception 'El reporte no existe o fue eliminado.' using errcode = 'P0001';
    end if;

    v_reporte := privado.reporte_json(p_id);

    return jsonb_build_object(
        'reporte', privado.ocultar_autor(v_reporte) - 'id_usuario',
        'calificacion', jsonb_build_object(
            'promedio', v_reporte->'promedio_calificacion',
            'cantidad', v_reporte->'cantidad_calificaciones',
            'mia', (select valor from valoracion where id_reporte = p_id and id_usuario = v_yo)),
        'comentarios', coalesce((
            select jsonb_agg(jsonb_build_object(
                       'id_comentario', c.id_comentario,
                       'contenido', c.contenido,
                       'fecha', privado.fecha_local(c.fecha),
                       'nombre_usuario', u.nombre_usuario) order by c.fecha, c.id_comentario)
            from comentario c join usuario u on u.id_usuario = c.id_usuario
            where c.id_reporte = p_id), '[]'::jsonb),
        'credibilidad', privado.credibilidad_reporte(p_id),
        'ya_denuncie', exists (select 1 from advertencia where id_reporte = p_id and id_usuario = v_yo),
        'puede_interactuar', v_registrado,
        'es_mio', (v_reporte->>'id_usuario')::uuid = v_yo
    );
end;
$$;

-- Historial "Tus reportes"
create or replace function public.mis_reportes()
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
declare
    v_yo uuid := privado.requerir_registrado();
    v_datos jsonb;
begin
    select coalesce(jsonb_agg(privado.con_credibilidad(privado.reporte_json(r.id_reporte)) - 'id_usuario'
                              order by r.fecha_reporte desc, r.id_reporte desc), '[]'::jsonb)
      into v_datos
      from reporte r where r.id_usuario = v_yo;

    return jsonb_build_object(
        'total', jsonb_array_length(v_datos),
        'mi_credibilidad', privado.credibilidad_usuario(v_yo),
        'data', v_datos);
end;
$$;

-- Publicar un reporte (registrados e invitados). Valida todo y guarda en una sola transacción.
create or replace function public.crear_reporte(p jsonb)
returns jsonb language plpgsql security definer set search_path = public, pg_temp as $$
declare
    v_yo uuid := auth.uid();
    v_es_delito boolean := coalesce(p->>'categoria', '') = 'delito' or coalesce(p->>'tipo', '') = 'delito';
    v_lat numeric;
    v_lng numeric;
    v_desc text;
    v_dir text;
    v_fecha timestamptz;
    v_tipo int;
    v_ubicacion bigint;
    v_reporte bigint;
    v_grav jsonb := case when jsonb_typeof(p->'gravedad') = 'object' then p->'gravedad' else '{}'::jsonb end;
begin
    if v_yo is null or not exists (select 1 from usuario where id_usuario = v_yo) then
        raise exception 'Debés iniciar sesión para publicar un reporte.' using errcode = 'P0001';
    end if;

    begin
        v_lat := (p->>'latitud')::numeric;
        v_lng := (p->>'longitud')::numeric;
    exception when others then
        v_lat := null;
    end;
    if v_lat is null or v_lng is null or v_lat not between -90 and 90 or v_lng not between -180 and 180 then
        raise exception 'La ubicación del reporte no es válida.' using errcode = 'P0001';
    end if;

    v_desc := btrim(regexp_replace(coalesce(p->>'descripcion', ''), '[\x00-\x09\x0B-\x1F\x7F]', '', 'g'));
    if char_length(v_desc) > 1000 then
        raise exception 'La descripción puede tener hasta 1000 caracteres.' using errcode = 'P0001';
    end if;
    if v_desc = '' then
        v_desc := 'Sin descripción';
    end if;

    if coalesce(p->>'fecha_hora', '') <> '' then
        begin
            -- Si la fecha llega sin zona horaria, se toma como hora de Argentina
            if (p->>'fecha_hora') ~ '([zZ]|[+-]\d{2}(:?\d{2})?)$' then
                v_fecha := (p->>'fecha_hora')::timestamptz;
            else
                v_fecha := (p->>'fecha_hora')::timestamp at time zone 'America/Argentina/Buenos_Aires';
            end if;
        exception when others then
            raise exception 'La fecha del incidente no es válida.' using errcode = 'P0001';
        end;
        if v_fecha > now() + interval '5 minutes' or v_fecha < now() - interval '1 year' then
            raise exception 'La fecha del incidente tiene que ser del último año y no puede ser futura.' using errcode = 'P0001';
        end if;
    elsif v_es_delito then
        raise exception 'Indicá la fecha y hora del delito.' using errcode = 'P0001';
    else
        v_fecha := now();
    end if;

    if v_es_delito then
        select id_tipo_robo into v_tipo from tipo_robo where nombre = p->>'tipo_robo';
    else
        select id_tipo_incidente into v_tipo from tipo_incidente where nombre = p->>'incidente';
    end if;
    if v_tipo is null then
        raise exception 'Elegí un tipo de reporte válido.' using errcode = 'P0001';
    end if;

    -- Máximo 5 reportes cada 10 minutos por cuenta
    if (select count(*) from reporte where id_usuario = v_yo and fecha_reporte > now() - interval '10 minutes') >= 5 then
        raise exception 'Publicaste muchos reportes seguidos. Esperá unos minutos.' using errcode = 'P0001';
    end if;

    v_dir := left(btrim(regexp_replace(coalesce(p->'ubicacion'->>'direccion', ''), '\s+', ' ', 'g')), 120);
    if v_dir = '' then
        v_dir := 'Ubicación seleccionada en mapa';
    end if;

    insert into ubicacion (latitud, longitud, direccion) values (v_lat, v_lng, v_dir)
    returning id_ubicacion into v_ubicacion;

    insert into reporte (id_usuario, id_ubicacion, fecha_incidente, descripcion, es_anonimo)
    values (v_yo, v_ubicacion, v_fecha, v_desc, coalesce((p->>'anonimo')::boolean, false))
    returning id_reporte into v_reporte;

    if v_es_delito then
        insert into robo (id_reporte, id_tipo_robo, hubo_violencia, hubo_arma, multiples_delincuentes)
        values (v_reporte, v_tipo,
                coalesce((v_grav->>'violencia')::boolean, false),
                coalesce((v_grav->>'arma_fuego')::boolean, false),
                coalesce((v_grav->>'multiples_delincuentes')::boolean, false));
    else
        insert into incidente_comunitario (id_reporte, id_tipo_incidente) values (v_reporte, v_tipo);
    end if;

    return jsonb_build_object('id_reporte', v_reporte, 'message', 'Reporte guardado con éxito');
end;
$$;

create or replace function public.comentar(p_id bigint, p_contenido text)
returns jsonb language plpgsql security definer set search_path = public, pg_temp as $$
declare
    v_yo uuid := privado.requerir_registrado();
    v_texto text := btrim(coalesce(p_contenido, ''));
begin
    if char_length(v_texto) not between 2 and 500 then
        raise exception 'El comentario debe tener entre 2 y 500 caracteres.' using errcode = 'P0001';
    end if;
    if not exists (select 1 from reporte where id_reporte = p_id and (estado <> 'rechazado' or id_usuario = v_yo)) then
        raise exception 'El reporte no existe o fue eliminado.' using errcode = 'P0001';
    end if;
    perform privado.controlar_limite('comentario', 10, 300);

    insert into comentario (id_reporte, id_usuario, contenido) values (p_id, v_yo, v_texto);
    return jsonb_build_object('message', 'Comentario publicado.');
end;
$$;

create or replace function public.calificar(p_id bigint, p_valor int)
returns jsonb language plpgsql security definer set search_path = public, pg_temp as $$
declare
    v_yo uuid := privado.requerir_registrado();
    v_existia boolean;
begin
    if p_valor is null or p_valor not between 1 and 5 then
        raise exception 'La calificación debe ser de 1 a 5 estrellas.' using errcode = 'P0001';
    end if;
    if not exists (select 1 from reporte where id_reporte = p_id and (estado <> 'rechazado' or id_usuario = v_yo)) then
        raise exception 'El reporte no existe o fue eliminado.' using errcode = 'P0001';
    end if;
    perform privado.controlar_limite('calificacion', 30, 300);

    v_existia := exists (select 1 from valoracion where id_reporte = p_id and id_usuario = v_yo);
    insert into valoracion (id_reporte, id_usuario, valor) values (p_id, v_yo, p_valor)
    on conflict (id_reporte, id_usuario) do update set valor = excluded.valor, fecha = now();

    return jsonb_build_object(
        'message', case when v_existia then 'Calificación actualizada.' else 'Gracias por calificar.' end,
        'calificacion', (select jsonb_build_object('promedio', round(avg(valor), 1), 'cantidad', count(*), 'mia', p_valor)
                         from valoracion where id_reporte = p_id));
end;
$$;

create or replace function public.denunciar(p_id bigint, p_motivo text, p_detalle text default '')
returns jsonb language plpgsql security definer set search_path = public, pg_temp as $$
declare
    v_yo uuid := privado.requerir_registrado();
    v_detalle text := btrim(coalesce(p_detalle, ''));
begin
    if p_motivo is null or p_motivo not in ('Información falsa', 'Contenido ofensivo o discriminatorio', 'Datos personales expuestos',
                                            'Spam o publicidad', 'Reporte duplicado', 'Otro') then
        raise exception 'Elegí un motivo para la denuncia.' using errcode = 'P0001';
    end if;
    if p_motivo = 'Otro' and char_length(v_detalle) < 5 then
        raise exception 'Contanos brevemente el motivo de la denuncia.' using errcode = 'P0001';
    end if;
    if not exists (select 1 from reporte where id_reporte = p_id and estado <> 'rechazado') then
        raise exception 'El reporte no existe o fue eliminado.' using errcode = 'P0001';
    end if;
    if exists (select 1 from advertencia where id_reporte = p_id and id_usuario = v_yo) then
        raise exception 'Ya denunciaste este reporte. Un administrador lo va a revisar.' using errcode = 'P0001';
    end if;
    perform privado.controlar_limite('denuncia', 10, 3600);

    insert into advertencia (id_reporte, id_usuario, motivo)
    values (p_id, v_yo, left(case when v_detalle <> '' then p_motivo || ': ' || v_detalle else p_motivo end, 255));

    return jsonb_build_object('message', 'Denuncia enviada. Un administrador la va a revisar.');
end;
$$;

-- El autor borra su propio reporte
create or replace function public.borrar_mi_reporte(p_id bigint)
returns jsonb language plpgsql security definer set search_path = public, pg_temp as $$
declare
    v_yo uuid := privado.requerir_registrado();
begin
    perform privado.controlar_limite('borrar_reporte', 20, 600);

    if not exists (select 1 from reporte where id_reporte = p_id and id_usuario = v_yo) then
        raise exception 'El reporte no existe o no es tuyo.' using errcode = 'P0001';
    end if;

    perform privado.borrar_reporte(p_id);
    return jsonb_build_object('message', 'Reporte borrado.');
end;
$$;

-- ---------------------------------------------------------------------------
-- Permisos: solo usuarios con sesión (registrados o invitados) llaman a las funciones públicas
-- ---------------------------------------------------------------------------
revoke all on all functions in schema privado from public, anon, authenticated;
revoke all on all tables in schema privado from public, anon, authenticated;

revoke execute on function public.mi_perfil(), public.reportes_mapa(), public.reporte_detalle(bigint),
    public.mis_reportes(), public.crear_reporte(jsonb), public.comentar(bigint, text),
    public.calificar(bigint, int), public.denunciar(bigint, text, text), public.borrar_mi_reporte(bigint)
    from public, anon;

grant execute on function public.mi_perfil(), public.reportes_mapa(), public.reporte_detalle(bigint),
    public.mis_reportes(), public.crear_reporte(jsonb), public.comentar(bigint, text),
    public.calificar(bigint, int), public.denunciar(bigint, text, text), public.borrar_mi_reporte(bigint)
    to authenticated;
