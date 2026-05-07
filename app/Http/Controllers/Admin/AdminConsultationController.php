<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Consultation\Consultant;
use App\Models\Consultation\ConsultationPackage;
use App\Models\Consultation\ConsultationSession;
use Illuminate\Http\Request;

class AdminConsultationController extends Controller
{
    /**
     * List all consultants
     */
    public function getConsultants()
    {
        $consultants = Consultant::with('user:id,name,email,account_status')->get();
        return response()->json(['success' => true, 'data' => $consultants]);
    }

    /**
     * Assign consultant role to a user
     */
    public function assignConsultantRole(Request $request)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        
        $user = User::findOrFail($request->user_id);
        $user->role = 'consultant';
        $user->save();

        // Create consultant profile if not exists
        if (!$user->consultantProfile) {
            Consultant::create([
                'user_id' => $user->id,
                'specialization' => 'Business Advisor',
                'working_days' => [1, 2, 3, 4, 5]
            ]);
        }

        return response()->json(['success' => true, 'message' => 'User berhasil dijadikan Konsultan']);
    }

    /**
     * CRUD Packages
     */
    public function indexPackages()
    {
        $packages = ConsultationPackage::all();
        return response()->json(['success' => true, 'data' => $packages]);
    }
    public function storePackage(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'sessions_count' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'validity_days' => 'required|integer|min:1',
        ]);

        $package = ConsultationPackage::create($data);
        return response()->json(['success' => true, 'data' => $package]);
    }

    /**
     * View all sessions for monitoring
     */
    public function allSessions()
    {
        $sessions = ConsultationSession::with(['member:id,name', 'consultant.user:id,name'])
            ->orderByDesc('session_date')
            ->get();
            
        return response()->json(['success' => true, 'data' => $sessions]);
    }
}
