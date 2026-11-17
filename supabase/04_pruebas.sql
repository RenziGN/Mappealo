-- Mappealo en Supabase · pruebas de seguridad y de lógica
-- Crea usuarios de prueba (correos @prueba.invalid), se hace pasar por cada uno,
-- prueba lo que puede y no puede hacer, y al final borra todo lo que creó.
-- El resultado es una tabla: cada fila dice si la prueba pasó.

-- Limpieza por si una ejecución anterior quedó a medias
delete from auth.users where email like '%@prueba.invalid' or id in (
    '00000000-0000-4000-8000-0000000000a1', '00000000-0000-4000-8000-0000000000c1',
    '00000000-0000-4000-8000-0000000000d1', '00000000-0000-4000-8000-0000000000e1');

create temp table resultado (n serial, prueba text, esperado text, obtenido text, ok boolean);

-- Ejecuta una consulta como un usuario dado y anota si salió como se esperaba.
-- p_espera: 'ok' (sin error), 'true' (devuelve verdadero) o el pedazo de texto que tiene que tener el error.
create function pg_temp.probar(p_prueba text, p_usuario uuid, p_anonimo boolean, p_sql text, p_espera text)
returns text language plpgsql as $$
declare
    v_resultado text;
    v_error text;
begin
    begin
        if p_usuario is null then
            perform set_config('request.jwt.claims', '{"role":"anon"}', true);
            perform set_config('role', 'anon', true);
        else
            perform set_config('request.jwt.claims',
                json_build_object('sub', p_usuario, 'role', 'authenticated', 'is_anonymous', p_anonimo)::text, true);
            perform set_config('role', 'authenticated', true);
        end if;

        if p_sql ~* '^\s*(update|delete|insert)\s' then
            execute p_sql;
            v_resultado := 'ejecutado';
        else
            execute 'select (' || p_sql || ')::text' into v_resultado;
        end if;
    exception when others then
        v_error := sqlerrm;
    end;

    perform set_config('role', 'postgres', true);
    perform set_config('request.jwt.claims', '', true);

    insert into resultado (prueba, esperado, obtenido, ok) values (
        p_prueba, p_espera, left(coalesce('ERROR: ' || v_error, v_resultado, 'null'), 160),
        case when p_espera = 'ok' then v_error is null
             when p_espera = 'true' then v_error is null and v_resultado = 'true'
             else v_error is not null and v_error ilike '%' || p_espera || '%' end);
    return v_resultado;
end;
$$;

do $$
declare
    admin uuid := '00000000-0000-4000-8000-0000000000a1';
    carlos uuid := '00000000-0000-4000-8000-0000000000c1';
    mariana uuid := '00000000-0000-4000-8000-0000000000d1';
    invitado uuid := '00000000-0000-4000-8000-0000000000e1';
    rep_carlos bigint;
    rep_invitado bigint;
    cred jsonb;
    ahora text := to_char(now() at time zone 'America/Argentina/Buenos_Aires' - interval '1 hour', 'YYYY-MM-DD"T"HH24:MI');
    delito text;
begin
    insert into auth.users (id, instance_id, aud, role, email, is_anonymous, created_at, updated_at) values
        (admin, '00000000-0000-0000-0000-000000000000', 'authenticated', 'authenticated', 'admin@prueba.invalid', false, now(), now()),
        (carlos, '00000000-0000-0000-0000-000000000000', 'authenticated', 'authenticated', 'carlos@prueba.invalid', false, now(), now()),
        (mariana, '00000000-0000-0000-0000-000000000000', 'authenticated', 'authenticated', 'mariana@prueba.invalid', false, now(), now()),
        (invitado, '00000000-0000-0000-0000-000000000000', 'authenticated', 'authenticated', null, true, now(), now());
    update public.usuario set es_admin = true where id_usuario = admin;

    insert into resultado (prueba, esperado, obtenido, ok)
    select 'Cuentas creadas por el trigger', '4 filas, invitado marcado', count(*) || ' filas',
           count(*) = 4 and bool_or(es_invitado) and not bool_or(es_admin and id_usuario <> admin)
    from public.usuario where id_usuario in (admin, carlos, mariana, invitado);

    insert into resultado (prueba, esperado, obtenido, ok)
    select 'Nombre público sale del correo', 'carlos', nombre_usuario, nombre_usuario = 'carlos'
    from public.usuario where id_usuario = carlos;

    -- Sin sesión
    perform pg_temp.probar('Sin sesión: ver el mapa', null, false, 'public.reportes_mapa()', 'permission denied');
    perform pg_temp.probar('Sin sesión: leer tabla reporte', null, false, '(select count(*) from public.reporte)', 'permission denied');

    -- Invitado
    perform pg_temp.probar('Invitado: su perfil dice invitado', invitado, true, '(public.mi_perfil()->>''es_invitado'')::boolean = true', 'true');
    perform pg_temp.probar('Invitado: publica reporte comunitario', invitado, true,
        $q$public.crear_reporte('{"tipo":"comunitario","incidente":"Basura acumulada","descripcion":"PRUEBA invitado","latitud":-34.66,"longitud":-58.66}')$q$, 'ok');
    select id_reporte into rep_invitado from public.reporte where id_usuario = invitado limit 1;
    perform pg_temp.probar('Invitado: comentar', invitado, true, format('public.comentar(%s, ''hola hola'')', rep_invitado), 'cuenta registrada');
    perform pg_temp.probar('Invitado: calificar', invitado, true, format('public.calificar(%s, 5)', rep_invitado), 'cuenta registrada');
    perform pg_temp.probar('Invitado: ver su historial', invitado, true, 'public.mis_reportes()', 'cuenta registrada');

    -- Validaciones al publicar
    delito := format('"tipo":"delito","tipo_robo":"Robo a mano armada","fecha_hora":"%s","descripcion":"PRUEBA delito","latitud":-34.661,"longitud":-58.662,"gravedad":{"violencia":true}', ahora);
    perform pg_temp.probar('Carlos: publica delito', carlos, false, format('public.crear_reporte(''{%s}'')', delito), 'ok');
    select id_reporte into rep_carlos from public.reporte where id_usuario = carlos order by id_reporte limit 1;
    perform pg_temp.probar('Carlos: lee la tabla reporte directo', carlos, false, '(select count(*) from public.reporte)', 'permission denied');
    insert into resultado (prueba, esperado, obtenido, ok)
    select 'Fecha guardada en hora de Argentina', ahora,
           to_char(fecha_incidente at time zone 'America/Argentina/Buenos_Aires', 'YYYY-MM-DD"T"HH24:MI'),
           to_char(fecha_incidente at time zone 'America/Argentina/Buenos_Aires', 'YYYY-MM-DD"T"HH24:MI') = ahora
    from public.reporte where id_reporte = rep_carlos;
    perform pg_temp.probar('Latitud inválida', carlos, false,
        $q$public.crear_reporte('{"tipo":"comunitario","incidente":"Basura acumulada","latitud":500,"longitud":-58.66}')$q$, 'ubicación del reporte no es válida');
    perform pg_temp.probar('Fecha futura', carlos, false,
        $q$public.crear_reporte('{"tipo":"comunitario","incidente":"Basura acumulada","latitud":-34.66,"longitud":-58.66,"fecha_hora":"2099-01-01T10:00"}')$q$, 'no puede ser futura');
    perform pg_temp.probar('Tipo inventado', carlos, false,
        $q$public.crear_reporte('{"tipo":"comunitario","incidente":"Hackeo","latitud":-34.66,"longitud":-58.66}')$q$, 'tipo de reporte válido');
    perform pg_temp.probar('Delito sin fecha', carlos, false,
        $q$public.crear_reporte('{"tipo":"delito","tipo_robo":"Robo a mano armada","latitud":-34.66,"longitud":-58.66}')$q$, 'fecha y hora del delito');
    perform pg_temp.probar('Descripción de 1001 caracteres', carlos, false,
        format('public.crear_reporte(''{"tipo":"comunitario","incidente":"Basura acumulada","latitud":-34.66,"longitud":-58.66,"descripcion":"%s"}'')', repeat('a', 1001)), 'hasta 1000');

    -- Reporte anónimo: el público ve "Anónimo", el admin ve el nombre real
    perform pg_temp.probar('Admin: publica un reporte anónimo', admin, false,
        $q$public.crear_reporte('{"tipo":"comunitario","incidente":"Bache / Calle anegada","descripcion":"PRUEBA anonimo","latitud":-34.663,"longitud":-58.663,"anonimo":true}')$q$, 'ok');
    perform pg_temp.probar('Anónimo: en el mapa no se ve el nombre', mariana, false,
        $q$(select bool_and(x->>'nombre_usuario' = 'Anónimo') from jsonb_array_elements(public.reportes_mapa()) x where x->>'descripcion' = 'PRUEBA anonimo')$q$, 'true');
    perform pg_temp.probar('Anónimo: en el detalle no se ve el nombre', mariana, false,
        $q$public.reporte_detalle((select id_reporte from public.reporte where descripcion = 'PRUEBA anonimo'))->'reporte'->>'nombre_usuario' = 'Anónimo'$q$, 'true');
    perform pg_temp.probar('Anónimo: el panel admin muestra el nombre real', admin, false,
        $q$(select bool_and(x->>'nombre_usuario' = 'admin' and (x->>'es_anonimo')::boolean) from jsonb_array_elements(public.admin_reportes()) x where x->>'descripcion' = 'PRUEBA anonimo')$q$, 'true');

    -- Interacciones entre usuarios
    perform pg_temp.probar('Mariana: comenta', mariana, false, format('public.comentar(%s, ''Lo vi también'')', rep_carlos), 'ok');
    perform pg_temp.probar('Mariana: comentario de 1 letra', mariana, false, format('public.comentar(%s, ''a'')', rep_carlos), 'entre 2 y 500');
    perform pg_temp.probar('Mariana: califica 4', mariana, false, format('public.calificar(%s, 4)', rep_carlos), 'ok');
    perform pg_temp.probar('Mariana: cambia a 5 (no duplica)', mariana, false,
        format('(public.calificar(%s, 5)->''calificacion''->>''cantidad'')::int = 1', rep_carlos), 'true');
    perform pg_temp.probar('Mariana: califica con 9', mariana, false, format('public.calificar(%s, 9)', rep_carlos), 'de 1 a 5');
    perform pg_temp.probar('Carlos: se califica a sí mismo', carlos, false, format('public.calificar(%s, 5)', rep_carlos), 'ok');
    perform pg_temp.probar('Mariana: denuncia', mariana, false, format('public.denunciar(%s, ''Información falsa'', '''')', rep_carlos), 'ok');
    perform pg_temp.probar('Mariana: denuncia otra vez', mariana, false, format('public.denunciar(%s, ''Spam o publicidad'', '''')', rep_carlos), 'Ya denunciaste');
    perform pg_temp.probar('Mariana: motivo inventado', mariana, false, format('public.denunciar(%s, ''Me cae mal'', '''')', rep_invitado), 'motivo');

    -- Credibilidad: base 50 + calificación de mariana 5★ (1) = +8, denuncia = -15; el voto propio no cuenta
    cred := privado.credibilidad_reporte(rep_carlos);
    insert into resultado (prueba, esperado, obtenido, ok)
    values ('Credibilidad del reporte de Carlos', '43', cred->>'puntaje', (cred->>'puntaje')::int = 43);
    insert into resultado (prueba, esperado, obtenido, ok)
    select 'Credibilidad del reporte de invitado', '25', privado.credibilidad_reporte(rep_invitado)->>'puntaje',
           (privado.credibilidad_reporte(rep_invitado)->>'puntaje')::int = 25;

    -- Lo que un usuario común no puede hacer
    perform pg_temp.probar('Mariana: borra reporte ajeno', mariana, false, format('public.borrar_mi_reporte(%s)', rep_carlos), 'no es tuyo');
    perform pg_temp.probar('Mariana: entra al panel admin', mariana, false, 'public.admin_reportes()', 'Acceso denegado');
    perform pg_temp.probar('Mariana: cambia estado de un reporte', mariana, false, format('public.admin_accion(''cambiar_estado'', %s, ''rechazado'')', rep_carlos), 'Acceso denegado');
    perform pg_temp.probar('Mariana: se hace admin (tabla)', mariana, false, format('update public.usuario set es_admin = true where id_usuario = %L', mariana), 'permission denied');
    perform pg_temp.probar('Mariana: se hace admin (función)', mariana, false, format('public.admin_cambiar_rol(%L, true)', mariana), 'Acceso denegado');
    perform pg_temp.probar('Mariana: lee la tabla de usuarios', mariana, false, '(select count(*) from public.usuario)', 'permission denied');
    perform pg_temp.probar('Mariana: borra comentarios directo', mariana, false, 'delete from public.comentario', 'permission denied');
    perform pg_temp.probar('Mariana: lee los tipos de robo', mariana, false, '(select count(*) from public.tipo_robo)', 'ok');
    perform pg_temp.probar('Mariana: usa funciones privadas', mariana, false, format('privado.borrar_reporte(%s)', rep_carlos), 'permission denied');

    -- Administrador
    perform pg_temp.probar('Admin: ve el panel', admin, false, 'public.admin_reportes()', 'ok');
    perform pg_temp.probar('Admin: ve correos de usuarios', admin, false, '(public.admin_usuarios()::text like ''%carlos@prueba.invalid%'')', 'true');
    perform pg_temp.probar('Admin: rechaza el reporte de Carlos', admin, false, format('public.admin_accion(''cambiar_estado'', %s, ''rechazado'')', rep_carlos), 'ok');
    perform pg_temp.probar('Admin: estado inventado', admin, false, format('public.admin_accion(''cambiar_estado'', %s, ''borrado'')', rep_carlos), 'Estado inválido');
    perform pg_temp.probar('Rechazado: no sale en el mapa', mariana, false,
        format('not (public.reportes_mapa() @> ''[{"id_reporte": %s}]'')', rep_carlos), 'true');
    perform pg_temp.probar('Rechazado: Mariana no ve el detalle', mariana, false, format('public.reporte_detalle(%s)', rep_carlos), 'no existe');
    perform pg_temp.probar('Rechazado: Carlos sí ve el suyo', carlos, false, format('public.reporte_detalle(%s)', rep_carlos), 'ok');
    perform pg_temp.probar('Rechazado: Mariana no puede comentarlo', mariana, false, format('public.comentar(%s, ''hola hola'')', rep_carlos), 'no existe');
    perform pg_temp.probar('Admin: se quita el rol', admin, false, format('public.admin_cambiar_rol(%L, false)', admin), 'a vos mismo');
    perform pg_temp.probar('Admin: se borra a sí mismo', admin, false, format('public.admin_borrar_usuario(%L)', admin), 'propia cuenta');
    perform pg_temp.probar('Admin: descarta denuncias', admin, false, format('public.admin_accion(''descartar_denuncias'', %s)', rep_carlos), 'ok');

    -- Límite de 5 reportes cada 10 minutos (Carlos ya publicó 1)
    for i in 1..4 loop
        perform pg_temp.probar('Carlos: reporte extra ' || i, carlos, false,
            $q$public.crear_reporte('{"tipo":"comunitario","incidente":"Luminaria rota","latitud":-34.66,"longitud":-58.66}')$q$, 'ok');
    end loop;
    perform pg_temp.probar('Carlos: 6.º reporte en 10 minutos', carlos, false,
        $q$public.crear_reporte('{"tipo":"comunitario","incidente":"Luminaria rota","latitud":-34.66,"longitud":-58.66}')$q$, 'muchos reportes seguidos');

    -- Borrados
    perform pg_temp.probar('Carlos: borra su reporte', carlos, false, format('public.borrar_mi_reporte(%s)', rep_carlos), 'ok');
    perform pg_temp.probar('Admin: borra la cuenta de Mariana', admin, false, format('public.admin_borrar_usuario(%L)', mariana), 'ok');
    insert into resultado (prueba, esperado, obtenido, ok)
    select 'Cuenta de Mariana borrada en cascada', '0', count(*)::text, count(*) = 0
    from public.usuario where id_usuario = mariana;
end;
$$;

-- Limpieza: borra las cuentas de prueba y todo lo que crearon
delete from privado.limite_accion where id_usuario in (
    '00000000-0000-4000-8000-0000000000a1', '00000000-0000-4000-8000-0000000000c1',
    '00000000-0000-4000-8000-0000000000d1', '00000000-0000-4000-8000-0000000000e1');
delete from public.ubicacion ub
 where not exists (select 1 from public.reporte r where r.id_ubicacion = ub.id_ubicacion);
delete from auth.users where id in (
    '00000000-0000-4000-8000-0000000000a1', '00000000-0000-4000-8000-0000000000c1',
    '00000000-0000-4000-8000-0000000000d1', '00000000-0000-4000-8000-0000000000e1');

select n, case when ok then '✅' else '❌' end as resultado, prueba, esperado, obtenido
from resultado order by resultado.ok, n;
