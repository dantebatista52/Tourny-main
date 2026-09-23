<?php

use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

$app = AppFactory::create();

// =========================================================================
// MANEJO GLOBAL DE ERRORES CON REGISTRO EN TXT
// =========================================================================

// Instanciamos el ErrorMiddleware de Slim
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Definimos el manejador de errores personalizado
$customErrorHandler = function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    
    // 1. Definir la ruta del archivo txt dentro de /src/Middlewares
    $logDirectory = __DIR__ . '/src/Middlewares';
    $logFilePath = $logDirectory . '/errores.txt';

    // Asegurar que el directorio exista
    if (!file_exists($logDirectory)) {
        mkdir($logDirectory, 0777, true);
    }

    // 2. Dar formato al mensaje de error con fecha y hora
    $date = date('Y-m-d H:i:s');
    $errorMessage = "==================================================" . PHP_EOL;
    $errorMessage .= "FECHA: [{$date}]" . PHP_EOL;
    $errorMessage .= "RUTA HTTP: " . $request->getMethod() . ' ' . $request->getUri()->getPath() . PHP_EOL;
    $errorMessage .= "MENSAJE: " . $exception->getMessage() . PHP_EOL;
    $errorMessage .= "ARCHIVO: " . $exception->getFile() . " (Línea " . $exception->getLine() . ")" . PHP_EOL;
    $errorMessage .= "TRAZA:" . PHP_EOL . $exception->getTraceAsString() . PHP_EOL;
    $errorMessage .= "==================================================" . PHP_EOL . PHP_EOL;

    // 3. Escribir el error en el archivo txt (FILE_APPEND evita que se sobrescriba lo anterior)
    file_put_contents($logFilePath, $errorMessage, FILE_APPEND | LOCK_EX);

    // 4. Generar la respuesta HTTP para el navegador
    $response = $app->getResponseFactory()->createResponse();
    
    if ($displayErrorDetails) {
        // En modo desarrollo, muestra el error legible en pantalla
        $response->getBody()->write("<h1>Ocurrió un error en la aplicación</h1><p>" . htmlspecialchars($exception->getMessage()) . "</p>");
    } else {
        // En producción, muestra un mensaje genérico
        $response->getBody()->write("<h1>500 Internal Server Error</h1><p>Ocurrió un error inesperado. El equipo técnico ya fue notificado.</p>");
    }

    return $response->withStatus(500);
};

// Asignamos el manejador personalizado como el procesador predeterminado de errores
$errorMiddleware->setDefaultErrorHandler($customErrorHandler);