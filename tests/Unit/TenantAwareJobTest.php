<?php

namespace Tests\Unit;

use App\Jobs\Concerns\TenantAware;
use App\Jobs\Middleware\RestoreTenantContext;
use App\Models\School;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAwareJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_middleware_re_pins_school_context(): void
    {
        $this->seed();
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        $ctx = app(TenantContext::class);
        $ctx->clear();

        $middleware = new RestoreTenantContext($school->id, false);
        $seen = null;

        $middleware->handle(new \stdClass, function () use ($ctx, &$seen) {
            $seen = $ctx->schoolId();

            return 'ok';
        });

        $this->assertSame($school->id, $seen);
        $this->assertNull($ctx->schoolId());
    }

    public function test_tenant_aware_trait_captures_and_exposes_middleware(): void
    {
        $this->seed();
        $school = School::where('slug', 'like', 'pearledu%')->firstOrFail();
        app(TenantContext::class)->forSchool($school->id);

        $job = new class {
            use TenantAware;

            public function __construct()
            {
                $this->captureTenantContext();
            }
        };

        $this->assertSame($school->id, $job->tenantSchoolId);
        $this->assertFalse($job->tenantIsPlatform);

        $middleware = $job->middleware();
        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(RestoreTenantContext::class, $middleware[0]);
        $this->assertSame($school->id, $middleware[0]->schoolId);
    }
}
