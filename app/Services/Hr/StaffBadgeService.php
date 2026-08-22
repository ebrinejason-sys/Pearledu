<?php

namespace App\Services\Hr;

use App\Models\School;
use App\Models\StaffBadge;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

class StaffBadgeService
{
    public function issue(School $school, User $user): StaffBadge
    {
        $badge = StaffBadge::query()
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->first();

        if ($badge && $badge->isActive()) {
            return $badge;
        }

        $code = $this->uniqueCode($school);

        if ($badge) {
            $badge->forceFill([
                'code' => $code,
                'issued_at' => now(),
                'revoked_at' => null,
            ])->save();

            return $badge->fresh();
        }

        return StaffBadge::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'code' => $code,
            'issued_at' => now(),
        ]);
    }

    public function findActive(School $school, string $code): ?StaffBadge
    {
        $normalized = strtoupper(trim($code));

        return StaffBadge::query()
            ->where('school_id', $school->id)
            ->where('code', $normalized)
            ->whereNull('revoked_at')
            ->first();
    }

    public function qrSvg(string $code): string
    {
        $renderer = new ImageRenderer(new RendererStyle(180), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($code);
    }

    private function uniqueCode(School $school): string
    {
        do {
            $code = 'PE'.str_pad((string) $school->id, 4, '0', STR_PAD_LEFT).Str::upper(Str::random(8));
        } while (StaffBadge::query()->where('school_id', $school->id)->where('code', $code)->exists());

        return $code;
    }
}
