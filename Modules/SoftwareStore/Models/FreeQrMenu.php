<?php

namespace Modules\SoftwareStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A restaurant or café menu made with the free QR menu maker, shown at /menu/{code}.
 */
class FreeQrMenu extends Model
{
    protected $fillable = ['code', 'edit_token_hash', 'data', 'ip'];

    protected $hidden = ['edit_token_hash', 'ip'];

    protected $casts = ['data' => 'array'];

    /** @return array{0: self, 1: string} the menu and its plain edit token (only returned once) */
    public static function createWithToken(array $data, ?string $ip): array
    {
        $token = Str::random(40);

        do {
            $code = Str::lower(Str::random(8));
        } while (self::where('code', $code)->exists());

        $menu = self::create(['code' => $code, 'edit_token_hash' => hash('sha256', $token), 'data' => $data, 'ip' => $ip]);

        return [$menu, $token];
    }

    public function tokenMatches(?string $token): bool
    {
        return is_string($token) && $token !== '' && hash_equals($this->edit_token_hash, hash('sha256', $token));
    }

    public function publicUrl(): string
    {
        return route('services.tools.menu', $this->code);
    }
}
