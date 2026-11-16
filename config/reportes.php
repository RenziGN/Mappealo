<?php

const ESTADOS_REPORTE = ['pendiente', 'verificado', 'rechazado'];

function borrar_reporte(PDO $connection, int $idReporte, ?int $soloDelUsuario = null): bool
{
    $connection->beginTransaction();

    try {
        $sql = "SELECT id_ubicacion FROM reporte WHERE id_reporte = :id";
        $parametros = [':id' => $idReporte];

        if ($soloDelUsuario !== null) {
            $sql .= " AND id_usuario = :u";
            $parametros[':u'] = $soloDelUsuario;
        }

        $stmt = $connection->prepare($sql . " FOR UPDATE");
        $stmt->execute($parametros);
        $idUbicacion = $stmt->fetchColumn();

        if ($idUbicacion === false) {
            $connection->rollBack();
            return false;
        }

        $connection->prepare("DELETE FROM reporte WHERE id_reporte = :id")->execute([':id' => $idReporte]);

        $stmt = $connection->prepare("SELECT 1 FROM reporte WHERE id_ubicacion = :u LIMIT 1");
        $stmt->execute([':u' => $idUbicacion]);
        if (!$stmt->fetchColumn()) {
            $connection->prepare("DELETE FROM ubicacion WHERE id_ubicacion = :u")->execute([':u' => $idUbicacion]);
        }

        $connection->commit();
        return true;
    } catch (PDOException $e) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $e;
    }
}
