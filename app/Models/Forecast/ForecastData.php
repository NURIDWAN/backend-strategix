<?php

namespace App\Models\Forecast;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\ManagementFinancial\FinancialSimulation;

class ForecastData extends Model
{
    use HasFactory;

    protected $table = 'forecast_data';

    protected $fillable = [
        'user_id',
        'financial_simulation_id',
        'month',
        'year',
        'income_sales',
        'income_other',
        'expense_operational',
        'expense_other',
        'seasonal_factor',
        'notes',
    ];

    protected $casts = [
        'income_sales' => 'decimal:2',
        'income_other' => 'decimal:2',
        'expense_operational' => 'decimal:2',
        'expense_other' => 'decimal:2',
        'seasonal_factor' => 'decimal:2',
    ];

    /**
     * Relasi: ForecastData milik satu User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi: ForecastData bisa berasal dari FinancialSimulation
     */
    public function financialSimulation()
    {
        return $this->belongsTo(FinancialSimulation::class);
    }

    /**
     * Relasi: ForecastData memiliki banyak ForecastResult
     */
    public function forecastResults()
    {
        return $this->hasMany(ForecastResult::class, 'forecast_data_id');
    }

    /**
     * Relasi: ForecastData memiliki banyak ForecastInsight
     */
    public function insights()
    {
        return $this->hasMany(ForecastInsight::class, 'forecast_data_id');
    }
}
