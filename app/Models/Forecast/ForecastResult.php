<?php

namespace App\Models\Forecast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForecastResult extends Model
{
    use HasFactory;

    protected $table = 'forecast_results';

    protected $fillable = [
        'forecast_data_id',
        'month',
        'year',
        'forecast_income',
        'forecast_expense',
        'forecast_profit',
        'forecast_margin',
        'confidence_level',
        'method',
    ];

    protected $casts = [
        'forecast_income' => 'decimal:2',
        'forecast_expense' => 'decimal:2',
        'forecast_profit' => 'decimal:2',
        'forecast_margin' => 'decimal:2',
        'confidence_level' => 'decimal:2',
    ];

    /**
     * Relasi: ForecastResult milik satu ForecastData
     */
    public function forecastData()
    {
        return $this->belongsTo(ForecastData::class);
    }
}
