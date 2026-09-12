<?php

namespace App\Services;

use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SystemLogService
{
    public function record(Request $request, string $action, ?Model $target = null, array $metadata = []): SystemLog
    {
        return SystemLog::create([
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'metadata' => $metadata ?: null,
            'ip_address' => $request->ip(),
        ]);
    }
}
