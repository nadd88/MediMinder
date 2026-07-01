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
        
        if ($request->getMethod() === 'OPTIONS') {
            $response = new SlimResponse(204);
            return $this->addCorsHeaders($response, $origin);
        }

        $response = $handler->handle($request);
        return $this->addCorsHeaders($response, $origin);
    }

    private function addCorsHeaders(Response $response, string $origin = ''): Response
    {
        // Whitelist of allowed origins
        $allowedOrigins = [
            'http://localhost:5173',
            'http://localhost:3000',
            'https://medi-minder-hazel.vercel.app',
            'https://medi-minder-fkr7kbh0n-nadd88s-projects.vercel.app'
        ];
        
        // Check if origin is allowed
        $allowOrigin = in_array($origin, $allowedOrigins) ? $origin : '';
        
        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Vary', 'Origin');
    }
}
