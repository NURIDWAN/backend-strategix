<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductService extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_background_id',
        'type',
        'name',
        'description',
        'price',
        'image_path',
        'advantages',
        'development_strategy',
        'bmc_alignment',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'bmc_alignment' => 'array',
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

    // Accessor untuk full image URL
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return null;
    }

    // Accessor untuk BMC Alignment dengan default values
    public function getBmcAlignmentAttribute($value)
    {
        $defaultAlignment = [
            'customer_segment' => null,
            'value_proposition' => null,
            'channels' => null,
            'customer_relationships' => null,
            'revenue_streams' => null,
            'key_resources' => null,
            'key_activities' => null,
            'key_partnerships' => null,
            'cost_structure' => null
        ];

        if ($value) {
            $alignment = json_decode($value, true);
            return array_merge($defaultAlignment, $alignment);
        }

        return $defaultAlignment;
    }

    // Mutator untuk BMC Alignment
    public function setBmcAlignmentAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['bmc_alignment'] = json_encode($value);
        } else {
            $this->attributes['bmc_alignment'] = $value;
        }
    }

    // Method untuk generate BMC alignment otomatis berdasarkan data
    public function generateBmcAlignment()
    {
        $alignment = [
            'customer_segment' => $this->generateCustomerSegment(),
            'value_proposition' => $this->generateValueProposition(),
            'channels' => $this->generateChannels(),
            'customer_relationships' => $this->generateCustomerRelationships(),
            'revenue_streams' => $this->generateRevenueStreams(),
            'key_resources' => $this->generateKeyResources(),
            'key_activities' => $this->generateKeyActivities(),
            'key_partnerships' => $this->generateKeyPartnerships(),
            'cost_structure' => $this->generateCostStructure(),
        ];

        $this->bmc_alignment = $alignment;
        return $alignment;
    }

    // Helper methods untuk generate BMC alignment
    private function generateCustomerSegment()
    {
        return "Target pasar untuk {$this->name} berdasarkan deskripsi: " . substr($this->description, 0, 100) . "...";
    }

    private function generateValueProposition()
    {
        $valueProps = [];
        if ($this->advantages) {
            $valueProps[] = "Keunggulan: {$this->advantages}";
        }
        if ($this->description) {
            $valueProps[] = "Manfaat: " . substr($this->description, 0, 150) . "...";
        }
        return implode(' | ', $valueProps);
    }

    private function generateChannels()
    {
        $channels = ["Platform digital", "Pemasaran langsung"];
        if ($this->image_path) {
            $channels[] = "Visual marketing melalui gambar produk";
        }
        return implode(', ', $channels);
    }

    private function generateCustomerRelationships()
    {
        $relationships = ["Dukungan pelanggan"];
        if ($this->development_strategy) {
            $relationships[] = "Pengembangan berkelanjutan berdasarkan strategi";
        }
        return implode(', ', $relationships);
    }

    private function generateRevenueStreams()
    {
        if ($this->price) {
            return "Pendapatan dari penjualan {$this->type} dengan harga " . number_format($this->price, 0, ',', '.');
        }
        return "Model pendapatan dari penjualan {$this->type}";
    }

    private function generateKeyResources()
    {
        $resources = ["Aset intelektual", "Brand {$this->name}"];
        if ($this->image_path) {
            $resources[] = "Aset visual/gambar produk";
        }
        return implode(', ', $resources);
    }

    private function generateKeyActivities()
    {
        $activities = ["Produksi/pengembangan {$this->type}"];
        if ($this->development_strategy) {
            $activities[] = "Implementasi strategi pengembangan";
        }
        return implode(', ', $activities);
    }

    private function generateKeyPartnerships()
    {
        return "Supplier, mitra distribusi, partner teknologi";
    }

    private function generateCostStructure()
    {
        $costs = ["Biaya produksi/pengembangan {$this->type}"];
        if ($this->development_strategy) {
            $costs[] = "Biaya riset dan pengembangan";
        }
        if ($this->image_path) {
            $costs[] = "Biaya branding dan visual assets";
        }
        return implode(', ', $costs);
    }

    // Append accessor to JSON response
    protected $appends = ['image_url'];

    // Scope untuk filter
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_background_id', $businessId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk pencarian
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('advantages', 'like', "%{$search}%");
    }
}
