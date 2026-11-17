-- Mappealo en Supabase · reportes anónimos
-- Para una base que ya tiene 01, 02 y 03. Agrega la opción y actualiza las funciones.

alter table public.reporte add column if not exists es_anonimo boolean not null default false;

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

create or replace function privado.ocultar_autor(p_reporte jsonb)
returns jsonb language sql immutable as $$
    select case when coalesce((p_reporte->>'es_anonimo')::boolean, false)
                then p_reporte || '{"nombre_usuario": "Anónimo"}'::jsonb
                else p_reporte end
$$;

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

revoke all on function privado.ocultar_autor(jsonb) from public, anon, authenticated;
