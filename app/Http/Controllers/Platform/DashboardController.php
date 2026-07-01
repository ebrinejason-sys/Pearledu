<?php
namespace App\Http\Controllers\Platform;
use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller {
    public function index() {
        $stats = [
            'schools'   => School::count(),
            'active'    => School::where('status','active')->count(),
            'learners'  => DB::table('students')->whereNull('deleted_at')->count(),
            'operators' => User::where('is_platform', true)->count(),
            'sms_sent'  => SmsMessage::where('status','sent')->count(),
        ];
        $schools = School::orderByDesc('id')->limit(10)->get();
        return view('platform.dashboard', compact('stats','schools'));
    }
}
