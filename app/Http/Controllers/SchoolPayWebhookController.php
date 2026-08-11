<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Services\SchoolPay\SchoolPayPaymentService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Public SchoolPay endpoints (CSRF-exempt).
 * Schools register these URLs in the SchoolPay portal / adhoc callBackUrl.
 *
 * Route params are raw school ids (not implicit model binding) so we can
 * elevate to platform scope briefly — public requests have no tenant GUC yet.
 */
class SchoolPayWebhookController extends Controller
{
    public function __construct(
        private SchoolPayPaymentService $schoolPay,
        private TenantContext $tenancy,
    ) {}

    /** Adhoc payment callBackUrl target. */
    public function adhocCallback(Request $request, int $school)
    {
        $model = $this->resolveSchool($school);
        abort_unless($model->schoolPayConfigured(), 404);

        try {
            $this->schoolPay->handleAdhocCallback($model, $request->all());
        } catch (ValidationException $e) {
            Log::warning('SchoolPay adhoc callback rejected', [
                'school_id' => $model->id,
                'errors' => $e->errors(),
            ]);

            return response()->json(['ok' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            Log::error('SchoolPay adhoc callback failed', [
                'school_id' => $model->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }

    /** SchoolPay portal webhook (SCHOOL_FEES / OTHER_FEES). */
    public function notify(Request $request, int $school)
    {
        $model = $this->resolveSchool($school);
        abort_unless($model->schoolPayConfigured(), 404);

        try {
            $this->schoolPay->handleWebhook($model, $request->all());
        } catch (ValidationException $e) {
            Log::warning('SchoolPay webhook rejected', [
                'school_id' => $model->id,
                'errors' => $e->errors(),
            ]);

            return response()->json(['ok' => false, 'errors' => $e->errors()], 422);
        } catch (Throwable $e) {
            Log::error('SchoolPay webhook failed', [
                'school_id' => $model->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['ok' => false], 500);
        }

        // SchoolPay only checks HTTP 200.
        return response()->json(['ok' => true]);
    }

    private function resolveSchool(int $schoolId): School
    {
        $this->tenancy->forPlatform();
        $school = School::query()->findOrFail($schoolId);
        $this->tenancy->forSchool((int) $school->id);

        return $school;
    }
}
