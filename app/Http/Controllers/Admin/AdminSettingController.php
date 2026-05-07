<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    /**
     * GET /api/admin/settings
     * Get all settings, grouped.
     */
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get();

        $grouped = $settings->groupBy('group')->map(function ($items) {
            return $items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'key' => $item->key,
                    'value' => $item->typed_value,
                    'type' => $item->type,
                    'description' => $item->description,
                ];
            });
        });

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * GET /api/admin/settings/{group}
     * Get settings for a specific group.
     */
    public function showGroup($group)
    {
        $settings = Setting::where('group', $group)->orderBy('key')->get();

        $result = $settings->map(function ($item) {
            return [
                'id' => $item->id,
                'key' => $item->key,
                'value' => $item->typed_value,
                'type' => $item->type,
                'description' => $item->description,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * PUT /api/admin/settings
     * Bulk update settings.
     * Expected: { "settings": { "key1": "value1", "key2": "value2" } }
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $updated = [];

        foreach ($validated['settings'] as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if ($setting) {
                $oldValue = $setting->typed_value;
                $setting->setTypedValue($value);
                $setting->save();
                $updated[$key] = [
                    'old' => $oldValue,
                    'new' => $setting->typed_value,
                ];
            }
        }

        ActivityLog::logAction(
            'settings.updated',
            'Pengaturan sistem diperbarui: ' . implode(', ', array_keys($updated)),
            null,
            $updated,
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $updated,
            'message' => 'Pengaturan berhasil diperbarui.',
        ]);
    }
}
