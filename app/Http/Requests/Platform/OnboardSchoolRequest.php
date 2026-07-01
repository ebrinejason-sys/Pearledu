<?php
namespace App\Http\Requests\Platform;
use App\Models\SchoolOffering;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class OnboardSchoolRequest extends FormRequest {
    public function authorize(): bool { return $this->user()?->isPlatformOperator() ?? false; }
    public function rules(): array {
        return [
            'name' => ['required','string','max:160'],
            'district' => ['nullable','string','max:120'],
            'emis_number' => ['nullable','string','max:60','unique:schools,emis_number'],
            'theme' => ['nullable', Rule::in(array_keys(config('themes.themes')))],
            'levels' => ['required','array','min:1'],
            'levels.*' => ['required', Rule::in(SchoolOffering::LEVELS)],
            'admin.full_name' => ['required','string','max:160'],
            'admin.email' => ['required_without:admin.phone','nullable','email','max:160'],
            'admin.phone' => ['required_without:admin.email','nullable','string','max:20'],
        ];
    }
}
