<?php

use Slim\App;
use Slim\Views\PhpRenderer;
use App\Middlewares\AuthMiddleware;

/** @var App $app */
/** @var PhpRenderer $renderer */
/** @var Database $database */

// ==========================================
// 3. DASHBOARD PRINCIPAL
// ==========================================

$app->get("/dashboard", function ($request, $response) use ($renderer) {
    $idOrganizador = $_SESSION['usuario_id'];

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmt = $db->prepare("SELECT * FROM torneos WHERE id_organizador = ? ORDER BY created_at DESC");
    $stmt->execute([$idOrganizador]);
    $torneos = $stmt->fetchAll();

    return view($renderer, $response, "dashboard/dashboard.php", [
        "nombre" => $_SESSION['usuario_nombre'],
        "torneos" => $torneos
    ]);
})->add(AuthMiddleware::class);

// ==========================================
// 4. TORNEOS (ZONA PRIVADA)
// ==========================================

$app->get("/torneos", function ($request, $response) use ($renderer) {
    $idOrganizador = $_SESSION['usuario_id'];

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmt = $db->prepare("SELECT * FROM torneos WHERE id_organizador = ? ORDER BY created_at DESC");
    $stmt->execute([$idOrganizador]);
    $torneos = $stmt->fetchAll();

    return view($renderer, $response, "torneos/index.php", [
        "torneos" => $torneos
    ]);
})->add(AuthMiddleware::class);

$app->get("/torneos/create", function ($request, $response) use ($renderer) {
    return view($renderer, $response, "torneos/create.php");
})->add(AuthMiddleware::class);

$app->post("/torneos/create", function ($request, $response) use ($renderer) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $parsedBody = $request->getParsedBody();
    $nombreTorneo = trim($parsedBody['nombre'] ?? '');
    $formato = $parsedBody['formato'] ?? 'liga';
    $idOrganizador = $_SESSION['usuario_id'];

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nombreTorneo), '-'));

    if (empty($nombreTorneo) || empty($formato) || empty($slug)) {
        $response->getBody()->write("Todos los campos son obligatorios y el nombre debe ser válido.");
        return $response->withStatus(400);
    }

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmt = $db->prepare("INSERT INTO torneos (nombre, slug, formato, id_organizador) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombreTorneo, $slug, $formato, $idOrganizador]);

    $idTorneo = $db->lastInsertId();

    return $response->withHeader('Location', "/torneos/{$idTorneo}/equipos")->withStatus(302);
})->add(AuthMiddleware::class);

$app->post("/torneos/{id}/delete", function ($request, $response, $args) use ($renderer) {

    $idTorneo = $args['id'];
    $idOrganizador = $_SESSION['usuario_id'];

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmt = $db->prepare("SELECT id FROM torneos WHERE id = ? AND id_organizador = ?");
    $stmt->execute([$idTorneo, $idOrganizador]);
    
    if (!$stmt->fetch()) {
        $response->getBody()->write("No tienes permisos para eliminar este torneo o no existe.");
        return $response->withStatus(403);
    }

    $stmtDelete = $db->prepare("DELETE FROM torneos WHERE id = ?");
    $stmtDelete->execute([$idTorneo]);

    return $response->withHeader('Location', '/dashboard')->withStatus(302);
})->add(AuthMiddleware::class);

// ==========================================
// 5. PARTIDOS / FIXTURE (ZONA PRIVADA)
// ==========================================

$app->post("/torneos/{id}/fixture/generar", function ($request, $response, $args) {
    
    $idTorneo = $args['id'];
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmtTorneo = $db->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmtTorneo->execute([$idTorneo]);
    $torneo = $stmtTorneo->fetch();

    if (!$torneo) {
        $response->getBody()->write("Torneo no encontrado.");
        return $response->withStatus(404);
    }

    $stmt = $db->prepare("SELECT id FROM equipos WHERE id_torneo = ?");
    $stmt->execute([$idTorneo]);
    $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $cantEquipos = count($equipos);

    if ($torneo['formato'] === 'eliminatoria') {
        $potenciasValidas = [2, 4, 8, 16, 32];
        
        if (!in_array($cantEquipos, $potenciasValidas, true)) {
            $response->getBody()->write("Para el formato de Eliminación Directa debes registrar exactamente 2, 4, 8, 16 o 32 equipos. Actualmente tienes {$cantEquipos}.");
            return $response->withStatus(400);
        }
    } else {
        if ($cantEquipos < 2) {
            $response->getBody()->write("Necesitas al menos 2 equipos para generar el fixture de una liga.");
            return $response->withStatus(400);
        }
    }

    $stmtDelete = $db->prepare("DELETE FROM partidos WHERE id_torneo = ?");
    $stmtDelete->execute([$idTorneo]);

    if ($torneo['formato'] === 'eliminatoria') {
        shuffle($equipos);

        for ($i = 0; $i < $cantEquipos; $i += 2) {
            $local = $equipos[$i];
            $visitante = $equipos[$i + 1];

            $stmtInsert = $db->prepare("INSERT INTO partidos (id_torneo, id_equipo_local, id_equipo_visitante, fecha_numero) VALUES (?, ?, ?, 1)");
            $stmtInsert->execute([$idTorneo, $local, $visitante]);
        }
    } else {
        if ($cantEquipos % 2 !== 0) {
            $equipos[] = null;
            $cantEquipos++;
        }

        $jornadas = $cantEquipos - 1;
        $partidosPorJornada = $cantEquipos / 2;

        for ($jornada = 1; $jornada <= $jornadas; $jornada++) {
            for ($i = 0; $i < $partidosPorJornada; $i++) {
                $local = $equipos[$i];
                $visitante = $equipos[$cantEquipos - 1 - $i];

                if ($local === null && $visitante === null) {
                    continue;
                }

                $stmtInsert = $db->prepare("INSERT INTO partidos (id_torneo, id_equipo_local, id_equipo_visitante, fecha_numero) VALUES (?, ?, ?, ?)");
                $stmtInsert->execute([$idTorneo, $local, $visitante, $jornada]);
            }

            $ultimoEquipo = array_pop($equipos);
            array_splice($equipos, 1, 0, [$ultimoEquipo]);
        }
    }

    return $response->withHeader('Location', "/torneos/{$idTorneo}/fixture")->withStatus(302);
})->add(AuthMiddleware::class);

$app->get("/torneos/{id}/fixture", function ($request, $response, $args) use ($renderer) {

    $idTorneo = $args['id'];
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmtTorneo = $db->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmtTorneo->execute([$idTorneo]);
    $torneo = $stmtTorneo->fetch();

    $sql = "SELECT p.*, 
                   el.nombre AS local_nombre, 
                   ev.nombre AS visitante_nombre 
            FROM partidos p
            LEFT JOIN equipos el ON p.id_equipo_local = el.id
            LEFT JOIN equipos ev ON p.id_equipo_visitante = ev.id
            WHERE p.id_torneo = ?
            ORDER BY p.fecha_numero ASC, p.id ASC";

    $stmtPartidos = $db->prepare($sql);
    $stmtPartidos->execute([$idTorneo]);
    $partidos = $stmtPartidos->fetchAll();

    return view($renderer, $response, "partidos/fixture.php", [
        "torneo" => $torneo,
        "partidos" => $partidos
    ]);
})->add(AuthMiddleware::class);

$app->post("/partidos/{id}/resultado", function ($request, $response, $args) {
    
    $idPartido = $args['id'];
    $parsedBody = $request->getParsedBody();
    
    $golesLocal = $parsedBody['goles_local'] ?? null;
    $golesVisitante = $parsedBody['goles_visitante'] ?? null;

    if ($golesLocal === null || $golesVisitante === null) {
        $response->getBody()->write("Debes ingresar los goles de ambos equipos.");
        return $response->withStatus(400);
    }

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmtPartido = $db->prepare("SELECT id_torneo FROM partidos WHERE id = ?");
    $stmtPartido->execute([$idPartido]);
    $partido = $stmtPartido->fetch();

    if (!$partido) {
        $response->getBody()->write("Partido no encontrado.");
        return $response->withStatus(404);
    }

    $stmt = $db->prepare("UPDATE partidos SET goles_local = ?, goles_visitante = ?, estado = 'finalizado' WHERE id = ?");
    $stmt->execute([$golesLocal, $golesVisitante, $idPartido]);

    return $response->withHeader('Location', "/torneos/{$partido['id_torneo']}/fixture")->withStatus(302);
})->add(AuthMiddleware::class);

$app->get("/torneos/{id}/tabla", function ($request, $response, $args) use ($renderer) {

    $idTorneo = $args['id'];
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmtTorneo = $db->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmtTorneo->execute([$idTorneo]);
    $torneo = $stmtTorneo->fetch();

    if (!$torneo) {
        $response->getBody()->write("Torneo no encontrado.");
        return $response->withStatus(404);
    }

    $stmtEquipos = $db->prepare("SELECT id, nombre FROM equipos WHERE id_torneo = ?");
    $stmtEquipos->execute([$idTorneo]);
    $equipos = $stmtEquipos->fetchAll();

    $tabla = [];
    foreach ($equipos as $eq) {
        $tabla[$eq['id']] = [
            'nombre' => $eq['nombre'],
            'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0,
            'gf' => 0, 'gc' => 0, 'dg' => 0, 'pts' => 0
        ];
    }

    $stmtPartidos = $db->prepare("
        SELECT * FROM partidos 
        WHERE id_torneo = ? 
          AND goles_local IS NOT NULL 
          AND goles_visitante IS NOT NULL 
          AND id_equipo_local IS NOT NULL 
          AND id_equipo_visitante IS NOT NULL
    ");
    $stmtPartidos->execute([$idTorneo]);
    $partidos = $stmtPartidos->fetchAll();

    foreach ($partidos as $p) {
        $idLocal = $p['id_equipo_local'];
        $idVisitante = $p['id_equipo_visitante'];
        $golesL = (int)$p['goles_local'];
        $golesV = (int)$p['goles_visitante'];

        if (!isset($tabla[$idLocal]) || !isset($tabla[$idVisitante])) {
            continue;
        }

        $tabla[$idLocal]['pj']++;
        $tabla[$idVisitante]['pj']++;

        $tabla[$idLocal]['gf'] += $golesL;
        $tabla[$idLocal]['gc'] += $golesV;
        $tabla[$idVisitante]['gf'] += $golesV;
        $tabla[$idVisitante]['gc'] += $golesL;

        if ($golesL > $golesV) {
            $tabla[$idLocal]['pg']++;
            $tabla[$idLocal]['pts'] += 3;
            $tabla[$idVisitante]['pp']++;
        } elseif ($golesV > $golesL) {
            $tabla[$idVisitante]['pg']++;
            $tabla[$idVisitante]['pts'] += 3;
            $tabla[$idLocal]['pp']++;
        } else {
            $tabla[$idLocal]['pe']++;
            $tabla[$idLocal]['pts'] += 1;
            $tabla[$idVisitante]['pe']++;
            $tabla[$idVisitante]['pts'] += 1;
        }
    }

    foreach ($tabla as &$e) {
        $e['dg'] = $e['gf'] - $e['gc'];
    }
    unset($e);

    usort($tabla, function ($a, $b) {
        if ($b['pts'] !== $a['pts']) {
            return $b['pts'] <=> $a['pts'];
        }
        if ($b['dg'] !== $a['dg']) {
            return $b['dg'] <=> $a['dg'];
        }
        return $b['gf'] <=> $a['gf'];
    });

    return view($renderer, $response, "torneos/tabla.php", [
        "torneo" => $torneo,
        "tabla" => $tabla
    ]);
})->add(AuthMiddleware::class);