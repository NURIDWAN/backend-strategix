<?php
// app/Http/Controllers/BusinessPlan/TeamStructureController.php

namespace App\Http\Controllers\BusinessPlan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TeamStructure;
use App\Models\BusinessBackground;
use App\Services\SalarySimulationService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TeamStructureController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = TeamStructure::with(['businessBackground', 'user', 'operationalPlan'])
                ->where('user_id', Auth::id())
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc');

            if ($request->business_background_id) {
                $query->where('business_background_id', $request->business_background_id);
            }

            if ($request->operational_plan_id) {
                $query->where('operational_plan_id', $request->operational_plan_id);
            }

            $teams = $query->get();

            // Format response dengan full photo URL
            $formattedTeams = $teams->map(function ($team) {
                return $this->formatTeamResponse($team);
            });

            return response()->json([
                'status' => 'success',
                'data' => $formattedTeams
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching team structures: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch team structures'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $team = TeamStructure::with(['businessBackground', 'user', 'operationalPlan'])
                ->where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$team) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Team structure not found or unauthorized'
                ], 404);
            }

            // Format response dengan full photo URL
            $formattedTeam = $this->formatTeamResponse($team);

            return response()->json([
                'status' => 'success',
                'data' => $formattedTeam
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching team structure: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch team structure'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'operational_plan_id' => 'nullable|exists:operational_plans,id',
            'team_category' => 'required|string|max:100',
            'member_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'jobdesk' => 'nullable|string',
            'experience' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Konsisten 2MB max
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,active'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store('team_photos', 'public');

                Log::info('Team photo uploaded successfully', [
                    'original_name' => $request->file('photo')->getClientOriginalName(),
                    'stored_path' => $photoPath,
                    'full_url' => asset('storage/' . $photoPath)
                ]);
            }

            $team = TeamStructure::create([
                'user_id' => Auth::id(),
                'business_background_id' => $request->business_background_id,
                'operational_plan_id' => $request->operational_plan_id,
                'team_category' => $request->team_category,
                'member_name' => $request->member_name,
                'position' => $request->position,
                'jobdesk' => $request->jobdesk,
                'experience' => $request->experience,
                'photo' => $photoPath,
                'sort_order' => $request->sort_order ?? 0,
                'status' => $request->status ?? 'draft',
            ]);

            // Load relationships
            $team->load(['businessBackground', 'user', 'operationalPlan']);

            // Format response dengan full photo URL
            $formattedTeam = $this->formatTeamResponse($team);

            return response()->json([
                'status' => 'success',
                'message' => 'Team structure created successfully',
                'data' => $formattedTeam
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating team structure: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create team structure'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $team = TeamStructure::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$team) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team structure not found or unauthorized'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'team_category' => 'required|string|max:100',
            'member_name' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'jobdesk' => 'nullable|string',
            'experience' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Konsisten 2MB max
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,active'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = [
                'team_category' => $request->team_category,
                'member_name' => $request->member_name,
                'position' => $request->position,
                'salary' => $request->salary,
                'jobdesk' => $request->jobdesk,
                'experience' => $request->experience,
                'sort_order' => $request->sort_order ?? $team->sort_order,
                'status' => $request->status ?? $team->status,
            ];

            // Handle photo update - KONSISTEN dengan BusinessController
            if ($request->hasFile('photo')) {
                // Upload photo baru
                $photoPath = $request->file('photo')->store('team_photos', 'public');
                $updateData['photo'] = $photoPath;

                // Hapus photo lama jika ada
                if ($team->photo) {
                    Storage::disk('public')->delete($team->photo);
                }

                Log::info('Team photo updated successfully', [
                    'new_path' => $photoPath,
                    'full_url' => asset('storage/' . $photoPath)
                ]);
            } elseif ($request->has('photo') && $request->photo === '') {
                // Jika photo dikirim sebagai string kosong, hapus photo
                if ($team->photo) {
                    Storage::disk('public')->delete($team->photo);
                }
                $updateData['photo'] = null;
            } else {
                // Jika tidak ada perubahan photo, pertahankan photo lama
                unset($updateData['photo']);
            }

            $team->update($updateData);

            // Load relationships
            $team->load(['businessBackground', 'user', 'operationalPlan']);

            // Format response dengan full photo URL
            $formattedTeam = $this->formatTeamResponse($team);

            return response()->json([
                'status' => 'success',
                'message' => 'Team structure updated successfully',
                'data' => $formattedTeam
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating team structure: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update team structure'
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $team = TeamStructure::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$team) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team structure not found or unauthorized'
            ], 404);
        }

        try {
            // Hapus photo jika ada - KONSISTEN dengan BusinessController
            if ($team->photo) {
                Storage::disk('public')->delete($team->photo);
            }

            $team->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Team structure deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting team structure: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete team structure'
            ], 500);
        }
    }

    // 🔥 NEW: Method untuk upload photo saja (Opsional)
    public function uploadPhoto(Request $request, $id)
    {
        $team = TeamStructure::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$team) {
            return response()->json([
                'status' => 'error',
                'message' => 'Team structure not found or unauthorized'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Delete old photo if exists
            if ($team->photo) {
                Storage::disk('public')->delete($team->photo);
            }

            $photoPath = $request->file('photo')->store('team_photos', 'public');
            $team->update(['photo' => $photoPath]);

            // Format response dengan full photo URL
            $formattedTeam = $this->formatTeamResponse($team);

            return response()->json([
                'status' => 'success',
                'message' => 'Photo uploaded successfully',
                'data' => $formattedTeam
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading team photo: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload photo'
            ], 500);
        }
    }

    private function formatTeamResponse($team)
    {
        $formatted = $team->toArray();

        // Tambahkan full photo URL jika ada photo
        if ($team->photo) {
            $formatted['photo_url'] = asset('storage/' . $team->photo);
        } else {
            $formatted['photo_url'] = null;
        }

        // Tambahkan full org_chart_image URL jika ada di business_background
        if (isset($formatted['business_background']) && !empty($formatted['business_background']['org_chart_image'])) {
            $formatted['business_background']['org_chart_image_url'] = asset('storage/' . $formatted['business_background']['org_chart_image']);
        }

        return $formatted;
    }

    /**
     * Check existing salary simulation untuk bulan tertentu
     */
    public function checkExistingSalary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salaryService = new SalarySimulationService();

            $existing = $salaryService->checkExistingSalary(
                $request->user_id,
                $request->business_background_id,
                $request->month,
                $request->year
            );

            return response()->json([
                'status' => 'success',
                'data' => $existing
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking existing salary: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to check existing salary'
            ], 500);
        }
    }

    /**
     * Get salary summary untuk preview
     */
    public function getSalarySummary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'business_background_id' => 'required|exists:business_backgrounds,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salaryService = new SalarySimulationService();

            $summary = $salaryService->getSalarySummary(
                $request->user_id,
                $request->business_background_id
            );

            return response()->json([
                'status' => 'success',
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting salary summary: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get salary summary'
            ], 500);
        }
    }

    /**
     * Generate monthly salary simulation
     */
    public function generateSalary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salaryService = new SalarySimulationService();

            $result = $salaryService->generateMonthlySalary(
                $request->user_id,
                $request->business_background_id,
                $request->month,
                $request->year
            );

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'action' => $result['action'],
                'data' => $result['data']
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating salary: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate salary simulation'
            ], 500);
        }
    }

    /**
     * Upload org chart image untuk business background
     */
    public function uploadOrgChart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_background_id' => 'required|exists:business_backgrounds,id',
            'org_chart_image' => 'required|image|mimes:jpeg,png,jpg|max:5120' // 5MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $businessBackground = BusinessBackground::where('id', $request->business_background_id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$businessBackground) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business background not found or unauthorized'
                ], 404);
            }

            // Delete old org chart if exists
            if ($businessBackground->org_chart_image) {
                Storage::disk('public')->delete($businessBackground->org_chart_image);
            }

            // Store new org chart image
            $file = $request->file('org_chart_image');
            $filename = $businessBackground->id . '_org_chart_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('org-charts', $filename, 'public');

            // Update business background
            $businessBackground->update(['org_chart_image' => $path]);

            return response()->json([
                'status' => 'success',
                'message' => 'Org chart image uploaded successfully',
                'data' => [
                    'org_chart_image' => $path,
                    'org_chart_url' => asset('storage/' . $path)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading org chart: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload org chart image'
            ], 500);
        }
    }

    /**
     * Delete org chart image
     */
    public function deleteOrgChart($businessBackgroundId)
    {
        try {
            $businessBackground = BusinessBackground::where('id', $businessBackgroundId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$businessBackground) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Business background not found or unauthorized'
                ], 404);
            }

            if ($businessBackground->org_chart_image) {
                Storage::disk('public')->delete($businessBackground->org_chart_image);
                $businessBackground->update(['org_chart_image' => null]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Org chart image deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting org chart: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete org chart image'
            ], 500);
        }
    }
}
