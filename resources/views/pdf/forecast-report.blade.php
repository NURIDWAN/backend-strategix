<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forecast Report - {{ $forecastData->year }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            background: white;
        }

        .page {
            page-break-after: always;
            padding: 40px;
            min-height: 100vh;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 32px;
            color: #2563eb;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 14px;
            color: #666;
        }

        .meta-info {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            font-size: 12px;
        }

        .meta-info-item {
            display: table-cell;
            padding: 10px 20px;
            border: 1px solid #ddd;
        }

        .meta-info-label {
            font-weight: bold;
            color: #2563eb;
        }

        .section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }

        .executive-summary {
            background: #f3f4f6;
            padding: 20px;
            border-left: 4px solid #2563eb;
            border-radius: 4px;
            line-height: 1.8;
            font-size: 14px;
        }

        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .stat-card {
            display: table-cell;
            padding: 15px;
            border: 1px solid #e5e7eb;
            text-align: center;
            vertical-align: middle;
            width: 25%;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12px;
        }

        th {
            background: #2563eb;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:nth-child(even) {
            background: #f9fafb;
        }

        .currency {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .percentage {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .positive {
            color: #10b981;
            font-weight: bold;
        }

        .negative {
            color: #ef4444;
            font-weight: bold;
        }

        .insight {
            margin-bottom: 15px;
            padding: 12px;
            border-left: 3px solid #2563eb;
            background: #eff6ff;
            font-size: 13px;
        }

        .insight.critical {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        .insight.warning {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }

        .insight.positive {
            border-left-color: #10b981;
            background: #f0fdf4;
        }

        .insight-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 11px;
            color: #999;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Page 1: Title & Executive Summary -->
    <div class="page">
        <div class="header">
            <h1>📊 Forecast Report</h1>
            <p>Laporan Proyeksi Keuangan</p>
        </div>

        <div class="meta-info">
            <div class="meta-info-item">
                <span class="meta-info-label">Tahun:</span>
                <span>{{ isset($is_combined) && $is_combined ? 'Laporan Gabungan' : $forecastData->year }}</span>
            </div>
            <div class="meta-info-item">
                <span class="meta-info-label">Tipe:</span>
                <span>{{ isset($is_combined) && $is_combined ? 'Multi-Tahun' : ($forecastData->month ? "Bulan {$forecastData->month}" : 'Tahun Penuh') }}</span>
            </div>
            <div class="meta-info-item">
                <span class="meta-info-label">Tanggal Generate:</span>
                <span>{{ $generated_at }}</span>
            </div>
            <div class="meta-info-item">
                <span class="meta-info-label">Mode:</span>
                <span>{{ ucfirst($mode) }}</span>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Ringkasan Eksekutif</div>
            <div class="executive-summary">
                {!! nl2br($executiveSummary) !!}
            </div>
        </div>

        <!-- Key Statistics -->
        <div class="section">
            <div class="section-title">Statistik Utama</div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Pendapatan</div>
                    <div class="stat-value">Rp {{ number_format($statistics['total_income'], 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Pengeluaran</div>
                    <div class="stat-value">Rp {{ number_format($statistics['total_expense'], 0, ',', '.') }}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Laba</div>
                    <div class="stat-value {{ $statistics['total_profit'] >= 0 ? 'positive' : 'negative' }}">
                        Rp {{ number_format($statistics['total_profit'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Rata-rata Margin</div>
                    <div class="stat-value">{{ number_format($statistics['avg_margin'], 2) }}%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page 2: Detailed Results -->
    <div class="page">
        <div class="header">
            <h1>Detail Proyeksi</h1>
        </div>

        <div class="section">
            <div class="section-title">Tabel Detail Prediksi Bulanan</div>

            @if($forecastResults && count($forecastResults) > 0)
                <table>
                    <thead>
                        <tr>
                            @if(isset($is_combined) && $is_combined)
                                <th>Periode</th>
                            @else
                                <th>Bulan</th>
                            @endif
                            <th class="currency">Pendapatan</th>
                            <th class="currency">Pengeluaran</th>
                            <th class="currency">Laba</th>
                            <th class="percentage">Margin %</th>
                            <th class="percentage">Confidence %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($forecastResults as $result)
                            <tr>
                                @if(isset($is_combined) && $is_combined)
                                    <td>Bulan {{ $result['month'] ?? '-' }} - {{ $result['year'] ?? '-' }}</td>
                                @else
                                    <td>Bulan {{ $result['month'] ?? '-' }}</td>
                                @endif
                                <td class="currency">Rp {{ number_format($result['forecast_income'] ?? 0, 0, ',', '.') }}</td>
                                <td class="currency">Rp {{ number_format($result['forecast_expense'] ?? 0, 0, ',', '.') }}</td>
                                <td class="currency {{ ($result['forecast_profit'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                                    Rp {{ number_format($result['forecast_profit'] ?? 0, 0, ',', '.') }}
                                </td>
                                <td class="percentage">{{ number_format($result['forecast_margin'] ?? 0, 2) }}%</td>
                                <td class="percentage">{{ number_format($result['confidence_level'] ?? 0, 2) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p style="color: #999; font-style: italic;">Tidak ada data proyeksi yang tersedia.</p>
            @endif
        </div>

        <!-- Performance Metrics -->
        <div class="section">
            <div class="section-title">Metrik Performa</div>

            <table>
                <tr>
                    <td style="width: 40%; font-weight: bold;">Rata-rata Kepercayaan Prediksi</td>
                    <td class="percentage">{{ number_format($statistics['avg_confidence'], 2) }}%</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Tingkat Pertumbuhan</td>
                    <td class="percentage">{{ number_format($statistics['growth_rate'], 2) }}%</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Pendapatan Tertinggi</td>
                    <td>Bulan {{ $statistics['highest_income_month'] ?? '-' }} (Rp {{ number_format($statistics['highest_income_value'] ?? 0, 0, ',', '.') }})</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Laba Tertinggi</td>
                    <td>Bulan {{ $statistics['highest_profit_month'] ?? '-' }} (Rp {{ number_format($statistics['highest_profit_value'] ?? 0, 0, ',', '.') }})</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Page 3: Insights & Analysis -->
    @if($insights && count($insights) > 0)
        <div class="page">
            <div class="header">
                <h1>Auto Insights & Analisis</h1>
            </div>

            <div class="section">
                <div class="section-title">Insight Otomatis Sistem</div>

                @foreach($insights as $insight)
                    <div class="insight {{ strtolower($insight['severity'] ?? 'info') }}">
                        <div class="insight-title">{{ $insight['title'] ?? 'Insight' }}</div>
                        <div>{{ $insight['description'] ?? '-' }}</div>
                        @if(isset($insight['value']) && $insight['value'])
                            <div style="margin-top: 5px; color: #666;">
                                <strong>Nilai:</strong> {{ $insight['value'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>SmartPlan - Forecast Report | Generated on {{ $generated_at }}</p>
    </div>
</body>
</html>
