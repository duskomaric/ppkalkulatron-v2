<?php

namespace App\Http\Middleware;

use App\Services\Diagnostics;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogDiagnosticAction
{
    public function __construct(private Diagnostics $diagnostics) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethodSafe() || $request->route()?->getName() === 'mobile.diagnostics.store') {
            return $response;
        }

        $context = [
            'action' => $request->route()?->getName(),
            'method' => $request->method(),
            'status' => $response->getStatusCode(),
            ...$this->resourceIdentifiers($request),
        ];

        if ($response->getStatusCode() >= 400 || $request->session()->has('errors') || $request->session()->has('error')) {
            $this->diagnostics->error('Application action failed', $context);
        } else {
            $this->diagnostics->debug('Application action completed', $context);
        }

        return $response;
    }

    /** @return array<string, int|string> */
    private function resourceIdentifiers(Request $request): array
    {
        $identifiers = [];

        foreach ($request->route()?->parameters() ?? [] as $name => $parameter) {
            if ($parameter instanceof Model) {
                $identifiers[$name.'_id'] = $parameter->getKey();
            } elseif (is_int($parameter) || is_string($parameter)) {
                $identifiers[$name.'_id'] = $parameter;
            }
        }

        return $identifiers;
    }
}
