<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'description',
        'model_type',
        'model_id',
        'ip_address',
        'user_agent',
        'properties',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * The user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The related model (polymorphic).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * Helper to create a log entry.
     */
    public static function logAction(
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $properties = null,
        ?Request $request = null
    ): self {
        $userId = null;
        $ipAddress = null;
        $userAgent = null;

        if ($request) {
            $userId = $request->user()?->id;
            $ipAddress = $request->ip();
            $userAgent = substr($request->userAgent() ?? '', 0, 500);
        } elseif (auth()->check()) {
            $userId = auth()->id();
        }

        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
            'model_type' => $subject ? get_class($subject) : null,
            'model_id' => $subject?->getKey(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'properties' => $properties,
            'created_at' => now(),
        ]);
    }
}
