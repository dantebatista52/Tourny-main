<?php

namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class GuestMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si el usuario YA inició sesión e intenta ir a /login o /registro, lo mandamos al dashboard
        if (isset($_SESSION['usuario_id'])) {
            $response = new Response();
            return $response
                ->withHeader('Location', '/dashboard')
                ->withStatus(302);
        }

        // Si es un invitado (no logueado), se le permite ver la pantalla de login/registro
        return $handler->handle($request);
    }
}