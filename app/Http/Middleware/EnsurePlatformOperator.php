<?php
namespace App\Http\Middleware;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsurePlatformOperator {
    public function __construct(private TenantContext $context) {}
    public function handle(Request $request, Closure $next): Response {
        $u = $request->user();
        abort_unless($u && $u->isPlatformOperator(), 403);
        if ($id = $request->session()->get('platform.entered_school_id')) {
            $this->context->forPlatformInSchool((int) $id);
        } else {
            $this->context->forPlatform();
        }
        return $next($request);
    }
}
