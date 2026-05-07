<?php

namespace App\Http\Controllers\BusinessPlan;

use App\Http\Controllers\Controller;
use App\Models\OperationalPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class OperationalPlanController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = OperationalPlan::with(['user', 'businessBackground'])
                ->where('user_id', Auth::id());

            if ($request->business_background_id) {
                $query->where('business_background_id', $request->business_background_id);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            // Sorting
            $sortBy = $request->sort_by ?? 'created_at';
            $sortOrder = $request->sort_order ?? 'desc';
            $query->orderBy($sortBy, $sortOrder);

            $data = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'meta' => [
                    'total' => $data->count(),
                    'draft_count' => $data->where('status', 'draft')->count(),
                    'completed_count' => $data->where('status', 'completed')->count(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching operational plans: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data.'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $plan = OperationalPlan::with(['user', 'businessBackground'])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$plan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Operational plan not found or unauthorized'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $plan
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching operational plan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data.'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'business_location' => 'required|string|max:255',
            'location_description' => 'nullable|string',
            'location_type' => 'required|string|max:50',
            'location_size' => 'nullable|numeric|min:0',
            'rent_cost' => 'nullable|numeric|min:0',
            'employees' => 'nullable|array',
            'operational_hours' => 'nullable|array',
            'suppliers' => 'nullable|array',
            'daily_workflow' => 'required|string',
            'workflow_diagram' => 'nullable|array',
            'workflow_image_path' => 'nullable|string|max:500',
            'equipment_needs' => 'nullable|string',
            'technology_stack' => 'nullable|string',
            'status' => 'nullable|in:draft,completed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $planData = $request->all();
            $planData['user_id'] = Auth::id();

            // Handle workflow_diagram - sesuaikan dengan struktur model
            if ($request->has('workflow_diagram') && is_array($request->workflow_diagram)) {
                // Validasi struktur workflow_diagram
                $validatedDiagram = $this->validateWorkflowDiagram($request->workflow_diagram);
                $planData['workflow_diagram'] = $validatedDiagram;
            }

            // Generate workflow diagram otomatis jika daily_workflow diisi dan tidak ada workflow_diagram
            if (!empty($request->daily_workflow) && !$request->has('workflow_diagram')) {
                $plan = new OperationalPlan($planData);
                $workflowDiagram = $plan->generateWorkflowDiagram();
                $planData['workflow_diagram'] = $workflowDiagram;
            }

            $plan = OperationalPlan::create($planData);

            // Reload dengan relationship
            $plan->load(['user', 'businessBackground']);

            return response()->json([
                'status' => 'success',
                'message' => 'Rencana operasional berhasil dibuat.',
                'data' => $plan
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating operational plan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data.'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $plan = OperationalPlan::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$plan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Operational plan not found or unauthorized'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'business_background_id' => 'sometimes|required|exists:business_backgrounds,id',
            'business_location' => 'sometimes|required|string|max:255',
            'location_description' => 'nullable|string',
            'location_type' => 'sometimes|required|string|max:50',
            'location_size' => 'nullable|numeric|min:0',
            'rent_cost' => 'nullable|numeric|min:0',
            'employees' => 'nullable|array',
            'operational_hours' => 'nullable|array',
            'suppliers' => 'nullable|array',
            'daily_workflow' => 'sometimes|required|string',
            'workflow_diagram' => 'nullable|array',
            'workflow_image_path' => 'nullable|string|max:500',
            'equipment_needs' => 'nullable|string',
            'technology_stack' => 'nullable|string',
            'status' => 'nullable|in:draft,completed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->all();

            // Handle workflow_diagram - sesuaikan dengan struktur model
            if ($request->has('workflow_diagram')) {
                if (is_array($request->workflow_diagram)) {
                    $validatedDiagram = $this->validateWorkflowDiagram($request->workflow_diagram);
                    $updateData['workflow_diagram'] = $validatedDiagram;
                } elseif ($request->workflow_diagram === null) {
                    $updateData['workflow_diagram'] = null;
                }
            }

            // Regenerate workflow diagram hanya jika daily_workflow berubah DAN tidak ada workflow_diagram yang dikirim
            $workflowChanged = $request->has('daily_workflow') &&
                $request->daily_workflow !== $plan->daily_workflow;

            if ($workflowChanged && !$request->has('workflow_diagram') && !empty($request->daily_workflow)) {
                $workflowDiagram = $plan->generateWorkflowDiagram();
                $updateData['workflow_diagram'] = $workflowDiagram;

                // Hapus workflow image lama jika ada
                if ($plan->workflow_image_path) {
                    Storage::disk('public')->delete($plan->workflow_image_path);
                    $updateData['workflow_image_path'] = null;
                }
            }

            $plan->update($updateData);

            // Reload dengan relationship
            $plan->load(['user', 'businessBackground']);

            return response()->json([
                'status' => 'success',
                'message' => 'Rencana operasional berhasil diperbarui.',
                'data' => $plan
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating operational plan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data.'
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $plan = OperationalPlan::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$plan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Operational plan not found or unauthorized'
            ], 404);
        }

        try {
            // Hapus workflow image jika ada
            if ($plan->workflow_image_path) {
                Storage::disk('public')->delete($plan->workflow_image_path);
            }

            $plan->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Rencana operasional berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting operational plan: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data.'
            ], 500);
        }
    }

    /**
     * Generate workflow diagram untuk operational plan
     */
    public function generateWorkflowDiagram($id)
    {
        try {
            $plan = OperationalPlan::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$plan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Operational plan not found or unauthorized'
                ], 404);
            }

            if (empty($plan->daily_workflow)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Daily workflow is empty. Please provide workflow description first.'
                ], 400);
            }

            $workflowDiagram = $plan->generateWorkflowDiagram();
            $plan->update(['workflow_diagram' => $workflowDiagram]);

            return response()->json([
                'status' => 'success',
                'message' => 'Workflow diagram berhasil digenerate.',
                'data' => [
                    'workflow_diagram' => $workflowDiagram,
                    'plan' => $plan
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating workflow diagram: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat generate workflow diagram.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload workflow diagram image
     */
    public function uploadWorkflowImage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'workflow_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plan = OperationalPlan::where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$plan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Operational plan not found or unauthorized'
                ], 404);
            }

            // Hapus image lama jika ada
            if ($plan->workflow_image_path) {
                Storage::disk('public')->delete($plan->workflow_image_path);
            }

            // Upload image baru
            $imagePath = $request->file('workflow_image')->store('workflow_diagrams', 'public');

            $plan->update(['workflow_image_path' => $imagePath]);

            return response()->json([
                'status' => 'success',
                'message' => 'Workflow diagram image berhasil diupload.',
                'data' => [
                    'workflow_image_url' => $plan->workflow_image_url,
                    'plan' => $plan
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading workflow image: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat upload workflow image.'
            ], 500);
        }
    }

    /**
     * Get statistics untuk operational plans
     */
    public function getStatistics(Request $request)
    {
        try {
            $stats = OperationalPlan::where('user_id', Auth::id())
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN workflow_diagram IS NOT NULL THEN 1 ELSE 0 END) as with_diagram_count,
                    SUM(CASE WHEN workflow_image_path IS NOT NULL THEN 1 ELSE 0 END) as with_image_count
                ')
                ->first();

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching operational plan statistics: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil statistics.'
            ], 500);
        }
    }

    /**
     * Validasi struktur workflow diagram
     */
    private function validateWorkflowDiagram($diagram)
    {
        // Struktur expected berdasarkan model:
        // {
        //     "steps": [...],
        //     "nodes": [...],
        //     "edges": [...],
        //     "generated_at": "..."
        // }

        $validated = [];

        if (isset($diagram['steps']) && is_array($diagram['steps'])) {
            $validated['steps'] = array_map(function ($step) {
                return [
                    'id' => $step['id'] ?? 'step_' . uniqid(),
                    'number' => $step['number'] ?? 1,
                    'description' => $step['description'] ?? '',
                    'type' => $step['type'] ?? 'process'
                ];
            }, $diagram['steps']);
        }

        if (isset($diagram['nodes']) && is_array($diagram['nodes'])) {
            $validated['nodes'] = $diagram['nodes'];
        }

        if (isset($diagram['edges']) && is_array($diagram['edges'])) {
            $validated['edges'] = $diagram['edges'];
        }

        $validated['generated_at'] = $diagram['generated_at'] ?? now()->toISOString();

        return $validated;
    }
}
