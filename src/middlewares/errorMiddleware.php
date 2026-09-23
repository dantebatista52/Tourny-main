<?php

use Psr\Http\Message\ServerRequestInterface;

// Retornamos directamente la función anónima (Closure)
return function (
    ServerRequestInterface $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) {
    
    // 1. Ruta del archivo txt (como ya estamos en src/middlewares, usamos __DIR__)
    $logDirectory = __DIR__;
    $logFilePath = $logDirectory . '/errores.txt';

    if (!file_exists($logDirectory)) {
        mkdir($logDirectory, 0777, true);
    }

    // 2. Formato del mensaje de error
    $date = date('Y-m-d H:i:s');
    $errorMessage = "==================================================" . PHP_EOL;
    $errorMessage .= "FECHA: [{$date}]" . PHP_EOL;
    $errorMessage .= "RUTA HTTP: " . $request->getMethod() . ' ' . $request->getUri()->getPath() . PHP_EOL;
    $errorMessage .= "MENSAJE: " . $exception->getMessage() . PHP_EOL;
    $errorMessage .= "ARCHIVO: " . $exception->getFile() . " (Línea " . $exception->getLine() . ")" . PHP_EOL;
    $errorMessage .= "TRAZA:" . PHP_EOL . $exception->getTraceAsString() . PHP_EOL;
    $errorMessage .= "==================================================" . PHP_EOL . PHP_EOL;

    // 3. Escribir el log en el archivo txt
    file_put_contents($logFilePath, $errorMessage, FILE_APPEND | LOCK_EX);

    // 4. Generar la respuesta HTTP PSR-7 nativa
    $response = new \Slim\Psr7\Response();
    
    if ($displayErrorDetails) {
        $response->getBody()->write("<h1>Ocurrió un error en la aplicación</h1><p>" . htmlspecialchars($exception->getMessage()) . "</p>");
    } else {
        $response->getBody()->write("<h1>500 Internal Server Error</h1><p>Ocurrió un error inesperado. El equipo técnico ya fue notificado.</p>");
    }

    return $response->withStatus(500);
};