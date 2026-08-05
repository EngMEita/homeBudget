<?php

namespace App\Http\Requests;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class RestoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');
        if (! $household instanceof Household || ! $this->user()?->can('export', $household)) {
            return false;
        }

        $confirmed = DB::table('cache')
            ->where('key', 'password-confirmed:'.$this->user()->id)
            ->where('expiration', '>', now()->timestamp)
            ->exists();

        return $confirmed;
    }

    public function rules(): array
    {
        return ['backup_log_id' => ['required', 'integer', 'exists:backup_logs,id']];
    }
}
