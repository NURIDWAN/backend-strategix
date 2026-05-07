<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan - {{ $data['business_background']->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(200, 200, 200, 0.1);
            font-weight: bold;
            z-index: -1;
            white-space: nowrap;
        }

        /* Cover Page */
        .cover-page {
            page-break-after: always;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
        }

        .cover-title {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .cover-subtitle {
            font-size: 24px;
            margin-bottom: 40px;
        }

        .cover-info {
            font-size: 16px;
            margin: 10px 0;
        }

        .cover-footer {
            margin-top: 60px;
            font-size: 12px;
        }

        /* Content Pages */
        .page {
            page-break-after: always;
            padding: 30px;
            position: relative;
        }

        .page:last-child {
            page-break-after: auto;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 20px;
            color: #667eea;
            margin-bottom: 5px;
        }

        .header .meta {
            font-size: 9px;
            color: #666;
        }

        /* Section */
        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e0e0e0;
        }

        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 12px;
            border-radius: 4px;
        }

        .summary-card.income {
            border-left-color: #10b981;
            background: #f0fdf4;
        }

        .summary-card.expense {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        .summary-card.profit {
            border-left-color: #3b82f6;
            background: #eff6ff;
        }

        .summary-card .label {
            font-size: 9px;
            color: #666;
            margin-bottom: 4px;
        }

        .summary-card .value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }

        .summary-card .subvalue {
            font-size: 8px;
            color: #999;
            margin-top: 2px;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
        }

        table th {
            background: #667eea;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }

        table td {
            padding: 6px;
            border-bottom: 1px solid #e0e0e0;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        table tr:last-child td {
            border-bottom: 2px solid #667eea;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-green {
            color: #10b981;
            font-weight: bold;
        }

        .text-red {
            color: #ef4444;
            font-weight: bold;
        }

        .text-blue {
            color: #3b82f6;
            font-weight: bold;
        }

        /* Chart Placeholder */
        .chart-container {
            background: #f8f9fa;
            border: 2px dashed #ccc;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            margin: 15px 0;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .chart-image {
            max-width: 100%;
            max-height: 250px;
            object-fit: contain;
        }

        /* Projection Cards */
        .projection-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .projection-card {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            border: 2px solid #e0e0e0;
        }

        .projection-card.optimistic {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .projection-card.realistic {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .projection-card.pessimistic {
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .projection-card .scenario-label {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .projection-card .metric {
            margin: 6px 0;
            font-size: 9px;
        }

        .projection-card .metric-label {
            color: #666;
        }

        .projection-card .metric-value {
            font-weight: bold;
            color: #333;
            float: right;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 15px;
            left: 30px;
            right: 30px;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #e0e0e0;
            padding-top: 8px;
        }

        /* Utilities */
        .mb-10 {
            margin-bottom: 10px;
        }

        .mb-15 {
            margin-bottom: 15px;
        }

        .mt-20 {
            margin-top: 20px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
        }

        .badge-success {
            background: #10b981;
            color: white;
        }

        .badge-danger {
            background: #ef4444;
            color: white;
        }

        .badge-warning {
            background: #f59e0b;
            color: white;
        }

        .badge-info {
            background: #3b82f6;
            color: white;
        }

        /* Page Numbers */
        @page {
            margin: 0;
        }
    </style>
</head>

<body>
    <!-- Watermark -->
    <div class="watermark">SMARTPLAN</div>

    <!-- Cover Page -->
    <div class="cover-page">
        <div class="cover-title">LAPORAN KEUANGAN</div>
        <div class="cover-subtitle">{{ $data['business_background']->name }}</div>
        <div class="cover-info">
            <strong>Periode:</strong> {{ $period_label }}
        </div>
        <div class="cover-info">
            <strong>Jenis Usaha:</strong> {{ $data['business_background']->business_type ?? '-' }}
        </div>
        <div class="cover-info">
            <strong>Modal Awal:</strong> Rp {{ number_format($data['summary']['initial_capital'], 0, ',', '.') }}
        </div>
        <div class="cover-footer">
            Dibuat dengan SmartPlan<br>
            {{ $generated_at }}
        </div>
    </div>

    <!-- Executive Summary Page -->
    <div class="page">
        <div class="header">
            <h1>Ringkasan Eksekutif</h1>
            <div class="meta">{{ $period_label }}</div>
        </div>

        <div class="summary-grid">
            <div class="summary-card income">
                <div class="label">Total Pendapatan</div>
                <div class="value">Rp {{ number_format($data['summary']['total_income'], 0, ',', '.') }}</div>
                <div class="subvalue">{{ $data['summary']['income_count'] }} transaksi</div>
            </div>
            <div class="summary-card expense">
                <div class="label">Total Pengeluaran</div>
                <div class="value">Rp {{ number_format($data['summary']['total_expense'], 0, ',', '.') }}</div>
                <div class="subvalue">{{ $data['summary']['expense_count'] }} transaksi</div>
            </div>
            <div class="summary-card profit">
                <div class="label">Laba/Rugi Bersih</div>
                <div class="value {{ $data['summary']['net_profit'] >= 0 ? 'text-green' : 'text-red' }}">
                    Rp {{ number_format($data['summary']['net_profit'], 0, ',', '.') }}
                </div>
                <div class="subvalue">
                    <span
                        class="badge {{ $executiveSummary['profit_status'] == 'profit' ? 'badge-success' : 'badge-danger' }}">
                        {{ $executiveSummary['profit_status'] == 'profit' ? 'PROFIT' : 'LOSS' }}
                        {{ number_format(abs($executiveSummary['profit_percentage']), 1) }}%
                    </span>
                </div>
            </div>
            <div class="summary-card">
                <div class="label">Saldo Kas Terkini</div>
                <div class="value {{ $data['summary']['current_cash_balance'] >= 0 ? 'text-green' : 'text-red' }}">
                    Rp {{ number_format($data['summary']['current_cash_balance'], 0, ',', '.') }}
                </div>
                <div class="subvalue">
                    <span
                        class="badge {{ $executiveSummary['cash_health'] == 'healthy' ? 'badge-success' : 'badge-danger' }}">
                        {{ strtoupper($executiveSummary['cash_health']) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">Posisi Kas</div>
            <table>
                <thead>
                    <tr>
                        <th>Deskripsi</th>
                        <th class="text-right">Jumlah</th>
                        <th class="text-right">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Modal Awal</td>
                        <td class="text-right">Rp {{ number_format($data['summary']['initial_capital'], 0, ',', '.') }}
                        </td>
                        <td class="text-right">100%</td>
                    </tr>
                    <tr>
                        <td>Akumulasi Pendapatan (Semua Periode)</td>
                        <td class="text-right text-green">Rp
                            {{ number_format($data['summary']['accumulated_income'], 0, ',', '.') }}</td>
                        <td class="text-right">
                            {{ $data['summary']['initial_capital'] > 0 ? number_format(($data['summary']['accumulated_income'] / $data['summary']['initial_capital']) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    <tr>
                        <td>Akumulasi Pengeluaran (Semua Periode)</td>
                        <td class="text-right text-red">Rp
                            {{ number_format($data['summary']['accumulated_expense'], 0, ',', '.') }}</td>
                        <td class="text-right">
                            {{ $data['summary']['initial_capital'] > 0 ? number_format(($data['summary']['accumulated_expense'] / $data['summary']['initial_capital']) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    <tr style="background: #f0f9ff; font-weight: bold;">
                        <td>Saldo Kas Terkini</td>
                        <td
                            class="text-right {{ $data['summary']['current_cash_balance'] >= 0 ? 'text-green' : 'text-red' }}">
                            Rp {{ number_format($data['summary']['current_cash_balance'], 0, ',', '.') }}
                        </td>
                        <td class="text-right">
                            {{ $data['summary']['initial_capital'] > 0 ? number_format(($data['summary']['current_cash_balance'] / $data['summary']['initial_capital']) * 100, 1) : 0 }}%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Kategori Terbaik</div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <strong style="color: #10b981; font-size: 10px;">Top Pendapatan:</strong>
                    <p style="margin-top: 5px; font-size: 9px;">{{ $executiveSummary['top_income_category'] }}</p>
                </div>
                <div>
                    <strong style="color: #ef4444; font-size: 10px;">Top Pengeluaran:</strong>
                    <p style="margin-top: 5px; font-size: 9px;">{{ $executiveSummary['top_expense_category'] }}</p>
                </div>
            </div>
        </div>

        @if (!empty($charts['income_vs_expense']))
            <div class="section mt-20">
                <div class="section-title">Perbandingan Pendapatan vs Pengeluaran</div>
                <div class="chart-container" style="border: none; background: white; min-height: auto;">
                    <img src="{{ $charts['income_vs_expense'] }}" alt="Income vs Expense Chart" class="chart-image">
                </div>
            </div>
        @endif
    </div>

    <!-- Category Summary Page -->
    <div class="page">
        <div class="header">
            <h1>Ringkasan Per Kategori</h1>
            <div class="meta">{{ $period_label }}</div>
        </div>

        <div class="section">
            <div class="section-title">Top 5 Kategori Pendapatan</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th>Kategori</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Transaksi</th>
                        <th class="text-right">Rata-rata</th>
                        <th class="text-right">% dari Total Income</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @foreach ($data['category_summary']['top_income'] as $cat)
                        <tr>
                            <td class="text-center">{{ $no++ }}</td>
                            <td>{{ $cat['category']->name }}</td>
                            <td class="text-right text-green">Rp {{ number_format($cat['total'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ $cat['count'] }}</td>
                            <td class="text-right">Rp {{ number_format($cat['average'], 0, ',', '.') }}</td>
                            <td class="text-right">
                                {{ $data['summary']['total_income'] > 0 ? number_format(($cat['total'] / $data['summary']['total_income']) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    @endforeach
                    @if (empty($data['category_summary']['top_income']))
                        <tr>
                            <td colspan="6" class="text-center" style="color: #999;">Tidak ada data pendapatan</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="section mt-20">
            <div class="section-title">Top 5 Kategori Pengeluaran</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th>Kategori</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Transaksi</th>
                        <th class="text-right">Rata-rata</th>
                        <th class="text-right">% dari Total Expense</th>
                    </tr>
                </thead>
                <tbody>
                    @php $no = 1; @endphp
                    @foreach ($data['category_summary']['top_expense'] as $cat)
                        <tr>
                            <td class="text-center">{{ $no++ }}</td>
                            <td>{{ $cat['category']->name }}</td>
                            <td class="text-right text-red">Rp {{ number_format($cat['total'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ $cat['count'] }}</td>
                            <td class="text-right">Rp {{ number_format($cat['average'], 0, ',', '.') }}</td>
                            <td class="text-right">
                                {{ $data['summary']['total_expense'] > 0 ? number_format(($cat['total'] / $data['summary']['total_expense']) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    @endforeach
                    @if (empty($data['category_summary']['top_expense']))
                        <tr>
                            <td colspan="6" class="text-center" style="color: #999;">Tidak ada data pengeluaran</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if (!empty($charts['category_income_pie']) || !empty($charts['category_expense_pie']))
            <div class="section mt-20">
                <div class="section-title">Distribusi Per Kategori</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    @if (!empty($charts['category_income_pie']))
                        <div>
                            <strong
                                style="font-size: 9px; color: #10b981; display: block; text-align: center; margin-bottom: 8px;">Distribusi
                                Pendapatan</strong>
                            <div class="chart-container" style="border: none; background: white; min-height: auto;">
                                <img src="{{ $charts['category_income_pie'] }}" alt="Category Income Chart"
                                    style="max-width: 100%; max-height: 300px;">
                            </div>
                        </div>
                    @endif

                    @if (!empty($charts['category_expense_pie']))
                        <div>
                            <strong
                                style="font-size: 9px; color: #ef4444; display: block; text-align: center; margin-bottom: 8px;">Distribusi
                                Pengeluaran</strong>
                            <div class="chart-container" style="border: none; background: white; min-height: auto;">
                                <img src="{{ $charts['category_expense_pie'] }}" alt="Category Expense Chart"
                                    style="max-width: 100%; max-height: 300px;">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- Monthly Trend (if year view) -->
    @if ($period_type == 'year' && !empty($data['monthly_summary']))
        <div class="page">
            <div class="header">
                <h1>Tren Bulanan</h1>
                <div class="meta">Tahun {{ $data['period']['year'] }}</div>
            </div>

            <div class="section">
                <div class="section-title">Ringkasan Per Bulan</div>
                <table>
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="text-right">Pendapatan</th>
                            <th class="text-right">Pengeluaran</th>
                            <th class="text-right">Laba/Rugi</th>
                            <th class="text-right">Margin (%)</th>
                            <th class="text-center">Transaksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data['monthly_summary'] as $month)
                            <tr>
                                <td>{{ $month['month_name'] }}</td>
                                <td class="text-right text-green">Rp
                                    {{ number_format($month['income'], 0, ',', '.') }}</td>
                                <td class="text-right text-red">Rp {{ number_format($month['expense'], 0, ',', '.') }}
                                </td>
                                <td class="text-right {{ $month['net_profit'] >= 0 ? 'text-green' : 'text-red' }}">
                                    Rp {{ number_format($month['net_profit'], 0, ',', '.') }}
                                </td>
                                <td class="text-right">
                                    {{ $month['income'] > 0 ? number_format(($month['net_profit'] / $month['income']) * 100, 1) : 0 }}%
                                </td>
                                <td class="text-center">{{ $month['transaction_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (!empty($charts['monthly_trend']))
                <div class="section mt-20">
                    <div class="section-title">Grafik Tren Bulanan</div>
                    <div class="chart-container">
                        <img src="{{ $charts['monthly_trend'] }}" alt="Monthly Trend Chart" class="chart-image">
                    </div>
                </div>
            @endif
        </div>
    @endif

    <!-- Financial Projections -->
    @if (!empty($data['projections']) && count($data['projections']) > 0)
        <div class="page">
            <div class="header">
                <h1>Proyeksi Keuangan 5 Tahun</h1>
                <div class="meta">3 Skenario Proyeksi (Data Terbaru)</div>
            </div>

            <div class="section">
                <div class="section-title">Ringkasan Proyeksi</div>
                <div class="projection-grid">
                    @foreach ($data['projections'] as $projection)
                        <div class="projection-card {{ $projection->scenario_type }}">
                            <div class="scenario-label">
                                @if ($projection->scenario_type == 'optimistic')
                                    🚀 Optimistik
                                @elseif($projection->scenario_type == 'realistic')
                                    📊 Realistik
                                @else
                                    ⚠️ Pesimistik
                                @endif
                            </div>
                            <div class="metric">
                                <span class="metric-label">NPV:</span>
                                <span class="metric-value {{ $projection->npv >= 0 ? 'text-green' : 'text-red' }}">
                                    {{ $projection->formatted_npv ?? 'Rp ' . number_format($projection->npv, 0, ',', '.') }}
                                </span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">ROI:</span>
                                <span class="metric-value text-blue">
                                    {{ $projection->formatted_roi ?? number_format($projection->roi, 2) . '%' }}
                                </span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">IRR:</span>
                                <span class="metric-value">
                                    {{ $projection->formatted_irr ?? number_format($projection->irr, 2) . '%' }}
                                </span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Payback Period:</span>
                                <span class="metric-value">
                                    {{ $projection->formatted_payback ?? number_format($projection->payback_period, 1) . ' tahun' }}
                                </span>
                            </div>
                            <div class="metric">
                                <span class="metric-label">Growth Rate:</span>
                                <span class="metric-value">{{ number_format($projection->growth_rate, 1) }}%</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="section mt-20">
                <div class="section-title">Detail Proyeksi Per Tahun</div>
                @foreach ($data['projections'] as $projection)
                    <div class="mb-15">
                        <strong style="font-size: 10px;">
                            Skenario {{ ucfirst($projection->scenario_type) }} (Growth:
                            {{ number_format($projection->growth_rate, 1) }}%)
                        </strong>
                        <table style="margin-top: 8px;">
                            <thead>
                                <tr>
                                    <th>Tahun</th>
                                    <th class="text-right">Pendapatan Proyeksi</th>
                                    <th class="text-right">Pengeluaran Proyeksi</th>
                                    <th class="text-right">Laba Bersih</th>
                                    <th class="text-right">Kumulatif Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $years = $projection->yearly_projections ?? [];
                                @endphp
                                @php $cumulativeProfit = 0; @endphp
                                @foreach ($years as $year)
                                    @php $cumulativeProfit += $year['net_profit']; @endphp
                                    <tr>
                                        <td>Tahun {{ $year['year'] }}</td>
                                        <td class="text-right text-green">Rp
                                            {{ number_format($year['revenue'], 0, ',', '.') }}</td>
                                        <td class="text-right text-red">Rp
                                            {{ number_format($year['cost'], 0, ',', '.') }}</td>
                                        <td
                                            class="text-right {{ $year['net_profit'] >= 0 ? 'text-green' : 'text-red' }}">
                                            Rp {{ number_format($year['net_profit'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-right text-blue">Rp
                                            {{ number_format($cumulativeProfit, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    @endif



    <!-- Footer on every page -->
    <div class="footer">
        <div>
            Laporan Keuangan - {{ $data['business_background']->name }} | Periode: {{ $period_label }}
        </div>
        <div style="margin-top: 3px;">
            Dibuat dengan SmartPlan © {{ date('Y') }} | Dicetak: {{ $generated_at }}
        </div>
    </div>
</body>

</html>
