<?php

use Slim\App;
use Slim\Views\PhpRenderer;
use App\Middlewares\GuestMiddleware;
use App\Middlewares\AuthMiddleware;

/** @var App $app */
/** @var PhpRenderer $renderer */
/** @var Database $database */

// ==========================================
// 1. RUTA PÚBLICA / LANDING
// ==========================================

// Ruta de la Landing Page (GET /)
$app->get("/", function ($request, $response) use ($renderer) {
    return viewStandalone($renderer, $response, "landing.php", [
        "titulo" => "Bienvenido a Tourny",
        "isLoggedIn" => isset($_SESSION['usuario_id'])
    ]);
})->add(GuestMiddleware::class);

// Vista pública compartida para los jugadores (Solo lectura)
$app->get("/torneo/{slug}", function ($request, $response, $args) use ($renderer) {
    $slug = $args['slug'];
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // 1. Obtener el torneo por el slug
    $stmtTorneo = $db->prepare("SELECT * FROM torneos WHERE slug = ?");
    $stmtTorneo->execute([$slug]);
    $torneo = $stmtTorneo->fetch();

    if (!$torneo) {
        $response->getBody()->write("Torneo no encontrado.");
        return $response->withStatus(404);
    }

    $idTorneo = $torneo['id'];

    // 2. Obtener partidos
    $sqlPartidos = "SELECT p.*, 
                           el.nombre AS local_nombre, 
                           ev.nombre AS visitante_nombre 
                    FROM partidos p
                    LEFT JOIN equipos el ON p.id_equipo_local = el.id
                    LEFT JOIN equipos ev ON p.id_equipo_visitante = ev.id
                    WHERE p.id_torneo = ?
                    ORDER BY p.fecha_numero ASC, p.id ASC";
    $stmtPartidos = $db->prepare($sqlPartidos);
    $stmtPartidos->execute([$idTorneo]);
    $partidos = $stmtPartidos->fetchAll();

    // 3. Obtener equipos y calcular la tabla de posiciones
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

    foreach ($partidos as $p) {
        if ($p['goles_local'] !== null && $p['goles_visitante'] !== null) {
            $idLocal = $p['id_equipo_local'];
            $idVisitante = $p['id_equipo_visitante'];
            
            if (isset($tabla[$idLocal]) && isset($tabla[$idVisitante])) {
                $golesL = (int)$p['goles_local'];
                $golesV = (int)$p['goles_visitante'];

                $tabla[$idLocal]['pj']++;
                $tabla[$idVisitante]['pj']++;
                $tabla[$idLocal]['gf'] += $golesL;
                $tabla[$idLocal]['gc'] += $golesV;
                $tabla[$idVisitante]['gf'] += $golesV;
                $tabla[$idVisitante]['gc'] += $golesL;

                if ($golesL > $golesV) {
                    $tabla[$idLocal]['pg']++; $tabla[$idLocal]['pts'] += 3; $tabla[$idVisitante]['pp']++;
                } elseif ($golesV > $golesL) {
                    $tabla[$idVisitante]['pg']++; $tabla[$idVisitante]['pts'] += 3; $tabla[$idLocal]['pp']++;
                } else {
                    $tabla[$idLocal]['pe']++; $tabla[$idLocal]['pts'] += 1;
                    $tabla[$idVisitante]['pe']++; $tabla[$idVisitante]['pts'] += 1;
                }
            }
        }
    }

    foreach ($tabla as &$e) { $e['dg'] = $e['gf'] - $e['gc']; }
    unset($e);

    usort($tabla, function ($a, $b) {
        if ($b['pts'] !== $a['pts']) return $b['pts'] <=> $a['pts'];
        if ($b['dg'] !== $a['dg']) return $b['dg'] <=> $a['dg'];
        return $b['gf'] <=> $a['gf'];
    });

    return view($renderer, $response, "public/torneo_slug.php", [
        "torneo" => $torneo,
        "partidos" => $partidos,
        "tabla" => $tabla
    ]);
    })->add(GuestMiddleware::class);


// ==========================================
// 2. AUTENTICACIÓN (AUTH)
// ==========================================

// Muestra el formulario de registro (GET)
$app->get("/registro", function ($request, $response) use ($renderer) {
    return view($renderer, $response, "auth/registro.php");
})->add(GuestMiddleware::class);

// Procesa el formulario de registro (POST)
$app->post("/registro", function ($request, $response) use ($renderer) {
    $parsedBody = $request->getParsedBody();
    
    $nombre = $parsedBody['nombre'] ?? '';
    $email = $parsedBody['email'] ?? '';
    $password = $parsedBody['password'] ?? '';

    if (empty($nombre) || empty($email) || empty($password)) {
        $response->getBody()->write("Todos los campos son obligatorios.");
        return $response->withStatus(400);
    }

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $response->getBody()->write("El email ya está registrado.");
        return $response->withStatus(400);
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $email, $passwordHash]);

    return $response->withHeader('Location', '/login')->withStatus(302);
})->add(GuestMiddleware::class);

// Muestra el formulario de login (GET)
$app->get("/login", function ($request, $response) use ($renderer) {
    return view($renderer, $response, "auth/login.php");
})->add(GuestMiddleware::class);

// Procesa el formulario de login (POST)
$app->post("/login", function ($request, $response) use ($renderer) {
    $parsedBody = $request->getParsedBody();
    
    $email = $parsedBody['email'] ?? '';
    $password = $parsedBody['password'] ?? '';

    if (empty($email) || empty($password)) {
        $response->getBody()->write("Por favor, completa todos los campos.");
        return $response->withStatus(400);
    }

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    $stmt = $db->prepare("SELECT id, nombre, password_hash FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        $response->getBody()->write("Credenciales incorrectas.");
        return $response->withStatus(401);
    }

    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];

    return $response->withHeader('Location', '/dashboard')->withStatus(302);
})->add(GuestMiddleware::class);

// Ruta para Cerrar Sesión (Soporta GET y POST)
$app->map(['GET', 'POST'], '/logout', function ($request, $response) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    session_destroy();

    return $response->withHeader('Location', '/')->withStatus(302);
});