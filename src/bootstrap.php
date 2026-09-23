<?php

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/database/database.php';
require __DIR__ .

// Cargar variables de entorno desde el .env
Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$env = $_ENV["APP_ENV"] ?? "prod";
$allowedEnvs = ["dev", "prod"];

if (!in_array($env, $allowedEnvs, true)) {
  throw new RuntimeException("APP_ENV inválido: $env");
}

$debug = $env === "dev";

// Crear la aplicacion de Slim
$app = AppFactory::create();
// Activa los errores detallados en el navegador
$app->addErrorMiddleware(true, true, true);

// Crear el motor de plantillas
$renderer = new PhpRenderer(
  templatePath: __DIR__ . "/views",
  attributes: ["title" => "Tourny - Gestor de torneos personalizados"],
);

// MIDDLEWARES

require __DIR__ . "/middlewares/authMiddleware.php";
require __DIR__ . "/middlewares/errorMiddleware.php";
require __DIR__ . "/middlewares/guestMiddleware.php";


// ==========================================
// 1. RUTA PÚBLICA / LANDING
// ==========================================

// Ruta de la Landing Page (GET /)
$app->get("/", function ($request, $response) use ($renderer) {
    // 2. Retornamos la vista independiente pasando todas las variables necesarias
    return viewStandalone($renderer, $response, "landing.php", [
        "titulo" => "Bienvenido a Tourny",
        "isLoggedIn" => $isLoggedIn
    ]);
})
->add("guestMiddleware.php");

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
})->add("guestMiddleware.php");


// ==========================================
// 2. AUTENTICACIÓN (AUTH)
// ==========================================

// ==========================================
// RUTAS DE REGISTRO
// ==========================================

// Muestra el formulario de registro (GET)
$app->get("/registro", function ($request, $response) use ($renderer) {
    return view($renderer, $response, "auth/registro.php");
})
->add("guestMiddleware.php");;

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

    // Instanciamos la conexión oficial del proyecto
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // Verificar si el email ya existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $response->getBody()->write("El email ya está registrado.");
        return $response->withStatus(400);
    }

    // Encriptar contraseña de manera segura
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Insertar en la base de datos
    $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $email, $passwordHash]);

    // Redireccionar al login tras registrarse con éxito
    return $response->withHeader('Location', '/login')->withStatus(302);
})->add("guestMiddleware.php");

// ==========================================
// RUTAS DE LOGIN
// ==========================================

// Muestra el formulario de login (GET)
$app->get("/login", function ($request, $response) use ($renderer) {
    return view($renderer, $response, "auth/login.php");
})
->add("guestMiddleware.php");

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

    // Buscar al usuario por email
    $stmt = $db->prepare("SELECT id, nombre, password_hash FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    // Verificar si existe y si la contraseña coincide con el hash guardado
    if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
        $response->getBody()->write("Credenciales incorrectas. No se encuentran los datos ingresados. Asegurate de haberte registrado y de ingresar el email y contraseña correctos.");
        return $response->withStatus(401);
    }

    // Guardamos los datos clave del usuario en la sesión global
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nombre'] = $usuario['nombre'];

    // Redireccionamos al dashboard (Panel de control del organizador)
    return $response->withHeader('Location', '/dashboard')->withStatus(302);
})
->add("authMiddleware.php");

// Ruta para Cerrar Sesión (Soporta GET y POST)
$app->map(['GET', 'POST'], '/logout', function ($request, $response) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Destruimos todas las variables y la sesión global
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

    // Redireccionamos al login
    return $response->withHeader('Location', '/')->withStatus(302);
});

// ==========================================
// 3. DASHBOARD PRINCIPAL
// ==========================================

// Muestra el Panel de Control con la lista de torneos (GET)
$app->get("/dashboard", function ($request, $response) use ($renderer) {
    $idOrganizador = $_SESSION['usuario_id'];

    // Obtenemos la conexión e instanciamos la consulta
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // Buscamos todos los torneos que le pertenecen a este organizador
    $stmt = $db->prepare("SELECT * FROM torneos WHERE id_organizador = ? ORDER BY created_at DESC");
    $stmt->execute([$idOrganizador]);
    $torneos = $stmt->fetchAll();

    // Renderizamos la vista pasándole el nombre del usuario Y la lista de torneos
    return view($renderer, $response, "dashboard/dashboard.php", [
        "nombre" => $_SESSION['usuario_nombre'],
        "torneos" => $torneos
    ]);
})
->add("guestMiddleware.php");

// ==========================================
// 4. TORNEOS (ZONA PRIVADA)
// ==========================================

// ==========================================
// LISTADO DE TORNEOS DE UN USUARIO
// ==========================================

// Ruta GET /torneos (Lista todos los torneos del organizador logueado)
$app->get("/torneos", function ($request, $response) use ($renderer) {
    $idOrganizador = $_SESSION['usuario_id'];

    // Conexión a la base de datos
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // Consultar todos los torneos que pertenecen a este usuario
    $stmt = $db->prepare("SELECT * FROM torneos WHERE id_organizador = ? ORDER BY created_at DESC");
    $stmt->execute([$idOrganizador]);
    $torneos = $stmt->fetchAll();

    // Renderizar la vista pasando el listado de torneos
    return view($renderer, $response, "torneos/index.php", [
        "torneos" => $torneos
    ]);
})
->add("authMiddleware.php");

// Formulario para crear un torneo (GET)
$app->get("/torneos/create", function ($request, $response) use ($renderer) {
    return view($renderer, $response, "torneos/create.php");
})
->add("authMiddleware.php");

// Procesa la creación del torneo (POST)
$app->post("/torneos/create", function ($request, $response) use ($renderer) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // CONTROL DE ACCESO
    if (!isset($_SESSION['usuario_id'])) {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }

    $parsedBody = $request->getParsedBody();
    $nombreTorneo = trim($parsedBody['nombre'] ?? '');
    $formato = $parsedBody['formato'] ?? 'liga';
    $idOrganizador = $_SESSION['usuario_id'];

    // 1. Generar el SLUG automáticamente limpiando el nombre
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nombreTorneo), '-'));

    // Validación básica
    if (empty($nombreTorneo) || empty($formato) || empty($slug)) {
        $response->getBody()->write("Todos los campos son obligatorios y el nombre debe ser válido.");
        return $response->withStatus(400);
    }

    // Conexión usando la clase oficial del proyecto
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // 2. Insertar el torneo incluyendo el SLUG generado y el ID del organizador
    $stmt = $db->prepare("INSERT INTO torneos (nombre, slug, formato, id_organizador) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nombreTorneo, $slug, $formato, $idOrganizador]);

    // Obtenemos el ID del torneo creado
    $idTorneo = $db->lastInsertId();

    // Redireccionamos a la gestión de equipos
    return $response->withHeader('Location', "/torneos/{$idTorneo}/equipos")->withStatus(302);
});

// Eliminar un torneo (POST)

$app->post("/torneos/{id}/delete", function ($request, $response, $args) use ($renderer) {

    $idTorneo = $args['id'];
    $idOrganizador = $_SESSION['usuario_id'];

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // VALIDACIÓN DE PROPIEDAD: Asegurar que el torneo pertenezca al usuario logueado
    $stmt = $db->prepare("SELECT id FROM torneos WHERE id = ? AND id_organizador = ?");
    $stmt->execute([$idTorneo, $idOrganizador]);
    
    if (!$stmt->fetch()) {
        $response->getBody()->write("No tienes permisos para eliminar este torneo o no existe.");
        return $response->withStatus(403);
    }

    // Proceder a eliminar el torneo
    $stmtDelete = $db->prepare("DELETE FROM torneos WHERE id = ?");
    $stmtDelete->execute([$idTorneo]);

    // Redireccionar al Dashboard tras la eliminación
    return $response->withHeader('Location', '/dashboard')->withStatus(302);
})
->add("authMiddleware.php");

// ==========================================
// 4.5 GESTIÓN DE EQUIPOS (ZONA PRIVADA)
// ==========================================

// Muestra el panel de control de equipos para un torneo (GET)
$app->get("/torneos/{id}/equipos", function ($request, $response, $args) use ($renderer) {
    
    $idTorneo = $args['id']; // Capturamos el ID del torneo desde la URL

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // Verificamos que el torneo realmente exista
    $stmt = $db->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmt->execute([$idTorneo]);
    $torneo = $stmt->fetch();

    if (!$torneo) {
        $response->getBody()->write("El torneo no existe.");
        return $response->withStatus(404);
    }

    // Traemos de la base de datos los equipos que ya pertenezcan a este torneo
    $stmt = $db->prepare("SELECT * FROM equipos WHERE id_torneo = ?");
    $stmt->execute([$idTorneo]);
    $equipos = $stmt->fetchAll();

    // Renderizamos la vista pasándole los datos dinámicos
    return view($renderer, $response, "torneos/equipos.php", [
        "torneo" => $torneo,
        "equipos" => $equipos
    ]);
})
->add("authMiddleware.php");

// Procesa el formulario para añadir un nuevo equipo (POST)
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

    // 1. VALIDACIÓN: Nombre duplicado
    $stmtCheck = $db->prepare("SELECT id FROM equipos WHERE id_torneo = ? AND LOWER(nombre) = LOWER(?)");
    $stmtCheck->execute([$idTorneo, $nombreEquipo]);

    if ($stmtCheck->fetch()) {
        $response->getBody()->write("Ya existe un equipo con el nombre '{$nombreEquipo}' en este torneo.");
        return $response->withStatus(400);
    }

    // 2. GENERAR CÓDIGO DE INVITACIÓN ÚNICO
    $codigoInvitacion = generarCodigoInvitacion(6);

    // 3. INSERTAR EQUIPO INCLUYENDO EL CÓDIGO
    $stmt = $db->prepare("INSERT INTO equipos (id_torneo, nombre, codigo_invitacion) VALUES (?, ?, ?)");
    $stmt->execute([$idTorneo, $nombreEquipo, $codigoInvitacion]);

    return $response->withHeader('Location', "/torneos/{$idTorneo}/equipos")->withStatus(302);
})
->add("authMiddleware.php");

// Procesa la eliminación de un equipo (POST)
$app->post("/torneos/{torneo_id}/equipos/{equipo_id}/delete", function ($request, $response, $args) use ($renderer) {
        
    $idTorneo = $args['torneo_id'];
    $idEquipo = $args['equipo_id'];
    $idOrganizador = $_SESSION['usuario_id'];

    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // SEGURIDAD: Verificar que el torneo pertenezca al usuario logueado
    $stmtCheck = $db->prepare("SELECT id FROM torneos WHERE id = ? AND id_organizador = ?");
    $stmtCheck->execute([$idTorneo, $idOrganizador]);

    if (!$stmtCheck->fetch()) {
        $response->getBody()->write("No tienes permisos para realizar esta acción.");
        return $response->withStatus(403);
    }

    // Proceder a eliminar el equipo
    $stmtDelete = $db->prepare("DELETE FROM equipos WHERE id = ? AND id_torneo = ?");
    $stmtDelete->execute([$idEquipo, $idTorneo]);

    // Redireccionar nuevamente al panel de equipos de este torneo
    return $response->withHeader('Location', "/torneos/{$idTorneo}/equipos")->withStatus(302);
})
->add("authMiddleware.php");

// Muestra el formulario para ingresar el código de fichaje
$app->get("/equipos/unirse", function ($request, $response) use ($renderer) {
    return view($renderer, $response, "equipos/unirse.php");
})
->add("authMiddleware.php");

// Procesa el fichaje de un jugador a un equipo mediante código de invitación
$app->post("/equipos/unirse", function ($request, $response) use ($renderer) {
    
    // Suponiendo que guardas al usuario logueado en la sesión
    $idUsuario = $_SESSION['user_id'] ?? null;

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

    // 1. Buscar el equipo correspondiente al código
    $stmt = $db->prepare("SELECT id, nombre, id_torneo FROM equipos WHERE codigo_invitacion = ?");
    $stmt->execute([$codigo]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$equipo) {
        $_SESSION['flash_error'] = "El código ingresado no es válido o venció.";
        return $response->withHeader('Location', '/equipos/unirse')->withStatus(302);
    }

    // 2. Validar que el jugador no esté ya en este equipo (o en la tabla pivot/relación)
    $stmtCheck = $db->prepare("SELECT id FROM equipo_jugadores WHERE id_equipo = ? AND id_usuario = ?");
    $stmtCheck->execute([$equipo['id'], $idUsuario]);

    if ($stmtCheck->fetch()) {
        $_SESSION['flash_error'] = "Ya formás parte de este equipo.";
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }

    // 3. Registrar al jugador en el equipo
    $stmtInsert = $db->prepare("INSERT INTO equipo_jugadores (id_equipo, id_usuario, fecha_union) VALUES (?, ?, NOW())");
    $stmtInsert->execute([$equipo['id'], $idUsuario]);

    $_SESSION['flash_success'] = "¡Te has unido con éxito a " . htmlspecialchars($equipo['nombre']) . "!";
    return $response->withHeader('Location', '/dashboard')->withStatus(302);
})
->add("authMiddleware.php");

// ==========================================
// 5. PARTIDOS / FIXTURE (ZONA PRIVADA)
// ==========================================

// Genera el fixture automáticamente (Soporta Liga y Eliminatoria)
$app->post("/torneos/{id}/fixture/generar", function ($request, $response, $args) {
    
    $idTorneo = $args['id'];
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // 1. Obtener datos del torneo (para verificar el formato)
    $stmtTorneo = $db->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmtTorneo->execute([$idTorneo]);
    $torneo = $stmtTorneo->fetch();

    if (!$torneo) {
        $response->getBody()->write("Torneo no encontrado.");
        return $response->withStatus(404);
    }

    // 2. Obtener todos los equipos cargados
    $stmt = $db->prepare("SELECT id FROM equipos WHERE id_torneo = ?");
    $stmt->execute([$idTorneo]);
    $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $cantEquipos = count($equipos);

    // 3. Validación de lógica según formato
    if ($torneo['formato'] === 'eliminatoria') {
        // En eliminatoria directa requerimos potencias de 2 válidas: 2, 4, 8, 16, 32
        $potenciasValidas = [2, 4, 8, 16, 32];
        
        if (!in_array($cantEquipos, $potenciasValidas, true)) {
            $response->getBody()->write("Para el formato de Eliminación Directa debes registrar exactamente 2, 4, 8, 16 o 32 equipos. Actualmente tienes {$cantEquipos}.");
            return $response->withStatus(400);
        }
    } else {
        // Formato Liga
        if ($cantEquipos < 2) {
            $response->getBody()->write("Necesitas al menos 2 equipos para generar el fixture de una liga.");
            return $response->withStatus(400);
        }
    }

    // 4. Limpiar partidos anteriores
    $stmtDelete = $db->prepare("DELETE FROM partidos WHERE id_torneo = ?");
    $stmtDelete->execute([$idTorneo]);

    // 5. GENERACIÓN SEGÚN FORMATO
    if ($torneo['formato'] === 'eliminatoria') {
        // Mezclamos los equipos para que los cruces sean aleatorios
        shuffle($equipos);

        // Creamos la primera ronda (Fecha 1: Octavos, Cuartos, Semis, etc.)
        for ($i = 0; $i < $cantEquipos; $i += 2) {
            $local = $equipos[$i];
            $visitante = $equipos[$i + 1];

            $stmtInsert = $db->prepare("INSERT INTO partidos (id_torneo, id_equipo_local, id_equipo_visitante, fecha_numero) VALUES (?, ?, ?, 1)");
            $stmtInsert->execute([$idTorneo, $local, $visitante]);
        }
    } else {
        // Algoritmo Round-Robin para LIGA
        if ($cantEquipos % 2 !== 0) {
            $equipos[] = null; // Fecha libre
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
})
->add("authMiddleware.php");;

// Muestra el Fixture de un torneo
$app->get("/torneos/{id}/fixture", function ($request, $response, $args) use ($renderer) {

    $idTorneo = $args['id'];
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // Consultar torneo
    $stmtTorneo = $db->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmtTorneo->execute([$idTorneo]);
    $torneo = $stmtTorneo->fetch();

    // Usamos LEFT JOIN para que traiga también las fechas libres (donde visitante o local son NULL)
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
})
->add("authMiddleware.php");;

// Actualizar el resultado de un partido (POST)
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

    // Obtener id_torneo para la redirección
    $stmtPartido = $db->prepare("SELECT id_torneo FROM partidos WHERE id = ?");
    $stmtPartido->execute([$idPartido]);
    $partido = $stmtPartido->fetch();

    if (!$partido) {
        $response->getBody()->write("Partido no encontrado.");
        return $response->withStatus(404);
    }

    // Actualizar resultado
    $stmt = $db->prepare("UPDATE partidos SET goles_local = ?, goles_visitante = ?, estado = 'finalizado' WHERE id = ?");
    $stmt->execute([$golesLocal, $golesVisitante, $idPartido]);

    return $response->withHeader('Location', "/torneos/{$partido['id_torneo']}/fixture")->withStatus(302);
})
->add("authMiddleware.php");

// Muestra la Tabla de Posiciones calculada dinámicamente
$app->get("/torneos/{id}/tabla", function ($request, $response, $args) use ($renderer) {

    $idTorneo = $args['id'];
    $databaseInstancia = new Database();
    $db = $databaseInstancia->getConnection();

    // 1. Obtener datos del torneo
    $stmtTorneo = $db->prepare("SELECT * FROM torneos WHERE id = ?");
    $stmtTorneo->execute([$idTorneo]);
    $torneo = $stmtTorneo->fetch();

    if (!$torneo) {
        $response->getBody()->write("Torneo no encontrado.");
        return $response->withStatus(404);
    }

    // 2. Obtener todos los equipos del torneo
    $stmtEquipos = $db->prepare("SELECT id, nombre FROM equipos WHERE id_torneo = ?");
    $stmtEquipos->execute([$idTorneo]);
    $equipos = $stmtEquipos->fetchAll();

    // Estructura inicial para acumular estadísticas por equipo
    $tabla = [];
    foreach ($equipos as $eq) {
        $tabla[$eq['id']] = [
            'nombre' => $eq['nombre'],
            'pj' => 0, // Partidos Jugados
            'pg' => 0, // Ganados
            'pe' => 0, // Empatados
            'pp' => 0, // Perdidos
            'gf' => 0, // Goles a Favor
            'gc' => 0, // Goles en Contra
            'dg' => 0, // Diferencia de Gol
            'pts' => 0 // Puntos
        ];
    }

    // 3. Obtener partidos finalizados con marcador cargado
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

    // 4. Procesar resultados y sumar estadísticas
    foreach ($partidos as $p) {
        $idLocal = $p['id_equipo_local'];
        $idVisitante = $p['id_equipo_visitante'];
        $golesL = (int)$p['goles_local'];
        $golesV = (int)$p['goles_visitante'];

        if (!isset($tabla[$idLocal]) || !isset($tabla[$idVisitante])) {
            continue;
        }

        // Partidos jugados
        $tabla[$idLocal]['pj']++;
        $tabla[$idVisitante]['pj']++;

        // Goles a favor y en contra
        $tabla[$idLocal]['gf'] += $golesL;
        $tabla[$idLocal]['gc'] += $golesV;
        $tabla[$idVisitante]['gf'] += $golesV;
        $tabla[$idVisitante]['gc'] += $golesL;

        // Evaluación del resultado
        if ($golesL > $golesV) {
            // Gana Local
            $tabla[$idLocal]['pg']++;
            $tabla[$idLocal]['pts'] += 3;
            $tabla[$idVisitante]['pp']++;
        } elseif ($golesV > $golesL) {
            // Gana Visitante
            $tabla[$idVisitante]['pg']++;
            $tabla[$idVisitante]['pts'] += 3;
            $tabla[$idLocal]['pp']++;
        } else {
            // Empate
            $tabla[$idLocal]['pe']++;
            $tabla[$idLocal]['pts'] += 1;
            $tabla[$idVisitante]['pe']++;
            $tabla[$idVisitante]['pts'] += 1;
        }
    }

    // 5. Calcular diferencia de gol y ordenar la tabla
    foreach ($tabla as &$e) {
        $e['dg'] = $e['gf'] - $e['gc'];
    }
    unset($e);

    // Criterio de ordenamiento: Puntos DESC, Diferencia de Gol DESC, Goles a Favor DESC
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
})
->add("authMiddleware.php");

$app->addErrorMiddleware($debug, true, true);
$app->add("errorMiddleware.php");

return $app;
