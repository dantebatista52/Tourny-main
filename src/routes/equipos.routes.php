<?php

use Slim\App;
use Slim\Views\PhpRenderer;
use App\Middlewares\AuthMiddleware;

/** @var App $app */
/** @var PhpRenderer $renderer */
/** @var Database $database */

// ==========================================
// 4.5 GESTIÓN DE EQUIPOS (ZONA PRIVADA)
// ==========================================

$app->get("/torneos/{id}/equipos", function ($request, $response, $args) use ($renderer) {
    
    $idTorneo = $args['id'];

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmt = $db->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmt->execute([$idTorneo]);
    $torneo = $stmt->fetch();

    if (!$torneo) {
        $response->getBody()->write("El torneo no existe.");
        return $response->withStatus(404);
    }

    $stmt = $db->prepare("SELECT * FROM equipos WHERE id_torneo = ?");
    $stmt->execute([$idTorneo]);
    $equipos = $stmt->fetchAll();

    return view($renderer, $response, "torneos/equipos.php", [
        "torneo" => $torneo,
        "equipos" => $equipos
    ]);
})->add(AuthMiddleware::class);

$app->post("/torneos/{id}/equipos", function ($request, $response, $args) use ($renderer) {
    
    $idTorneo = $args['id'];
    $parsedBody = $request->getParsedBody();
    $nombreEquipo = trim($parsedBody['nombre_equipo'] ?? '');

    if (empty($nombreEquipo)) {
        $response->getBody()->write("El nombre del equipo no puede estar vacío.");
        return $response->withStatus(400);
    }

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmtCheck = $db->prepare("SELECT id FROM equipos WHERE id_torneo = ? AND LOWER(nombre) = LOWER(?)");
    $stmtCheck->execute([$idTorneo, $nombreEquipo]);

    if ($stmtCheck->fetch()) {
        $response->getBody()->write("Ya existe un equipo con el nombre '{$nombreEquipo}' en este torneo.");
        return $response->withStatus(400);
    }

    $codigoInvitacion = generarCodigoInvitacion(6);

    $stmt = $db->prepare("INSERT INTO equipos (id_torneo, nombre, codigo_invitacion) VALUES (?, ?, ?)");
    $stmt->execute([$idTorneo, $nombreEquipo, $codigoInvitacion]);

    return $response->withHeader('Location', "/torneos/{$idTorneo}/equipos")->withStatus(302);
})->add(AuthMiddleware::class);

$app->post("/torneos/{torneo_id}/equipos/{equipo_id}/delete", function ($request, $response, $args) use ($renderer) {
        
    $idTorneo = $args['torneo_id'];
    $idEquipo = $args['equipo_id'];
    $idOrganizador = $_SESSION['usuario_id'];

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmtCheck = $db->prepare("SELECT id FROM torneos WHERE id = ? AND id_organizador = ?");
    $stmtCheck->execute([$idTorneo, $idOrganizador]);

    if (!$stmtCheck->fetch()) {
        $response->getBody()->write("No tienes permisos para realizar esta acción.");
        return $response->withStatus(403);
    }

    $stmtDelete = $db->prepare("DELETE FROM equipos WHERE id = ? AND id_torneo = ?");
    $stmtDelete->execute([$idEquipo, $idTorneo]);

    return $response->withHeader('Location', "/torneos/{$idTorneo}/equipos")->withStatus(302);
})->add(AuthMiddleware::class);

$app->get("/equipos/unirse", function ($request, $response) use ($renderer) {
    return view($renderer, $response, "equipos/unirse.php");
})->add(AuthMiddleware::class);

$app->post("/equipos/unirse", function ($request, $response) use ($renderer) {
    
    $idUsuario = $_SESSION['usuario_id'] ?? null;

    if (!$idUsuario) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $parsedBody = $request->getParsedBody();
    $codigo = strtoupper(trim($parsedBody['codigo_invitacion'] ?? ''));

    if (empty($codigo)) {
        $_SESSION['flash_error'] = "Debes ingresar un código de invitación.";
        return $response->withHeader('Location', '/equipos/unirse')->withStatus(302);
    }

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmt = $db->prepare("SELECT id, nombre, id_torneo FROM equipos WHERE codigo_invitacion = ?");
    $stmt->execute([$codigo]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$equipo) {
        $_SESSION['flash_error'] = "El código ingresado no es válido o venció.";
        return $response->withHeader('Location', '/equipos/unirse')->withStatus(302);
    }

    $stmtCheck = $db->prepare("SELECT id FROM equipo_jugadores WHERE id_equipo = ? AND id_usuario = ?");
    $stmtCheck->execute([$equipo['id'], $idUsuario]);

    if ($stmtCheck->fetch()) {
        $_SESSION['flash_error'] = "Ya formás parte de este equipo.";
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    $stmtInsert = $db->prepare("INSERT INTO equipo_jugadores (id_equipo, id_usuario, fecha_union) VALUES (?, ?, NOW())");
    $stmtInsert->execute([$equipo['id'], $idUsuario]);

    $_SESSION['flash_success'] = "¡Te has unido con éxito a " . htmlspecialchars($equipo['nombre']) . "!";
    return $response->withHeader('Location', '/dashboard')->withStatus(302);
})->add(AuthMiddleware::class);