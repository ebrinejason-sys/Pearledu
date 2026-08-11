<?php

namespace App\Http\Controllers;

use App\Services\Emis\EmisExportService;
use App\Services\Tenancy\TenantContext;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmisController extends Controller
{
    public function export(TenantContext $ctx, EmisExportService $emis): StreamedResponse
    {
        $school = $ctx->school();
        abort_unless($school, 404);
        abort_unless($school->emisEnabled(), 404);

        $rows = $emis->studentsCsvRows($school->id);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'full_name', 'class', 'emis_number', 'status']);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'emis-students-'.$school->slug.'.csv');
    }
}
