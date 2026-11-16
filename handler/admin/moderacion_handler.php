<?php

require_once __DIR__ . '/../../config/auth.php';

requerir_admin();

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/reportes.php';

function volver_a(string $destino): void
{
    $ruta = parse_url($destino, PHP_URL_PATH) ?: '';
    $consulta = parse_url($destino, PHP_URL_QUERY);

    if (!preg_match('#/pages/admin/[a-z_]+\.php$#', $ruta)) {
        $destino = '../../pages/admin/reportes.php';
    } else {
        $destino = $ruta . ($consulta ? '?' . $consulta : '');
    }

    header("Location: $destino");
    exit;
}

$volver = (string) ($_POST['volver'] ?? '');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido($_POST['csrf_token'] ?? null)) {
    $_SESSION['errors'] = ["La acción no es válida o la sesión expiró. Intentá nuevamente."];
    volver_a($volver);
}

$accion = $_POST['accion'] ?? '';
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false) {
    $_SESSION['errors'] = ["Identificador inválido."];
    volver_a($volver);
}

try {
    switch ($accion) {
        case 'borrar_reporte':
            if (!borrar_reporte($connection, $id)) {
                $_SESSION['errors'] = ["El reporte #$id ya no existe."];
                break;
            }

            $_SESSION['success'] = "Reporte #$id eliminado, junto con sus comentarios, calificaciones y denuncias.";

            if (preg_match('#/pages/admin/reporte\.php#', $volver)) {
                $volver = '../../pages/admin/reportes.php';
            }
            break;

        case 'cambiar_estado':
            $estado = $_POST['estado'] ?? '';

            if (!in_array($estado, ESTADOS_REPORTE, true)) {
                $_SESSION['errors'] = ["Estado inválido."];
                break;
            }

            $stmt = $connection->prepare("UPDATE reporte SET estado = :e WHERE id_reporte = :id");
            $stmt->execute([':e' => $estado, ':id' => $id]);

            $textos = ['pendiente' => 'vuelve a estar pendiente', 'verificado' => 'quedó verificado', 'rechazado' => 'quedó rechazado y ya no se muestra en el mapa'];
            $_SESSION['success'] = "El reporte #$id " . $textos[$estado] . ".";
            break;

        case 'descartar_denuncias':
            $stmt = $connection->prepare("DELETE FROM advertencia WHERE id_reporte = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = "Se descartaron {$stmt->rowCount()} denuncia(s) del reporte #$id. El reporte sigue publicado.";
            break;

        case 'borrar_denuncia':
            $stmt = $connection->prepare("DELETE FROM advertencia WHERE id_advertencia = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = $stmt->rowCount() ? "Denuncia descartada." : "La denuncia ya no existía.";
            break;

        case 'borrar_comentario':
            $stmt = $connection->prepare("DELETE FROM comentario WHERE id_comentario = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = $stmt->rowCount() ? "Comentario eliminado." : "El comentario ya no existía.";
            break;

        case 'borrar_calificacion':
            $stmt = $connection->prepare("DELETE FROM valoracion WHERE id_valoracion = :id");
            $stmt->execute([':id' => $id]);
            $_SESSION['success'] = $stmt->rowCount() ? "Calificación eliminada." : "La calificación ya no existía.";
            break;

        default:
            $_SESSION['errors'] = ["Acción desconocida."];
    }
} catch (PDOException $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    $_SESSION['errors'] = ["No se pudo completar la acción en la base de datos."];
}

volver_a($volver);
