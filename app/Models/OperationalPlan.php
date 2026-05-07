<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class OperationalPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_background_id',
        'business_location',
        'location_description',
        'location_type',
        'location_size',
        'rent_cost',
        'employees',
        'operational_hours',
        'suppliers',
        'daily_workflow',
        'workflow_diagram',
        'workflow_image_path',
        'equipment_needs',
        'technology_stack',
        'status'
    ];

    protected $casts = [
        'employees' => 'array',
        'operational_hours' => 'array',
        'suppliers' => 'array',
        'workflow_diagram' => 'array',
        'rent_cost' => 'decimal:2',
        'location_size' => 'decimal:2'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessBackground()
    {
        return $this->belongsTo(BusinessBackground::class);
    }

    // Accessor untuk workflow image URL
    public function getWorkflowImageUrlAttribute()
    {
        if ($this->workflow_image_path) {
            return asset('storage/' . $this->workflow_image_path);
        }
        return null;
    }

    // Append accessor to JSON response
    protected $appends = ['workflow_image_url'];

    // Method untuk generate workflow diagram otomatis
    public function generateWorkflowDiagram()
    {
        $workflowText = $this->daily_workflow;

        // Parse workflow text menjadi steps
        $steps = $this->parseWorkflowSteps($workflowText);

        // Generate nodes dan edges untuk diagram
        $diagramData = $this->createDiagramData($steps);

        return [
            'steps' => $steps,
            'nodes' => $diagramData['nodes'],
            'edges' => $diagramData['edges'],
            'generated_at' => now()->toISOString()
        ];
    }

    // Parse workflow text menjadi steps terstruktur
    private function parseWorkflowSteps($workflowText)
    {
        $steps = [];
        $lines = preg_split('/\r\n|\r|\n/', $workflowText);

        $stepNumber = 1;
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line)) {
                // Deteksi pattern seperti "1. ", "Step 1:", dll
                if (preg_match('/^(\d+)[\.\)]\s*(.+)$/', $line, $matches)) {
                    $steps[] = [
                        'id' => 'step_' . $matches[1],
                        'number' => intval($matches[1]),
                        'description' => trim($matches[2]),
                        'type' => $this->detectStepType(trim($matches[2]))
                    ];
                } elseif (preg_match('/^[*-]\s*(.+)$/', $line, $matches)) {
                    $steps[] = [
                        'id' => 'step_' . $stepNumber,
                        'number' => $stepNumber,
                        'description' => trim($matches[1]),
                        'type' => $this->detectStepType(trim($matches[1]))
                    ];
                    $stepNumber++;
                } else {
                    $steps[] = [
                        'id' => 'step_' . $stepNumber,
                        'number' => $stepNumber,
                        'description' => $line,
                        'type' => $this->detectStepType($line)
                    ];
                    $stepNumber++;
                }
            }
        }

        return $steps;
    }

    // Deteksi tipe step berdasarkan keyword
    private function detectStepType($description)
    {
        $description = strtolower($description);

        if ($this->containsAny($description, ['buka', 'open', 'mulai', 'start', 'awal'])) {
            return 'start';
        } elseif ($this->containsAny($description, ['tutup', 'close', 'selesai', 'end', 'finish', 'akhir'])) {
            return 'end';
        } elseif ($this->containsAny($description, ['persiapan', 'prepare', 'setup', 'siap'])) {
            return 'preparation';
        } elseif ($this->containsAny($description, ['cek', 'check', 'verifikasi', 'verify', 'periksa', 'pemeriksaan'])) {
            return 'decision';
        } elseif ($this->containsAny($description, ['pelanggan', 'customer', 'client', 'pembeli', 'konsumen'])) {
            return 'customer';
        } elseif ($this->containsAny($description, ['laporan', 'report', 'document', 'dokumen', 'catat'])) {
            return 'document';
        } else {
            return 'process';
        }
    }

    // Helper function untuk check jika string mengandung kata-kata tertentu
    private function containsAny($string, $words)
    {
        foreach ($words as $word) {
            if (str_contains($string, $word)) {
                return true;
            }
        }
        return false;
    }

    // Buat data diagram dari steps
    private function createDiagramData($steps)
    {
        $nodes = [];
        $edges = [];

        foreach ($steps as $index => $step) {
            // Add node
            $nodes[] = [
                'id' => $step['id'],
                'label' => $step['description'],
                'type' => $step['type'],
                'shape' => $this->getNodeShape($step['type'])
            ];

            // Add edge jika bukan step terakhir
            if ($index < count($steps) - 1) {
                $edges[] = [
                    'from' => $step['id'],
                    'to' => $steps[$index + 1]['id'],
                    'label' => 'Lanjut'
                ];
            }
        }

        return [
            'nodes' => $nodes,
            'edges' => $edges
        ];
    }

    // Dapatkan shape node berdasarkan tipe
    private function getNodeShape($type)
    {
        $shapes = [
            'start' => 'circle',
            'end' => 'circle',
            'process' => 'rect',
            'decision' => 'diamond',
            'preparation' => 'round-rect',
            'customer' => 'stadium',
            'document' => 'document'
        ];

        return $shapes[$type] ?? 'rect';
    }

    // Scope untuk filter
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_background_id', $businessId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Method untuk statistics
    public function scopeGetStatistics($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count,
                SUM(CASE WHEN workflow_diagram IS NOT NULL THEN 1 ELSE 0 END) as with_diagram_count
            ')
            ->first();
    }
}
