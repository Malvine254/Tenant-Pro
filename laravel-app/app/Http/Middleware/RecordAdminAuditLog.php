<?php

namespace App\Http\Middleware;

use App\Models\AdminAuditLog;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class RecordAdminAuditLog
{
    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $statusCode = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : ($exception instanceof ValidationException ? $exception->status : 500);
            $this->record($request, $actor, $statusCode);
            throw $exception;
        }

        if (! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            $this->record($request, $actor, $response->getStatusCode());
        }

        return $response;
    }

    private function record(Request $request, $actor, int $statusCode): void
    {
        try {
            [$targetType, $targetId] = $this->resolveTarget($request);

            AdminAuditLog::create([
                'actor_id' => $actor?->getAuthIdentifier(),
                'actor_role' => $actor?->role?->name,
                'action' => $request->route()?->getName() ?? 'admin.unknown',
                'method' => $request->method(),
                'path' => '/'.ltrim($request->path(), '/'),
                'status_code' => $statusCode,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'request_id' => (string) $request->attributes->get('request_id', 'unavailable'),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);
        } catch (Throwable $exception) {
            Log::warning('Unable to persist admin audit log.', [
                'route' => $request->route()?->getName(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveTarget(Request $request): array
    {
        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            if ($parameter instanceof Model) {
                return [class_basename($parameter), (string) $parameter->getRouteKey()];
            }
        }

        return [null, null];
    }
}
