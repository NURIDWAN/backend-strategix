<?php

namespace App\Models\Forecast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForecastInsight extends Model
{
    use HasFactory;

    protected $table = 'forecast_insights';

    protected $fillable = [
        'forecast_data_id',
        'insight_type',
        'title',
        'description',
        'value',
        'month',
        'year',
        'severity',
    ];

    /**
     * Relasi: ForecastInsight milik satu ForecastData
     */
    public function forecastData()
    {
        return $this->belongsTo(ForecastData::class);
    }
}
