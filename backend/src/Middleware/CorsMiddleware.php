<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;

class CorsMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        $origin = $request->getHeaderLine('Origin');
        
        // Whitelist of allowed origins
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:3000',
            'https://medi-minder-hazel.vercel.app',
        ];
        
        // Check if origin is allowed
        $isAllowed = in_array($origin, $allowedOrigins);
        
        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            $response = new SlimResponse(200);
            if ($isAllowed) {
                $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
            }
            return $response
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        // Handle actual requests
        $response = $handler->handle($request);
        
        if ($isAllowed) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        }
        
        return $response
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
}

