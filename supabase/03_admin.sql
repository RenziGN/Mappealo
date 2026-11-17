-- Mappealo en Supabase · 3 de 3: funciones del panel de administración
-- Todas verifican que quien llama sea administrador.

create or replace function privado.requerir_admin()
returns uuid language plpgsql stable security definer set search_path = public, pg_temp as $$
begin
    if not privado.es_admin() then
        raise exception 'Acceso denegado. Solo los administradores pueden hacer esto.' using errcode = 'P0001';
    end if;
    return auth.uid();
end;
$$;

-- Cantidad de reportes con denuncias (para la pestaña Denuncias)
create or replace function public.admin_resumen()
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
begin
    perform privado.requerir_admin();
    return jsonb_build_object('denunciados', (select count(distinct id_reporte) from advertencia));
end;
$$;

-- Todos los reportes, incluidos los rechazados, con credibilidad y cantidad de denuncias
create or replace function public.admin_reportes()
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
begin
    perform privado.requerir_admin();
    return coalesce((
        select jsonb_agg(privado.con_credibilidad(privado.reporte_json(r.id_reporte)) - 'id_usuario'
                         order by r.fecha_reporte desc, r.id_reporte desc)
        from reporte r
    ), '[]'::jsonb);
end;
$$;

create or replace function public.admin_reporte(p_id bigint)
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
declare
    v_reporte jsonb;
    v_autor uuid;
begin
    perform privado.requerir_admin();
    v_reporte := privado.reporte_json(p_id);
    if v_reporte is null then
        raise exception 'El reporte no existe o fue eliminado.' using errcode = 'P0001';
    end if;
    v_autor := (v_reporte->>'id_usuario')::uuid;

    return jsonb_build_object(
        'reporte', (v_reporte - 'id_usuario') || jsonb_build_object(
            'email', (select email from auth.users where id = v_autor),
            'autor_invitado', (select es_invitado from usuario where id_usuario = v_autor)),
        'credibilidad', privado.credibilidad_reporte(p_id),
        'credibilidad_autor', case when (select es_invitado from usuario where id_usuario = v_autor) then null
                                   else privado.credibilidad_usuario(v_autor) end,
        'denuncias', coalesce((
            select jsonb_agg(jsonb_build_object('id_advertencia', a.id_advertencia, 'motivo', a.motivo,
                       'fecha', privado.fecha_local(a.fecha), 'nombre_usuario', u.nombre_usuario,
                       'email', au.email) order by a.fecha desc)
            from advertencia a join usuario u on u.id_usuario = a.id_usuario
            left join auth.users au on au.id = a.id_usuario
            where a.id_reporte = p_id), '[]'::jsonb),
        'calificaciones', coalesce((
            select jsonb_agg(jsonb_build_object('id_valoracion', v.id_valoracion, 'valor', v.valor,
                       'fecha', privado.fecha_local(v.fecha), 'nombre_usuario', u.nombre_usuario) order by v.fecha desc)
            from valoracion v join usuario u on u.id_usuario = v.id_usuario
            where v.id_reporte = p_id), '[]'::jsonb),
        'comentarios', coalesce((
            select jsonb_agg(jsonb_build_object('id_comentario', c.id_comentario, 'contenido', c.contenido,
                       'fecha', privado.fecha_local(c.fecha), 'nombre_usuario', u.nombre_usuario) order by c.fecha)
            from comentario c join usuario u on u.id_usuario = c.id_usuario
            where c.id_reporte = p_id), '[]'::jsonb)
    );
end;
$$;

create or replace function public.admin_denuncias()
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
begin
    perform privado.requerir_admin();
    return coalesce((
        select jsonb_agg(jsonb_build_object(
                   'id_advertencia', a.id_advertencia, 'id_reporte', a.id_reporte, 'motivo', a.motivo,
                   'fecha', privado.fecha_local(a.fecha), 'nombre_usuario', u.nombre_usuario,
                   'descripcion', r.descripcion, 'autor_reporte', autor.nombre_usuario,
                   'categoria', case when rb.id_reporte is not null then 'delito' when ic.id_reporte is not null then 'comunitario' else 'otro' end,
                   'tipo', coalesce(tr.nombre, ti.nombre, '-'), 'estado', r.estado) order by a.fecha desc)
        from advertencia a
        join usuario u on u.id_usuario = a.id_usuario
        join reporte r on r.id_reporte = a.id_reporte
        join usuario autor on autor.id_usuario = r.id_usuario
        left join robo rb on rb.id_reporte = r.id_reporte
        left join tipo_robo tr on tr.id_tipo_robo = rb.id_tipo_robo
        left join incidente_comunitario ic on ic.id_reporte = r.id_reporte
        left join tipo_incidente ti on ti.id_tipo_incidente = ic.id_tipo_incidente
    ), '[]'::jsonb);
end;
$$;

create or replace function public.admin_comentarios()
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
begin
    perform privado.requerir_admin();
    return coalesce((
        select jsonb_agg(x.fila order by x.fecha desc, x.id desc)
        from (
            select c.fecha, c.id_comentario as id, jsonb_build_object(
                       'id_comentario', c.id_comentario, 'id_reporte', c.id_reporte, 'contenido', c.contenido,
                       'fecha', privado.fecha_local(c.fecha), 'nombre_usuario', u.nombre_usuario,
                       'descripcion', r.descripcion) as fila
            from comentario c
            join usuario u on u.id_usuario = c.id_usuario
            join reporte r on r.id_reporte = c.id_reporte
            order by c.fecha desc, c.id_comentario desc
            limit 500
        ) x
    ), '[]'::jsonb);
end;
$$;

create or replace function public.admin_calificaciones()
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
begin
    perform privado.requerir_admin();
    return jsonb_build_object(
        'resumen', (select jsonb_build_object(
                        'total', count(*), 'promedio', round(avg(valor), 1),
                        'v1', count(*) filter (where valor = 1), 'v2', count(*) filter (where valor = 2),
                        'v3', count(*) filter (where valor = 3), 'v4', count(*) filter (where valor = 4),
                        'v5', count(*) filter (where valor = 5))
                    from valoracion),
        'calificaciones', coalesce((
            select jsonb_agg(x.fila order by x.fecha desc, x.id desc)
            from (
                select v.fecha, v.id_valoracion as id, jsonb_build_object(
                           'id_valoracion', v.id_valoracion, 'id_reporte', v.id_reporte, 'valor', v.valor,
                           'fecha', privado.fecha_local(v.fecha), 'nombre_usuario', u.nombre_usuario,
                           'descripcion', r.descripcion) as fila
                from valoracion v
                join usuario u on u.id_usuario = v.id_usuario
                join reporte r on r.id_reporte = v.id_reporte
                order by v.fecha desc, v.id_valoracion desc
                limit 500
            ) x), '[]'::jsonb),
        'peor_calificados', coalesce((
            select jsonb_agg(x.fila order by x.promedio, x.cantidad desc)
            from (
                select round(avg(v.valor), 1) as promedio, count(*) as cantidad, jsonb_build_object(
                           'id_reporte', r.id_reporte, 'descripcion', r.descripcion,
                           'promedio', round(avg(v.valor), 1), 'cantidad', count(*)) as fila
                from valoracion v join reporte r on r.id_reporte = v.id_reporte
                group by r.id_reporte, r.descripcion
                having avg(v.valor) <= 2
                order by 1, 2 desc
                limit 10
            ) x), '[]'::jsonb)
    );
end;
$$;

create or replace function public.admin_usuarios()
returns jsonb language plpgsql stable security definer set search_path = public, pg_temp as $$
begin
    perform privado.requerir_admin();
    return coalesce((
        select jsonb_agg(jsonb_build_object(
                   'id_usuario', u.id_usuario, 'nombre_usuario', u.nombre_usuario, 'email', au.email,
                   'es_admin', u.es_admin, 'es_yo', u.id_usuario = auth.uid(),
                   'fecha_registro', privado.fecha_local(u.fecha_registro),
                   'credibilidad', privado.credibilidad_usuario(u.id_usuario)) order by u.fecha_registro)
        from usuario u join auth.users au on au.id = u.id_usuario
        where not u.es_invitado
    ), '[]'::jsonb);
end;
$$;

-- Acciones de moderación sobre reportes, denuncias, comentarios y calificaciones
create or replace function public.admin_accion(p_accion text, p_id bigint, p_estado text default null)
returns jsonb language plpgsql security definer set search_path = public, pg_temp as $$
declare
    v_filas int;
    v_mensaje text;
begin
    perform privado.requerir_admin();

    case p_accion
        when 'borrar_reporte' then
            if not privado.borrar_reporte(p_id) then
                raise exception 'El reporte #% ya no existe.', p_id using errcode = 'P0001';
            end if;
            v_mensaje := format('Reporte #%s eliminado, junto con sus comentarios, calificaciones y denuncias.', p_id);

        when 'cambiar_estado' then
            if p_estado is null or p_estado not in ('pendiente', 'verificado', 'rechazado') then
                raise exception 'Estado inválido.' using errcode = 'P0001';
            end if;
            update reporte set estado = p_estado where id_reporte = p_id;
            get diagnostics v_filas = row_count;
            if v_filas = 0 then
                raise exception 'El reporte #% ya no existe.', p_id using errcode = 'P0001';
            end if;
            v_mensaje := format('El reporte #%s %s.', p_id, case p_estado
                when 'pendiente' then 'vuelve a estar pendiente'
                when 'verificado' then 'quedó verificado'
                else 'quedó rechazado y ya no se muestra en el mapa' end);

        when 'descartar_denuncias' then
            delete from advertencia where id_reporte = p_id;
            get diagnostics v_filas = row_count;
            v_mensaje := format('Se descartaron %s denuncia(s) del reporte #%s. El reporte sigue publicado.', v_filas, p_id);

        when 'borrar_denuncia' then
            delete from advertencia where id_advertencia = p_id;
            get diagnostics v_filas = row_count;
            v_mensaje := case when v_filas > 0 then 'Denuncia descartada.' else 'La denuncia ya no existía.' end;

        when 'borrar_comentario' then
            delete from comentario where id_comentario = p_id;
            get diagnostics v_filas = row_count;
            v_mensaje := case when v_filas > 0 then 'Comentario eliminado.' else 'El comentario ya no existía.' end;

        when 'borrar_calificacion' then
            delete from valoracion where id_valoracion = p_id;
            get diagnostics v_filas = row_count;
            v_mensaje := case when v_filas > 0 then 'Calificación eliminada.' else 'La calificación ya no existía.' end;

        else
            raise exception 'Acción desconocida.' using errcode = 'P0001';
    end case;

    return jsonb_build_object('message', v_mensaje);
end;
$$;

create or replace function public.admin_cambiar_rol(p_usuario uuid, p_es_admin boolean)
returns jsonb language plpgsql security definer set search_path = public, pg_temp as $$
declare
    v_yo uuid := privado.requerir_admin();
begin
    if p_usuario = v_yo and not p_es_admin then
        raise exception 'No podés quitarte el rol de administrador a vos mismo.' using errcode = 'P0001';
    end if;
    update usuario set es_admin = coalesce(p_es_admin, false) where id_usuario = p_usuario and not es_invitado;
    if not found then
        raise exception 'El usuario no existe.' using errcode = 'P0001';
    end if;
    return jsonb_build_object('message', 'Usuario actualizado exitosamente.');
end;
$$;

-- Borra la cuenta (y en cascada sus reportes, comentarios, calificaciones y denuncias)
create or replace function public.admin_borrar_usuario(p_usuario uuid)
returns jsonb language plpgsql security definer set search_path = public, pg_temp as $$
declare
    v_yo uuid := privado.requerir_admin();
    v_ubicaciones bigint[];
begin
    if p_usuario = v_yo then
        raise exception 'No podés eliminar tu propia cuenta desde el panel.' using errcode = 'P0001';
    end if;
    if not exists (select 1 from usuario where id_usuario = p_usuario and not es_invitado) then
        raise exception 'El usuario no existe.' using errcode = 'P0001';
    end if;

    select array_agg(id_ubicacion) into v_ubicaciones from reporte where id_usuario = p_usuario;

    delete from auth.users where id = p_usuario;

    -- Ubicaciones que quedaron sin reportes
    delete from ubicacion ub
     where ub.id_ubicacion = any (coalesce(v_ubicaciones, '{}'))
       and not exists (select 1 from reporte r where r.id_ubicacion = ub.id_ubicacion);

    return jsonb_build_object('message', 'Usuario eliminado exitosamente.');
end;
$$;

revoke all on all functions in schema privado from public, anon, authenticated;

revoke execute on function public.admin_resumen(), public.admin_reportes(), public.admin_reporte(bigint),
    public.admin_denuncias(), public.admin_comentarios(), public.admin_calificaciones(), public.admin_usuarios(),
    public.admin_accion(text, bigint, text), public.admin_cambiar_rol(uuid, boolean), public.admin_borrar_usuario(uuid)
    from public, anon;

grant execute on function public.admin_resumen(), public.admin_reportes(), public.admin_reporte(bigint),
    public.admin_denuncias(), public.admin_comentarios(), public.admin_calificaciones(), public.admin_usuarios(),
    public.admin_accion(text, bigint, text), public.admin_cambiar_rol(uuid, boolean), public.admin_borrar_usuario(uuid)
    to authenticated;

-- Para nombrar al primer administrador, después de crear su cuenta desde la app:
-- update public.usuario set es_admin = true
--  where id_usuario = (select id from auth.users where email = 'TU_CORREO@ejemplo.com');
