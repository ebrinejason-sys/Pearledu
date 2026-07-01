<?php
namespace App\Http\Controllers;
use App\Services\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;

class AppHomeController extends Controller {
    public function index(TenantContext $context) {
        return view('app.home', [
            'school' => $context->school(),
            'permissions' => $context->schoolId() ? Auth::user()->permissionsForSchool($context->schoolId()) : [],
        ]);
    }
}
