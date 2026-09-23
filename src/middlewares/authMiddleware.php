<?php

namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Iniciar sesión si aún no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si no existe la sesión del usuario, redirigir al login
        if (!isset($_SESSION['usuario_id'])) {
            $response = new Response();
            return $response
                ->withHeader('Location', '/login')
                ->withStatus(302);
        }

        // Si está autenticado, continuar hacia el controlador
        return $handler->handle($request);
    }
}