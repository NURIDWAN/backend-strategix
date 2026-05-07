<!DOCTYPE html>
<html>

@php
    // ============================================
    // GLOBAL HELPER FUNCTIONS
    // ============================================

    /**
     * Split long text into multiple paragraphs
     * Splits text by sentences and groups them per paragraph
     * Enhanced to handle newlines, numbered lists, and various text formats
     */
    if (!function_exists('splitLongText')) {
        function splitLongText($text, $sentencesPerParagraph = 2)
        {
            $text = trim(strip_tags(htmlspecialchars_decode($text)));
            if (empty($text)) {
                return [];
            }

            // First, check if text already has double newlines (paragraph separators)
            if (strpos($text, "\n\n") !== false) {
                $paragraphs = preg_split('/\n\n+/', $text, -1, PREG_SPLIT_NO_EMPTY);
                return array_map('trim', array_filter($paragraphs));
            }

            // Check for escaped newlines (\\n\\n)
            if (strpos($text, '\\n\\n') !== false) {
                $paragraphs = preg_split('/\\\\n\\\\n+/', $text, -1, PREG_SPLIT_NO_EMPTY);
                return array_map('trim', array_filter($paragraphs));
            }

            // Split by single newlines for list items or separate lines
            if (preg_match('/\n\d+\.|\n-|\n\*/', $text)) {
                $paragraphs = preg_split('/\n(?=\d+\.|-|\*)/', $text, -1, PREG_SPLIT_NO_EMPTY);
                $result = [];
                $buffer = [];
                foreach ($paragraphs as $para) {
                    $para = trim($para);
                    if (!empty($para)) {
                        // If it's a list item or too long, keep it separate
                    if (preg_match('/^\d+\.|-|\*/', $para) || strlen($para) > 500) {
                        if (!empty($buffer)) {
                            $result[] = implode(' ', $buffer);
                            $buffer = [];
                        }
                        $result[] = $para;
                    } else {
                        $buffer[] = $para;
                    }
                }
            }
            if (!empty($buffer)) {
                $result[] = implode(' ', $buffer);
            }
            return array_filter($result);
        }

        // Default: Split by sentences and group them
        $sentences = preg_split('/(?<=[.!?])\s+(?=[A-Z])/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($sentences) || count($sentences) <= 1) {
            // If no sentence split possible, try to break by length
            if (strlen($text) > 800) {
                $chunks = [];
                $words = explode(' ', $text);
                $currentChunk = [];
                $currentLength = 0;
                foreach ($words as $word) {
                    $currentChunk[] = $word;
                    $currentLength += strlen($word) + 1;
                    if ($currentLength > 400) {
                        $chunks[] = implode(' ', $currentChunk);
                        $currentChunk = [];
                        $currentLength = 0;
                    }
                }
                if (!empty($currentChunk)) {
                    $chunks[] = implode(' ', $currentChunk);
                }
                return array_filter($chunks);
            }
            return [$text];
        }

        $paragraphs = [];
        $currentParagraph = [];
        $sentenceCount = 0;

        foreach ($sentences as $sentence) {
            $cleanSentence = trim($sentence);
            if (!empty($cleanSentence)) {
                $currentParagraph[] = $cleanSentence;
                $sentenceCount++;

                if ($sentenceCount >= $sentencesPerParagraph) {
                    $paragraphs[] = implode(' ', $currentParagraph);
                    $currentParagraph = [];
                    $sentenceCount = 0;
                }
            }
        }

        // Tambahkan sisa kalimat
        if (!empty($currentParagraph)) {
            $paragraphs[] = implode(' ', $currentParagraph);
            }

            return array_filter($paragraphs);
        }
    }
@endphp

<head>
    <meta charset="utf-8">
    <title>Laporan Lengkap - {{ $data['business_background']->name }}</title>
    <style>
        /* Reset dan base styles - SAMA SEPERTI BUSINESS PLAN */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            font-size: 12px;
            text-align: justify;
            padding-top: 20mm;
            padding-bottom: 15mm;
        }

        /* Watermark Container Full Page */
        .watermark-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            pointer-events: none;

        }

        /* Watermark Content (Grouped Border Box) */
        .watermark-content {
            position: fixed;
            top: 50%;
            left: 49%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 9999;
            pointer-events: none;
        }

        .watermark-logo {
            width: 800px;
            /* Adjusted size to fit nicely */
            height: auto;
            opacity: 0.35;
            display: block;
            margin: 0 auto;
        }


        /* Layout halaman */
        .page {
            page-break-after: always;
            padding: 15mm;
            padding-bottom: 15mm;
            position: relative;
        }

        .page:last-child {
            page-break-after: auto;
        }

        /* Mask footer for specific pages (cover/separator) */
        .footer-mask {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 40mm;
            background: #ffffff;
            z-index: 5000;
        }

        /* Header - SAMA SEPERTI BUSINESS PLAN */
        .header {
            border-bottom: 2px solid #2c5aa0;
            padding-bottom: 10px;
            margin-bottom: 15px;
            margin-top: -20mm;
        }

        .company-name {
            font-size: 24px;
            color: #2c5aa0;
            font-weight: bold;
        }

        .document-title {
            font-size: 18px;
            color: #666;
            margin-top: 5px;
        }

        /* Sections - SAMA SEPERTI BUSINESS PLAN */
        .section {
            margin-top: 15px;
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 16px;
            color: #2c5aa0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .subsection {
            margin-bottom: 15px;
        }

        .subsection-title {
            font-size: 14px;
            color: #333;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 8px;
        }

        /* Tables - SAMA SEPERTI BUSINESS PLAN */
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
            line-height: 1.4;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 8px 6px;
            text-align: justify;
            word-wrap: break-word;
            vertical-align: top;
            line-height: 1.6;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: bold;
            text-align: left;
        }

        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        /* Utilities */
        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: bold;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        .mb-15 {
            margin-bottom: 15px;
        }

        .mb-5 {
            margin-bottom: 5px;
        }

        .mt-15 {
            margin-top: 15px;
        }

        .text-green {
            color: #10b981;
        }

        .text-red {
            color: #ef4444;
        }

        /* Scenario Box Styles */
        .box-optimistic {
            background: #f0fdf4;
            border-left: 4px solid #10b981;
        }

        .box-realistic {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
        }

        .box-pessimistic {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
        }


        /* Chart images */
        .chart-image {
            width: 100%;
            max-width: 350px;
            height: auto;
            margin: 10px auto;
            display: block;
            page-break-inside: avoid;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px;
            background: #ffffff;
        }

        /* Chart Analysis Box */
        .chart-analysis {
            background: #f0f9ff;
            border-left: 4px solid #0284c7;
            padding: 12px 15px;
            margin-top: 18px;
            border-radius: 4px;
            font-size: 11px;
            line-height: 1.6;
            color: #1e293b;
            page-break-inside: avoid;
        }

        .chart-analysis-title {
            font-weight: bold;
            color: #0284c7;
            margin-bottom: 6px;
            font-size: 11px;
        }

        /* Financial highlights */
        .financial-highlights {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2c5aa0;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-size: 10px;
            color: #666;
        }

        /* Page numbers */
        .page-number:after {
            content: "Page " counter(page);
        }

        /* Separator page untuk pemisah bagian */
        .separator-page {
            text-align: center;
            padding-top: 200px;
        }

        .separator-title {
            font-size: 36px;
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 20px;
        }

        .separator-subtitle {
            font-size: 20px;
            color: #666;
        }
    </style>
</head>

<body>
    @if ($mode === 'free' && !empty($watermark_logo))
        <div class="watermark-container">
            <div class="watermark-content">
                <img src="{{ $watermark_logo }}" alt="Watermark" class="watermark-logo">
            </div>
        </div>
    @endif

    <!-- Cover Page - SAMA SEPERTI BUSINESS PLAN -->
    <div class="page">
        <div style="text-align: center; margin-top: 100px;">
            <h1 style="font-size: 32px; color: #2c5aa0; margin-bottom: 30px;">
                {{ $data['business_background']->name }}
            </h1>

            <!-- Logo Below Title -->
            @if ($data['business_background']->logo)
                <div style="margin-bottom: 30px;">
                    <img src="{{ $data['business_background']->logo }}" alt="Logo"
                        style="max-width: 200px; max-height: 200px;">
                </div>
            @endif

            <h2 style="font-size: 24px; color: #666; margin-bottom: 10px;">
                BUSINESS PLAN & PROYEKSI KEUANGAN
            </h2>
            <p style="font-size: 14px; color: #888;">
                Disusun pada: {{ $generated_at }}
            </p>
            @if ($mode === 'pro')
                <p style="font-size: 12px; color: #2c5aa0; margin-top: 20px; font-weight: bold;">
                    PRO VERSION
                </p>
            @endif
        </div>

        <div style="position: absolute; bottom: 50px; left: 0; right: 0; text-align: center;">
            <p style="font-size: 12px; color: #666;">
                Dokumen ini dibuat secara otomatis oleh GRAPADI STRATEGIX System
            </p>
        </div>

        <div class="footer-mask"></div>
    </div>

    <!-- Table of Contents - SAMA SEPERTI BUSINESS PLAN -->
    <div class="page">
        <div class="header">
            <div class="company-name">{{ $data['business_background']->name }}</div>
            <div class="document-title">DAFTAR ISI</div>
        </div>

        <div class="section">
            <h3 style="font-size: 16px; color: #2c5aa0; margin-bottom: 15px;">BAGIAN I: LATAR BELAKANG UMUM & LEGAL</h3>
            <ol style="line-height: 2; font-size: 14px; margin-bottom: 30px;">
                <li>Ringkasan Eksekutif</li>
                <li>Latar Belakang Usaha</li>
                <li>Profil Usaha {{ $data['business_background']->name }}</li>
                <li>Visi, Misi, dan Tujuan Usaha</li>
                <li>Bentuk Badan Usaha dan Legalitas</li>
                <li>Struktur Organisasi dan Tim Manajemen</li>
                <li>Rencana Operasional Usaha</li>
            </ol>

            <h3 style="font-size: 16px; color: #2c5aa0; margin-bottom: 15px;">BAGIAN II: ASPEK PASAR
                ({{ $period_label }})</h3>
            <ol style="line-height: 2; font-size: 14px;" start="8">
                <li>Gambaran Umum Industri, Tren Pasar, dan Target Pasar</li>
                <li>Analisis Kompetitor</li>
                <li>Analisis SWOT</li>
                <li>Produk dan Layanan</li>
                <li>Strategi Pemasaran dan Penjualan</li>
                <li>Proyeksi Penjualan dan Tren Bulanan</li>
            </ol>

            <h3 style="font-size: 16px; color: #2c5aa0; margin-bottom: 15px;">BAGIAN III: ASPEK KEUANGAN</h3>
            <ol style="line-height: 2; font-size: 14px;" start="14">
                <li>Proyeksi Laporan Keuangan</li>
                <li>Proyeksi Keuangan 5 Tahun</li>
                <li>Ringkasan Eksekutif Keuangan</li>
                <li>Auto Insights & Analisis</li>
            </ol>
        </div>
    </div>

    {{-- ========================================
         BAGIAN 1: BUSINESS PLAN
         Copy PERSIS dari business-plan.blade.php
    ========================================= --}}

    <!-- Separator: BAGIAN 1 -->
    <div class="page">
        <div class="separator-page">
            <div class="separator-title">BAGIAN I</div>
            <div class="separator-subtitle">LATAR BELAKANG UMUM & LEGAL</div>
        </div>
        <div class="footer-mask"></div>
    </div>

    <!-- Executive Summary -->
    <div class="page">
        <div class="header">
            <div class="company-name">{{ $data['business_background']->name }}</div>
            <div class="document-title">1. RINGKASAN EKSEKUTIF</div>
        </div>

        <div class="section">
            @php
                $executiveSummaryParagraphs = splitLongText($executiveSummary, 3);
            @endphp

            @foreach ($executiveSummaryParagraphs as $para)
                <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                    {{ $para }}
                </p>
            @endforeach
        </div>
    </div>

    <!-- Halaman 2: Latar Belakang Usaha -->
    <div class="page">
        <div class="header">
            <div class="company-name">{{ $data['business_background']->name }}</div>
            <div class="document-title">2. LATAR BELAKANG USAHA</div>
        </div>

        <div class="section">
            <div class="subsection">
                <div class="subsection-title">Deskripsi Bisnis</div>
                @php
                    $descriptionParagraphs = splitLongText($data['business_background']->description, 3);
                @endphp
                @foreach ($descriptionParagraphs as $para)
                    <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                        {{ $para }}
                    </p>
                @endforeach
            </div>

            @if ($data['business_background']->business_overview)
                <div class="subsection">
                    <div class="subsection-title">Gambaran Umum Usaha</div>
                    @php
                        $overviewParagraphs = splitLongText($data['business_background']->business_overview, 3);
                    @endphp
                    @foreach ($overviewParagraphs as $para)
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            {{ $para }}
                        </p>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Halaman 3: Profil Usaha -->
    <div class="page">
        <div class="header">
            <div class="company-name">{{ $data['business_background']->name }}</div>
            <div class="document-title">3. PROFIL USAHA {{ strtoupper($data['business_background']->name) }}</div>
        </div>

        <div class="section">
            <!-- Deskripsi Profil -->
            <div class="subsection">
                <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                    {{ $data['business_background']->name }} merupakan sebuah bisnis yang bergerak di bidang
                    <strong>{{ $data['business_background']->category }}</strong> dengan tipe usaha
                    <strong>{{ $data['business_background']->business_type }}</strong>.
                    Usaha ini didirikan pada
                    <strong>{{ $data['business_background']->start_date ? date('d F Y', strtotime($data['business_background']->start_date)) : 'periode yang tidak ditentukan' }}</strong>
                    dan berlokasi di <strong>{{ $data['business_background']->location }}</strong>.
                    Dengan fokus pada industri ini, {{ $data['business_background']->name }} berkomitmen untuk
                    memberikan produk/layanan berkualitas kepada pelanggan.
                </p>
            </div>

            <!-- Tabel Informasi Umum -->
            <div class="subsection">
                <div class="subsection-title">Informasi Umum</div>
                <table class="table">
                    <tr>
                        <td style="width: 30%;"><strong>Nama Bisnis</strong></td>
                        <td>{{ $data['business_background']->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Kategori</strong></td>
                        <td>{{ $data['business_background']->category }}</td>
                    </tr>
                    <tr>
                        <td><strong>Lokasi</strong></td>
                        <td>{{ $data['business_background']->location }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tipe Bisnis</strong></td>
                        <td>{{ $data['business_background']->business_type }}</td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal Mulai</strong></td>
                        <td>{{ $data['business_background']->start_date ? date('d F Y', strtotime($data['business_background']->start_date)) : '-' }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Halaman 4: Visi, Misi, dan Tujuan Usaha -->
    <div class="page">
        <div class="header">
            <div class="company-name">{{ $data['business_background']->name }}</div>
            <div class="document-title">4. VISI, MISI, DAN TUJUAN USAHA</div>
        </div>

        <div class="section">
            @if ($data['business_background']->vision)
                <div class="subsection">
                    <div class="subsection-title">Visi</div>
                    @php
                        $visionParagraphs = splitLongText($data['business_background']->vision, 2);
                    @endphp
                    @foreach ($visionParagraphs as $para)
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            {{ $para }}
                        </p>
                    @endforeach
                </div>
            @endif

            @if ($data['business_background']->mission)
                <div class="subsection">
                    <div class="subsection-title">Misi</div>
                    @php
                        $missionParagraphs = splitLongText($data['business_background']->mission, 2);
                    @endphp
                    @foreach ($missionParagraphs as $para)
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            {{ $para }}
                        </p>
                    @endforeach
                </div>
            @endif

            @if ($data['business_background']->purpose)
                <div class="subsection">
                    <div class="subsection-title">Tujuan Bisnis</div>
                    @php
                        $purposeParagraphs = splitLongText($data['business_background']->purpose, 2);
                    @endphp
                    @foreach ($purposeParagraphs as $para)
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            {{ $para }}
                        </p>
                    @endforeach
                </div>
            @endif

            @if ($data['business_background']->business_objectives)
                <div class="subsection">
                    <div class="subsection-title">Maksud & Tujuan Pendirian Usaha</div>
                    @php
                        $objectivesParagraphs = splitLongText($data['business_background']->business_objectives, 2);
                    @endphp
                    @foreach ($objectivesParagraphs as $para)
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            {{ $para }}
                        </p>
                    @endforeach
                </div>
            @endif

            @if ($data['business_background']->values)
                <div class="subsection">
                    <div class="subsection-title">Nilai-Nilai</div>
                    @php
                        $valuesParagraphs = splitLongText($data['business_background']->values, 2);
                    @endphp
                    @foreach ($valuesParagraphs as $para)
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            {{ $para }}
                        </p>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Halaman 5: Bentuk Badan Usaha dan Legalitas -->
    <div class="page">
        <div class="header">
            <div class="company-name">{{ $data['business_background']->name }}</div>
            <div class="document-title">5. BENTUK BADAN USAHA DAN LEGALITAS</div>
        </div>

        <div class="section">
            @if ($data['business_background']->business_legality)
                <div class="subsection">
                    <div class="subsection-title">Legalitas Usaha</div>
                    @php
                        $legalityParagraphs = splitLongText($data['business_background']->business_legality, 3);
                    @endphp
                    @foreach ($legalityParagraphs as $para)
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            {{ $para }}
                        </p>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Halaman 6: Struktur Organisasi dan Tim Manajemen -->
    @if ($data['team_structures']->count() > 0)
        <div class="page">
            <div class="header">
                <div class="company-name">{{ $data['business_background']->name }}</div>
                <div class="document-title">6. STRUKTUR ORGANISASI DAN TIM MANAJEMEN</div>
            </div>

            <div class="section">
                @php
                    $groupedTeams = $data['team_structures']->groupBy('team_category');
                    $totalSalary = $data['team_structures']->where('status', 'active')->sum('salary');
                @endphp

                <!-- Summary Statistics - Pindah ke atas -->
                <div style="margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e5e7eb;">
                    <p style="font-size: 11px; color: #666; line-height: 1.8; margin: 0;">
                        <strong style="color: #2c5aa0;">Total Anggota:</strong>
                        {{ $data['team_structures']->count() }} orang
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        <strong style="color: #2c5aa0;">Kategori Tim:</strong> {{ $groupedTeams->count() }} kategori
                        &nbsp;&nbsp;|&nbsp;&nbsp;
                        <strong style="color: #10b981;">Anggota Aktif:</strong>
                        {{ $data['team_structures']->where('status', 'active')->count() }} orang
                    </p>
                </div>

                <!-- Org Chart Image - Jika ada -->
                @if ($data['business_background']->org_chart_image)
                    <div style="margin-bottom: 25px; text-align: center; page-break-inside: avoid;">
                        <h4 style="font-size: 12px; font-weight: bold; color: #2c5aa0; margin-bottom: 10px;">
                            Struktur Organisasi
                        </h4>
                        <img src="{{ $data['business_background']->org_chart_image }}" alt="Organization Chart"
                            style="max-width: 100%; height: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
                    </div>
                @endif

                @foreach ($groupedTeams as $category => $members)
                    <div style="margin-bottom: 30px; page-break-inside: avoid;">
                        <!-- Category Header - Sederhana -->
                        <h3
                            style="font-size: 14px; font-weight: bold; color: #2c5aa0; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #2c5aa0;">
                            Bagian {{ $category }}
                        </h3>

                        <!-- Member Details Table -->
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 4%;">No</th>
                                    <th style="width: 18%;">Nama</th>
                                    <th style="width: 13%;">Posisi</th>
                                    <th style="width: 14%;">Gaji</th>
                                    <th style="width: 26%;">Job Desk</th>
                                    <th style="width: 25%;">Pengalaman</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($members as $index => $member)
                                    <tr>
                                        <td style="text-align: center;">{{ $index + 1 }}</td>
                                        <td style="font-weight: bold;">{{ $member->member_name }}</td>
                                        <td style="font-style: italic;">{{ $member->position }}</td>
                                        <td style="font-size: 9px; font-weight: bold; color: #059669;">
                                            Rp {{ number_format($member->salary, 0, ',', '.') }}
                                        </td>
                                        <td style="font-size: 8px; line-height: 1.6;">
                                            {{ str_replace(['\n', '\\n'], ' • ', $member->jobdesk ?? '-') }}
                                        </td>
                                        <td style="font-size: 8px; line-height: 1.6;">
                                            {{ str_replace(['\n', '\\n'], ' ', $member->experience ?? '-') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                <!-- Total Salary Summary -->
                <div
                    style="margin-top: 20px; padding: 15px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 4px;">
                    <p style="font-size: 11px; color: #065f46; margin: 0; line-height: 1.6;">
                        <strong style="font-size: 12px;">Total Gaji Karyawan (Bulanan)</strong><br>
                        <span style="font-size: 16px; font-weight: bold; color: #059669;">
                            Rp {{ number_format($totalSalary, 0, ',', '.') }}
                        </span>
                        <span style="font-size: 9px; color: #666; display: block; margin-top: 4px;">
                            Berdasarkan {{ $data['team_structures']->where('status', 'active')->count() }} karyawan
                            aktif
                        </span>
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Halaman 7: Rencana Operasional Usaha -->
    @if ($data['operational_plans']->count() > 0)
        <div class="page">
            <div class="header">
                <div class="company-name">{{ $data['business_background']->name }}</div>
                <div class="document-title">7. RENCANA OPERASIONAL USAHA</div>
            </div>

            <div class="section">
                @foreach ($data['operational_plans'] as $plan)
                    <!-- GAMBAR DIAGRAM ALUR KERJA -->
                    @if (isset($workflowImages[$plan->id]))
                        <div style="margin-bottom: 15px; padding: 15px; background: #f0f4ff; border-radius: 8px;">
                            <h3 style="margin: 0 0 10px 0; font-size: 14px; font-weight: bold;">
                                Gambar Diagram Alur Kerja</h3>
                            <div style="text-align: center; background: #ffffff; padding: 10px; border-radius: 4px;">
                                <img src="{{ $workflowImages[$plan->id] }}"
                                    style="width: 100%; max-width: 300px; height: auto; display: block; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; background: #ffffff;"
                                    alt="Workflow Image {{ $plan->business_location }}" />
                            </div>
                        </div>
                    @endif

                    <!-- TABEL RENCANA OPERASIONAL -->
                    <table class="table" style="margin-bottom: 30px;">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Aspek Operasional</th>
                                <th style="width: 80%;">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Lokasi Bisnis</strong></td>
                                <td>{{ $plan->business_location }}</td>
                            </tr>
                            @if ($plan->location_description)
                                @php
                                    // Convert \n literal strings to actual newlines and chunk to allow page breaks
                                    $locationText = str_replace(['\\n', '\n'], "\n", $plan->location_description);
                                    $locationLines = preg_split('/[\r\n]+/', trim($locationText));
                                    $locationLines = array_filter(array_map('trim', $locationLines));
                                    $locationChunks = array_chunk($locationLines, 8); // 8 lines per chunk
                                @endphp
                                @foreach ($locationChunks as $chunkIndex => $chunk)
                                    <tr>
                                        <td><strong>{{ $chunkIndex === 0 ? 'Deskripsi Lokasi' : '' }}</strong></td>
                                        <td style="font-size: 9px; line-height: 1.8;">
                                            @foreach ($chunk as $lineIndex => $line)
                                                @if ($lineIndex > 0)
                                                    <br />
                                                @endif
                                                - {{ $line }}
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            <tr>
                                <td><strong>Tipe Lokasi</strong></td>
                                <td>{{ $plan->location_type }}</td>
                            </tr>
                            @if ($plan->location_size)
                                <tr>
                                    <td><strong>Ukuran Lokasi</strong></td>
                                    <td>{{ number_format($plan->location_size, 0, ',', '.') }} m²</td>
                                </tr>
                            @endif
                            @if ($plan->rent_cost)
                                <tr>
                                    <td><strong>Biaya Sewa</strong></td>
                                    <td>Rp {{ number_format($plan->rent_cost, 0, ',', '.') }}/bulan</td>
                                </tr>
                            @endif
                            @if ($plan->daily_workflow)
                                @php
                                    // Convert \n literal strings to actual newlines and chunk to allow page breaks
                                    $workflowText = str_replace(['\\n', '\n'], "\n", $plan->daily_workflow);
                                    $workflowLines = preg_split('/[\r\n]+/', trim($workflowText));
                                    $workflowLines = array_filter(array_map('trim', $workflowLines));
                                    $workflowChunks = array_chunk($workflowLines, 8);
                                @endphp
                                @foreach ($workflowChunks as $chunkIndex => $chunk)
                                    <tr>
                                        <td><strong>{{ $chunkIndex === 0 ? 'Alur Kerja Harian' : '' }}</strong></td>
                                        <td style="font-size: 9px; line-height: 1.8;">
                                            @foreach ($chunk as $lineIndex => $line)
                                                @if ($lineIndex > 0)
                                                    <br />
                                                @endif
                                                - {{ $line }}
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            @if ($plan->equipment_needs)
                                @php
                                    // Convert \n literal strings to actual newlines and chunk to allow page breaks
                                    $equipmentText = str_replace(['\\n', '\n'], "\n", $plan->equipment_needs);
                                    $equipmentLines = preg_split('/[\r\n]+/', trim($equipmentText));
                                    $equipmentLines = array_filter(array_map('trim', $equipmentLines));
                                    $equipmentChunks = array_chunk($equipmentLines, 8);
                                @endphp
                                @foreach ($equipmentChunks as $chunkIndex => $chunk)
                                    <tr>
                                        <td><strong>{{ $chunkIndex === 0 ? 'Kebutuhan Peralatan' : '' }}</strong></td>
                                        <td style="font-size: 9px; line-height: 1.8;">
                                            @foreach ($chunk as $lineIndex => $line)
                                                @if ($lineIndex > 0)
                                                    <br />
                                                @endif
                                                - {{ $line }}
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            @if ($plan->technology_stack)
                                @php
                                    // Convert \n literal strings to actual newlines and chunk to allow page breaks
                                    $techText = str_replace(['\\n', '\n'], "\n", $plan->technology_stack);
                                    $techLines = preg_split('/[\r\n]+/', trim($techText));
                                    $techLines = array_filter(array_map('trim', $techLines));
                                    $techChunks = array_chunk($techLines, 8);
                                @endphp
                                @foreach ($techChunks as $chunkIndex => $chunk)
                                    <tr>
                                        <td><strong>{{ $chunkIndex === 0 ? 'Teknologi yang Digunakan' : '' }}</strong>
                                        </td>
                                        <td style="font-size: 9px; line-height: 1.8;">
                                            @foreach ($chunk as $lineIndex => $line)
                                                @if ($lineIndex > 0)
                                                    <br />
                                                @endif
                                                - {{ $line }}
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Separator: BAGIAN 2 -->
    <div class="page">
        <div class="separator-page">
            <div class="separator-title">BAGIAN II</div>
            <div class="separator-subtitle">ASPEK PASAR</div>
            <p style="font-size: 16px; color: #666; margin-top: 20px;">Periode: {{ $period_label }}</p>
        </div>
        <div class="footer-mask"></div>
    </div>

    <!-- Section 8: Gambaran Umum Industri dan Tren Pasar -->
    @if ($data['market_analysis'])
        <div class="page">
            <div class="header">
                <div class="company-name">{{ $data['business_background']->name }}</div>
                <div class="document-title">8. GAMBARAN UMUM INDUSTRI DAN TREN PASAR (TAHUN {{ date('Y') }})</div>
            </div>

            <div class="section">
                <!-- Ukuran Pasar -->
                @if ($data['market_analysis']->market_size)
                    <div class="subsection">
                        <div class="subsection-title">Ukuran Pasar</div>
                        @php
                            $marketSizeText = str_replace(['\\n', '\n'], ' ', $data['market_analysis']->market_size);
                            $marketSizeParagraphs = splitLongText($marketSizeText, 3);
                        @endphp
                        @foreach ($marketSizeParagraphs as $para)
                            <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                                {{ $para }}
                            </p>
                        @endforeach
                    </div>
                @endif

                <!-- Analisis Ukuran Pasar (TAM, SAM, SOM) -->
                @if ($data['market_analysis']->tam_total)
                    <div class="subsection">
                        <div class="subsection-title">Analisis Ukuran Pasar</div>
                        <table class="table">
                            <tr>
                                <th>Metrik</th>
                                <th>Nilai</th>
                                <th>Keterangan</th>
                            </tr>
                            <tr>
                                <td>Total Addressable Market (TAM)</td>
                                <td>Rp {{ number_format($data['market_analysis']->tam_total, 0, ',', '.') }}</td>
                                <td>Total pasar yang tersedia</td>
                            </tr>
                            <tr>
                                <td>Serviceable Available Market (SAM)</td>
                                <td>
                                    @if ($data['market_analysis']->sam_total)
                                        Rp {{ number_format($data['market_analysis']->sam_total, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>Pasar yang dapat dilayani</td>
                            </tr>
                            <tr>
                                <td>Serviceable Obtainable Market (SOM)</td>
                                <td>
                                    @if ($data['market_analysis']->som_total)
                                        Rp {{ number_format($data['market_analysis']->som_total, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>Pasar yang dapat diraih</td>
                            </tr>
                        </table>

                        <!-- TAM/SAM/SOM Pie Chart -->
                        @if (isset($marketAnalysisCharts['tam_sam_som']))
                            <div style="text-align: center; margin-top: 15px;">
                                <img src="{{ $marketAnalysisCharts['tam_sam_som'] }}" alt="TAM/SAM/SOM Chart"
                                    class="chart-image">
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Tren Pasar -->
                @if ($data['market_analysis']->market_trends)
                    <div class="subsection">
                        <div class="subsection-title">Tren Pasar</div>
                        @php
                            $trendsText = str_replace(['\\n', '\n'], ' ', $data['market_analysis']->market_trends);
                            $trendsParagraphs = splitLongText($trendsText, 3);
                        @endphp
                        @foreach ($trendsParagraphs as $para)
                            <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                                {{ $para }}
                            </p>
                        @endforeach
                    </div>
                @endif

                <!-- Target Pasar -->
                @if ($data['market_analysis']->target_market)
                    <div class="subsection">
                        <div class="subsection-title">Target Pasar</div>
                        @php
                            $targetMarketText = str_replace(
                                ['\\n', '\n'],
                                ' ',
                                $data['market_analysis']->target_market,
                            );
                            $targetMarketParagraphs = splitLongText($targetMarketText, 3);
                        @endphp
                        @foreach ($targetMarketParagraphs as $para)
                            <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                                {{ $para }}
                            </p>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Section 9: Analisis Kompetitor -->
    @if ($data['market_analysis'])
        @if ($data['market_analysis']->competitors->count() > 0)
            <div class="page">
                <div class="header">
                    <div class="company-name">{{ $data['business_background']->name }}</div>
                    <div class="document-title">9. ANALISIS KOMPETITOR</div>
                </div>

                <div class="section">
                    <!-- Tabel Detail Kompetitor langsung di halaman -->
                    <table class="table" style="margin-top: 0;">
                        <tr>
                            <th>Nama Kompetitor</th>
                            <th>Tipe</th>
                            <th>Alamat</th>
                            <th>Penjualan Tahunan</th>
                            <th>Harga Jual</th>
                            <th>Kelebihan</th>
                            <th>Kekurangan</th>
                        </tr>
                        @foreach ($data['market_analysis']->competitors as $competitor)
                            <tr>
                                <td>{{ $competitor->competitor_name }}</td>
                                <td>{{ $competitor->type === 'ownshop' ? 'Usaha Sendiri' : 'Kompetitor' }}</td>
                                <td>{{ $competitor->address ?? '-' }}</td>
                                <td>
                                    @if ($competitor->annual_sales_estimate)
                                        Rp {{ number_format($competitor->annual_sales_estimate, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($competitor->selling_price)
                                        Rp {{ number_format($competitor->selling_price, 0, ',', '.') }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td style="font-size: 8px;">
                                    {{ str_replace(['\n', '\\n'], ' • ', $competitor->strengths ?? '-') }}
                                </td>
                                <td style="font-size: 8px;">
                                    {{ str_replace(['\n', '\\n'], ' • ', $competitor->weaknesses ?? '-') }}
                                </td>
                            </tr>
                        @endforeach
                    </table>

                    <!-- Kompetitor Utama -->
                    @if ($data['market_analysis']->main_competitors)
                        <div class="subsection">
                            <div class="subsection-title">Kompetitor Utama</div>
                            @php
                                $mainCompText = str_replace(
                                    ['\\n', '\n'],
                                    ' ',
                                    $data['market_analysis']->main_competitors,
                                );
                                $mainCompetitorsParagraphs = splitLongText($mainCompText, 3);
                            @endphp
                            @foreach ($mainCompetitorsParagraphs as $para)
                                <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                                    {{ $para }}
                                </p>
                            @endforeach
                        </div>
                    @endif

                    <!-- Kelebihan & Kekurangan Kompetitor -->
                    @if ($data['market_analysis']->competitor_strengths || $data['market_analysis']->competitor_weaknesses)
                        <div class="subsection">
                            <div class="subsection-title">Analisis Kelebihan & Kekurangan Kompetitor</div>
                            <table class="table">
                                <tr>
                                    <th style="width: 50%;">Kekuatan Kompetitor</th>
                                    <th style="width: 50%;">Kelemahan Kompetitor</th>
                                </tr>
                                <tr>
                                    <td style="vertical-align: top; line-height: 1.8;">
                                        {{ str_replace(['\n', '\\n'], ' • ', $data['market_analysis']->competitor_strengths ?? '-') }}
                                    </td>
                                    <td style="vertical-align: top; line-height: 1.8;">
                                        {{ str_replace(['\n', '\\n'], ' • ', $data['market_analysis']->competitor_weaknesses ?? '-') }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endif

                    <!-- Keunggulan Kompetitif -->
                    @if ($data['market_analysis']->competitive_advantage)
                        <div class="subsection">
                            <div class="subsection-title">Keunggulan Kompetitif</div>
                            @php
                                $compAdvText = str_replace(
                                    ['\\n', '\n'],
                                    ' ',
                                    $data['market_analysis']->competitive_advantage,
                                );
                                $competitiveAdvantageParagraphs = splitLongText($compAdvText, 3);
                            @endphp
                            @foreach ($competitiveAdvantageParagraphs as $para)
                                <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                                    {{ $para }}
                                </p>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    <!-- Section 11: Analisis SWOT -->
    @if ($data['market_analysis'])
        <div class="page">
            <div class="header">
                <div class="company-name">{{ $data['business_background']->name }}</div>
                <div class="document-title">11. ANALISIS SWOT</div>
            </div>

            <div class="section">
                @if (
                    $data['market_analysis']->strengths ||
                        $data['market_analysis']->weaknesses ||
                        $data['market_analysis']->opportunities ||
                        $data['market_analysis']->threats)
                    <!-- Penjelasan SWOT -->
                    <div class="subsection">
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            Analisis SWOT merupakan alat strategis yang digunakan untuk mengevaluasi posisi
                            {{ $data['business_background']->name }} di pasar.
                            Analisis ini mengidentifikasi kekuatan internal yang dapat dimanfaatkan, kelemahan yang
                            perlu diperbaiki, serta peluang dan ancaman eksternal yang mempengaruhi pertumbuhan bisnis.
                            Dengan pemahaman mendalam tentang faktor-faktor ini,
                            {{ $data['business_background']->name }} dapat mengembangkan strategi yang lebih efektif
                            untuk mencapai tujuan bisnis.
                        </p>
                    </div>

                    <!-- Tabel Detail SWOT -->
                    <div class="subsection">
                        <table class="table">
                            <tr>
                                <th style="width: 30%;">Kategori</th>
                                <th style="width: 70%;">Deskripsi</th>
                            </tr>
                            <tr>
                                <td><strong>Strengths (Kekuatan)</strong></td>
                                <td style="line-height: 1.8;">
                                    {{ str_replace(['\n', '\\n'], ' • ', $data['market_analysis']->strengths) }}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Weaknesses (Kelemahan)</strong></td>
                                <td style="line-height: 1.8;">
                                    {{ str_replace(['\n', '\\n'], ' • ', $data['market_analysis']->weaknesses) }}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Opportunities (Peluang)</strong></td>
                                <td style="line-height: 1.8;">
                                    {{ str_replace(['\n', '\\n'], ' • ', $data['market_analysis']->opportunities) }}
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Threats (Ancaman)</strong></td>
                                <td style="line-height: 1.8;">
                                    {{ str_replace(['\n', '\\n'], ' • ', $data['market_analysis']->threats) }}
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Analisis Kesimpulan SWOT -->
                    <div class="subsection">
                        <div class="subsection-title">Kesimpulan Analisis SWOT</div>
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            Berdasarkan analisis SWOT di atas, {{ $data['business_background']->name }} memiliki
                            beberapa kekuatan yang dapat menjadi fondasi pengembangan bisnis.
                            @if ($data['market_analysis']->strengths)
                                Kekuatan utama terletak pada
                                {{ strtolower(Str::limit($data['market_analysis']->strengths, 80, '...')) }},
                            @endif
                            yang dapat dimanfaatkan untuk merebut peluang pasar yang ada.
                            @if ($data['market_analysis']->opportunities)
                                Peluang bisnis yang signifikan mencakup
                                {{ strtolower(Str::limit($data['market_analysis']->opportunities, 80, '...')) }}.
                            @endif
                        </p>
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            Namun demikian, perlu perhatian khusus terhadap kelemahan internal dan ancaman eksternal
                            untuk memastikan keberlanjutan bisnis.
                            @if ($data['market_analysis']->weaknesses)
                                Kelemahan yang perlu dibenahi meliputi
                                {{ strtolower(Str::limit($data['market_analysis']->weaknesses, 80, '...')) }},
                            @endif
                            sementara
                            @if ($data['market_analysis']->threats)
                                ancaman potensial dari pasar antara lain
                                {{ strtolower(Str::limit($data['market_analysis']->threats, 80, '...')) }}.
                            @endif
                            Dengan strategi yang tepat untuk meminimalkan kelemahan dan mengantisipasi ancaman,
                            {{ $data['business_background']->name }} dapat memaksimalkan pertumbuhan dan pencapaian
                            target bisnis.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Section 12: Produk dan Layanan -->
    @if ($data['products_services']->count() > 0)
        <div class="page">
            <div class="header">
                <div class="company-name">{{ $data['business_background']->name }}</div>
                <div class="document-title">12. PRODUK DAN LAYANAN</div>
            </div>

            <div class="section">
                @foreach ($data['products_services'] as $product)
                    <div class="subsection" style="page-break-inside: avoid;">
                        <div class="subsection-title">
                            {{ $product->name }} ({{ $product->type === 'product' ? 'Produk' : 'Layanan' }})
                        </div>

                        <table class="table">
                            <tr>
                                <td style="width: 20%;"><strong>Deskripsi</strong></td>
                                <td style="line-height: 1.8;">
                                    {{ str_replace(['\n', '\\n'], ' ', $product->description) }}
                                </td>
                            </tr>
                            @if ($product->price)
                                <tr>
                                    <td><strong>Harga</strong></td>
                                    <td>Rp {{ number_format($product->price, 0, ',', '.') }}</td>
                                </tr>
                            @endif
                            @if ($product->advantages)
                                <tr>
                                    <td><strong>Keunggulan</strong></td>
                                    <td style="line-height: 1.8;">
                                        {{ str_replace(['\n', '\\n'], ' • ', $product->advantages) }}
                                    </td>
                                </tr>
                            @endif
                            @if ($product->development_strategy)
                                <tr>
                                    <td><strong>Strategi Pengembangan</strong></td>
                                    <td style="line-height: 1.8;">
                                        {{ str_replace(['\n', '\\n'], ' ', $product->development_strategy) }}
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td><strong>Status</strong></td>
                                <td>
                                    @if ($product->status == 'available')
                                        Tersedia
                                    @elseif($product->status == 'development')
                                        Dalam Pengembangan
                                    @else
                                        {{ $product->status }}
                                    @endif
                                </td>
                            </tr>
                        </table>

                        <!-- Business Model Canvas (BMC) -->
                        @if (
                            $product->bmc_alignment &&
                                (!empty($product->bmc_alignment['value_proposition']) ||
                                    !empty($product->bmc_alignment['customer_segment']) ||
                                    !empty($product->bmc_alignment['channels']) ||
                                    !empty($product->bmc_alignment['customer_relationships']) ||
                                    !empty($product->bmc_alignment['revenue_streams']) ||
                                    !empty($product->bmc_alignment['key_resources']) ||
                                    !empty($product->bmc_alignment['key_activities']) ||
                                    !empty($product->bmc_alignment['key_partnerships']) ||
                                    !empty($product->bmc_alignment['cost_structure'])))
                            <div style="margin-top: 15px;">
                                <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 10px; color: #2c5aa0;">
                                    Business Model Canvas Alignment
                                </h4>
                                <table class="table">
                                    @if (!empty($product->bmc_alignment['value_proposition']))
                                        <tr>
                                            <td style="width: 30%;"><strong>Value Proposition</strong></td>
                                            <td style="line-height: 1.8;">
                                                {{ str_replace(['\n', '\\n'], ' ', $product->bmc_alignment['value_proposition']) }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if (!empty($product->bmc_alignment['customer_segment']))
                                        <tr>
                                            <td><strong>Customer Segments</strong></td>
                                            <td style="line-height: 1.8;">
                                                {{ str_replace(['\n', '\\n'], ' ', $product->bmc_alignment['customer_segment']) }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if (!empty($product->bmc_alignment['channels']))
                                        <tr>
                                            <td><strong>Channels</strong></td>
                                            <td style="line-height: 1.8;">
                                                {{ str_replace(['\n', '\\n'], ' ', $product->bmc_alignment['channels']) }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if (!empty($product->bmc_alignment['customer_relationships']))
                                        <tr>
                                            <td><strong>Customer Relationships</strong></td>
                                            <td style="line-height: 1.8;">
                                                {{ str_replace(['\n', '\\n'], ' ', $product->bmc_alignment['customer_relationships']) }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if (!empty($product->bmc_alignment['revenue_streams']))
                                        <tr>
                                            <td><strong>Revenue Streams</strong></td>
                                            <td style="line-height: 1.8;">
                                                {{ str_replace(['\n', '\\n'], ' ', $product->bmc_alignment['revenue_streams']) }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if (!empty($product->bmc_alignment['key_resources']))
                                        <tr>
                                            <td><strong>Key Resources</strong></td>
                                            <td style="line-height: 1.8;">
                                                {{ str_replace(['\n', '\\n'], ' ', $product->bmc_alignment['key_resources']) }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if (!empty($product->bmc_alignment['key_activities']))
                                        <tr>
                                            <td><strong>Key Activities</strong></td>
                                            <td style="line-height: 1.8;">
                                                {{ str_replace(['\n', '\\n'], ' ', $product->bmc_alignment['key_activities']) }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if (!empty($product->bmc_alignment['key_partnerships']))
                                        <tr>
                                            <td><strong>Key Partnerships</strong></td>
                                            <td style="line-height: 1.8;">
                                                {{ str_replace(['\n', '\\n'], ' ', $product->bmc_alignment['key_partnerships']) }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if (!empty($product->bmc_alignment['cost_structure']))
                                        <tr>
                                            <td><strong>Cost Structure</strong></td>
                                            <td style="line-height: 1.8;">
                                                {{ str_replace(['\n', '\\n'], ' ', $product->bmc_alignment['cost_structure']) }}
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Section 13: Strategi Pemasaran dan Penjualan -->
    @if ($data['marketing_strategies']->count() > 0)
        <div class="page">
            <div class="header">
                <div class="company-name">{{ $data['business_background']->name }}</div>
                <div class="document-title">13. STRATEGI PEMASARAN DAN PENJUALAN</div>
            </div>

            <div class="section" style="margin-top: 5px; margin-bottom: 0;">
                @foreach ($data['marketing_strategies'] as $strategy)
                    <div style="margin-bottom: 12px;">
                        <table class="table">
                            <tr>
                                <td style="width: 20%;"><strong>Strategi Promosi</strong></td>
                                <td style="line-height: 1.8;">
                                    {{ str_replace(['\n', '\\n'], ' ', $strategy->promotion_strategy ?? '-') }}
                                </td>
                            </tr>
                            @if ($strategy->media_used)
                                <tr>
                                    <td><strong>Media yang Digunakan</strong></td>
                                    <td style="line-height: 1.8;">
                                        {{ str_replace(['\n', '\\n'], ' • ', $strategy->media_used) }}
                                    </td>
                                </tr>
                            @endif
                            @if ($strategy->pricing_strategy)
                                <tr>
                                    <td><strong>Strategi Harga</strong></td>
                                    <td style="line-height: 1.8;">
                                        {{ str_replace(['\n', '\\n'], ' ', $strategy->pricing_strategy) }}
                                    </td>
                                </tr>
                            @endif
                            @if ($strategy->monthly_target)
                                <tr>
                                    <td><strong>Target Bulanan</strong></td>
                                    <td>{{ $strategy->monthly_target }}</td>
                                </tr>
                            @endif
                            @if ($strategy->collaboration_plan)
                                <tr>
                                    <td><strong>Rencana Kolaborasi</strong></td>
                                    <td style="line-height: 1.8;">
                                        {{ str_replace(['\n', '\\n'], ' ', $strategy->collaboration_plan) }}
                                    </td>
                                </tr>
                            @endif
                        </table>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Section 14: Proyeksi Penjualan dan Tren Bulanan -->
    <div class="page">
        <div class="header">
            <div class="company-name">{{ $data['business_background']->name }}</div>
            <div class="document-title">14. PROYEKSI PENJUALAN DAN TREN BULANAN</div>
        </div>

        <div class="section">
            <!-- Grafik Pendapatan (Fokus Penjualan) -->
            @if (isset($financialCharts['income_vs_expense']))
                <div style="margin-bottom: 20px;">
                    <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Grafik Proyeksi Penjualan</h4>
                    <img src="{{ $financialCharts['income_vs_expense'] }}" alt="Income Chart" class="chart-image">
                    <div class="chart-analysis">
                        <div class="chart-analysis-title"> Analisis Penjualan:</div>
                        <p style="margin: 0;">
                            {{ $chartAnalyses['income_vs_expense'] ?? 'Grafik ini menunjukkan proyeksi penjualan dan tren pendapatan bisnis. Analisis lebih lanjut diperlukan untuk strategi peningkatan penjualan.' }}
                        </p>
                    </div>
                </div>
            @endif

            <!-- Tren Bulanan -->
            @if ($period_type === 'year' && isset($financial_summary['monthly_summary']))
                <div style="margin-top: 20px;">
                    <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 10px;">Tren Bulanan - Tahun
                        {{ $financial_summary['year'] }}
                    </h4>
                    <table class="table">
                        <tr>
                            <th>Bulan</th>
                            <th>Pendapatan</th>
                            <th>Pengeluaran</th>
                            <th>Laba Bersih</th>
                            <th>Transaksi</th>
                        </tr>
                        @foreach ($financial_summary['monthly_summary'] as $month)
                            <tr>
                                <td>{{ $month['month_name'] }}</td>
                                <td class="text-green">Rp {{ number_format($month['income'], 0, ',', '.') }}</td>
                                <td class="text-red">Rp {{ number_format($month['expense'], 0, ',', '.') }}</td>
                                <td class="{{ $month['net_profit'] >= 0 ? 'text-green' : 'text-red' }}">
                                    Rp {{ number_format($month['net_profit'], 0, ',', '.') }}
                                </td>
                                <td>{{ $month['transaction_count'] }}</td>
                            </tr>
                        @endforeach
                    </table>

                    @if (isset($financialCharts['monthly_trend']))
                        <div style="margin-top: 15px;">
                            <img src="{{ $financialCharts['monthly_trend'] }}" alt="Monthly Trend Chart"
                                class="chart-image">
                            <div class="chart-analysis">
                                <div class="chart-analysis-title"> Analisis:</div>
                                <p style="margin: 0;">
                                    {{ $chartAnalyses['monthly_trend'] ?? 'Data tren bulanan tidak tersedia.' }}
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ========================================
         BAGIAN III: ASPEK KEUANGAN
    ========================================= --}}

    <!-- Separator: BAGIAN 3 -->
    <div class="page">
        <div class="separator-page">
            <div class="separator-title">BAGIAN III</div>
            <div class="separator-subtitle">ASPEK KEUANGAN</div>
            <p style="font-size: 16px; color: #666; margin-top: 20px;">Periode: {{ $period_label }}</p>
        </div>
        <div class="footer-mask"></div>
    </div>

    {{-- TODO: Comment - FinancialPlan nonaktif di Business Plan --}}
    {{--
    <!-- Financial Plans -->
    @if ($data['financial_plans']->count() > 0)
        <div class="page">
            <div class="header">
                <div class="company-name">{{ $data['business_background']->name }}</div>
        <div class="document-title">8. RENCANA KEUANGAN</div>
        </div>

        <div class="section" style="margin-top: 10px;">
            @foreach ($data['financial_plans'] as $financial)
            <div class="subsection-title" style="margin-bottom: 10px;">{{ $financial->plan_name }}</div>

            <!-- Financial Highlights -->
            <div class="financial-highlights mb-15" style="page-break-inside: avoid;">
                >
                <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Ringkasan Keuangan</h4>
                <table class="table">
                    <tr>
                        <td><strong>ROI</strong></td>
                        <td>{{ $financial->roi_percentage }}%</td>
                        <td><strong>Payback Period</strong></td>
                        <td>{{ $financial->payback_period }} bulan</td>
                    </tr>
                    <tr>
                        <td><strong>Profit Margin</strong></td>
                        <td>{{ $financial->profit_margin }}%</td>
                        <td><strong>Status Kelayakan</strong></td>
                        <td>{{ $financial->feasibility_status }}</td>
                    </tr>
                </table>
            </div>

            <!-- Capital Sources -->
            @if ($financial->capital_sources)
            <div class="mb-15" style="page-break-inside: avoid;">
                <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Sumber Modal</h4>
                <table class="table">
                    <tr>
                        <th>Sumber</th>
                        <th>Jumlah</th>
                        <th>Persentase</th>
                    </tr>
                    @foreach ($financial->capital_sources as $source)
                    <tr>
                        <td>{{ $source['source'] }}</td>
                        <td>Rp {{ number_format($source['amount'], 0, ',', '.') }}</td>
                        <td>{{ $source['percentage'] }}%</td>
                    </tr>
                    @endforeach
                    <tr class="text-bold">
                        <td>Total Modal Awal</td>
                        <td colspan="2">Rp
                            {{ number_format($financial->total_initial_capital, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>

                @if (isset($charts['capital_structure']) && $charts['capital_structure'])
                <div style="margin-top: 15px;">
                    <img src="{{ $charts['capital_structure'] }}" alt="Grafik Struktur Modal"
                        class="chart-image">
                </div>
                @endif
            </div>
            @endif

            <!-- Sales Projections -->
            @if ($financial->sales_projections)
            <div class="mb-15" style="page-break-inside: avoid;">
                <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Proyeksi Penjualan</h4>
                <table class="table">
                    <tr>
                        <th>Produk/Layanan</th>
                        <th>Harga</th>
                        <th>Volume/Bulan</th>
                        <th>Pendapatan/Bulan</th>
                    </tr>
                    @foreach ($financial->sales_projections as $projection)
                    <tr>
                        <td>{{ $projection['product'] }}</td>
                        <td>Rp {{ number_format($projection['price'], 0, ',', '.') }}</td>
                        <td>{{ number_format($projection['volume'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($projection['monthly_income'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="text-bold">
                        <td colspan="3">Total Pendapatan Bulanan</td>
                        <td>Rp {{ number_format($financial->total_monthly_income, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="text-bold">
                        <td colspan="3">Total Pendapatan Tahunan</td>
                        <td>Rp {{ number_format($financial->total_yearly_income, 0, ',', '.') }}</td>
                    </tr>
                </table>

                @if (isset($charts['revenue_streams']) && $charts['revenue_streams'])
                <div style="margin-top: 15px;">
                    <img src="{{ $charts['revenue_streams'] }}" alt="Grafik Sumber Pendapatan"
                        class="chart-image">
                </div>
                @endif
            </div>
            @endif

            <!-- Operational Expenses Breakdown -->
            @if ($financial->monthly_opex)
            <div class="mb-15" style="page-break-inside: avoid;">
                <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Breakdown Biaya
                    Operasional</h4>
                <table class="table">
                    <tr>
                        <th>Kategori Biaya</th>
                        <th>Jumlah/Bulan</th>
                    </tr>
                    @foreach ($financial->monthly_opex as $expense)
                    <tr>
                        <td>{{ $expense['category'] }}</td>
                        <td>Rp {{ number_format($expense['amount'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="text-bold">
                        <td>Total Biaya Operasional Bulanan</td>
                        <td>Rp {{ number_format($financial->total_monthly_opex, 0, ',', '.') }}</td>
                    </tr>
                </table>

                @if (isset($charts['expense_breakdown']) && $charts['expense_breakdown'])
                <div style="margin-top: 15px;">
                    <img src="{{ $charts['expense_breakdown'] }}" alt="Grafik Breakdown Biaya"
                        class="chart-image">
                </div>
                @endif
            </div>
            @endif

            <!-- Profit Loss Summary -->
            <div class="mb-15" style="page-break-inside: avoid;">
                <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Ringkasan Laba Rugi
                    (Bulanan)
                </h4>
                <table class="table">
                    <tr>
                        <td><strong>Pendapatan Bulanan</strong></td>
                        <td class="text-right">Rp
                            {{ number_format($financial->total_monthly_income, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Biaya Operasional Bulanan</strong></td>
                        <td class="text-right">Rp
                            {{ number_format($financial->total_monthly_opex, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Laba Kotor</strong></td>
                        <td class="text-right">Rp {{ number_format($financial->gross_profit, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Pajak ({{ $financial->tax_rate }}%)</strong></td>
                        <td class="text-right">Rp {{ number_format($financial->tax_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Bunga</strong></td>
                        <td class="text-right">Rp
                            {{ number_format($financial->interest_expense, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr class="text-bold">
                        <td><strong>Laba Bersih</strong></td>
                        <td class="text-right">Rp {{ number_format($financial->net_profit, 0, ',', '.') }}
                        </td>
                    </tr>
                </table>

                @if (isset($charts['profit_loss']) && $charts['profit_loss'])
                <div style="margin-top: 15px;">
                    <img src="{{ $charts['profit_loss'] }}" alt="Grafik Laba Rugi" class="chart-image">
                </div>
                @endif
            </div>

            @if ($financial->feasibility_notes)
            <div class="mb-15" style="page-break-inside: avoid;">
                <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Analisis Kelayakan</h4>

                <table class="table" style="margin-bottom: 10px;">
                    <tr>
                        <td style="width: 30%;"><strong>ROI</strong></td>
                        <td>{{ number_format($financial->roi_percentage, 2) }}%</td>
                    </tr>
                    <tr>
                        <td><strong>Payback Period</strong></td>
                        <td>{{ $financial->payback_period }} bulan</td>
                    </tr>
                    <tr>
                        <td><strong>Break Even Point</strong></td>
                        <td>Rp {{ number_format($financial->bep_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Profit Margin</strong></td>
                        <td>{{ number_format($financial->profit_margin, 2) }}%</td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td><strong>{{ $financial->feasibility_status }}</strong></td>
                    </tr>
                </table>

                <p style="margin-top: 10px;">{!! nl2br(e($financial->feasibility_notes)) !!}</p>

                @if (isset($charts['feasibility']) && $charts['feasibility'])
                <div style="margin-top: 15px;">
                    <img src="{{ $charts['feasibility'] }}" alt="Grafik Analisis Kelayakan"
                        class="chart-image">
                </div>
                @endif
            </div>
            @endif

            <!-- Financial Forecast / Cash Flow Projection -->
            @if ($financial)
            <div class="mb-15" style="page-break-inside: avoid;">
                <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Proyeksi Keuangan Masa
                    Depan</h4>

                @if ($financial->cash_flow_simulation && is_array($financial->cash_flow_simulation) && count($financial->cash_flow_simulation) > 0)
                <table class="table" style="margin-bottom: 15px;">
                    <tr>
                        <th>Periode</th>
                        <th>Pendapatan</th>
                        <th>Pengeluaran</th>
                        <th>Arus Kas</th>
                        <th>Saldo Kumulatif</th>
                    </tr>
                    @php
                    // Group cash flow by month
                    $monthlyData = [];
                    foreach ($financial->cash_flow_simulation as $flow) {
                    $date = \Carbon\Carbon::parse($flow['date']);
                    $monthKey = $date->format('Y-m');

                    if (!isset($monthlyData[$monthKey])) {
                    $monthlyData[$monthKey] = [
                    'period' => $date->format('M Y'),
                    'income' => 0,
                    'expense' => 0,
                    ];
                    }

                    if ($flow['type'] === 'income') {
                    $monthlyData[$monthKey]['income'] += floatval($flow['amount']);
                    } else {
                    $monthlyData[$monthKey]['expense'] += floatval($flow['amount']);
                    }
                    }

                    ksort($monthlyData);
                    $cumulativeBalance = 0;
                    $displayedMonths = 0;
                    $maxDisplay = 12;
                    @endphp
                    @foreach ($monthlyData as $monthKey => $monthData)
                    @if ($displayedMonths < $maxDisplay)
                        @php
                        $netCashFlow=$monthData['income'] - $monthData['expense'];
                        $cumulativeBalance +=$netCashFlow;
                        $displayedMonths++;
                        @endphp
                        <tr>
                        <td>{{ $monthData['period'] }}</td>
                        <td>Rp {{ number_format($monthData['income'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($monthData['expense'], 0, ',', '.') }}</td>
                        <td class="{{ $netCashFlow >= 0 ? 'text-green' : 'text-red' }}">
                            Rp {{ number_format($netCashFlow, 0, ',', '.') }}
                        </td>
                        <td
                            class="{{ $cumulativeBalance >= 0 ? 'text-green' : 'text-red' }}">
                            Rp {{ number_format($cumulativeBalance, 0, ',', '.') }}
                        </td>
                        </tr>
                        @endif
                        @endforeach
                </table>
                @else
                <table class="table" style="margin-bottom: 15px;">
                    <tr>
                        <th>Periode</th>
                        <th>Proyeksi Pendapatan</th>
                        <th>Proyeksi Pengeluaran</th>
                        <th>Proyeksi Laba Bersih</th>
                        <th>Saldo Kumulatif</th>
                    </tr>
                    @php
                    $cumulativeBalance = 0;
                    @endphp
                    @for ($month = 1; $month <= 12; $month++)
                        @php
                        $monthlyRevenue=$financial->total_monthly_income;
                        $monthlyExpense = $financial->total_monthly_opex;
                        $monthlyProfit = $financial->net_profit;
                        $cumulativeBalance += $monthlyProfit;
                        @endphp
                        <tr>
                            <td>Bulan {{ $month }}</td>
                            <td>Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</td>
                            <td class="{{ $monthlyProfit >= 0 ? 'text-green' : 'text-red' }}">
                                Rp {{ number_format($monthlyProfit, 0, ',', '.') }}
                            </td>
                            <td class="{{ $cumulativeBalance >= 0 ? 'text-green' : 'text-red' }}">
                                Rp {{ number_format($cumulativeBalance, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endfor
                        <tr class="text-bold">
                            <td>Total Tahun 1</td>
                            <td>Rp {{ number_format($financial->total_yearly_income, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($financial->total_monthly_opex * 12, 0, ',', '.') }}
                            </td>
                            <td>Rp {{ number_format($financial->net_profit * 12, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($cumulativeBalance, 0, ',', '.') }}</td>
                        </tr>
                </table>
                @endif

                @if (isset($charts['forecast']) && $charts['forecast'])
                <div style="margin-top: 15px;">
                    <img src="{{ $charts['forecast'] }}" alt="Grafik Proyeksi Masa Depan"
                        class="chart-image">
                </div>
                @endif
            </div>
            @endif
            @endforeach
        </div>
        </div>
        @endif
        --}}
    {{-- END: TODO Comment - FinancialPlan nonaktif di Business Plan --}}

    <!-- Section 15: Proyeksi Laporan Keuangan -->
    <div class="page">
        <div class="header">
            <div class="company-name">{{ $data['business_background']->name }}</div>
            <div class="document-title">15. PROYEKSI LAPORAN KEUANGAN</div>
        </div>

        <div class="section">
            <!-- Grafik Pendapatan vs Pengeluaran -->
            @if (isset($financialCharts['income_vs_expense']))
                <div style="margin-bottom: 20px;">
                    <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Grafik Pendapatan vs
                        Pengeluaran</h4>
                    <img src="{{ $financialCharts['income_vs_expense'] }}" alt="Income vs Expense Chart"
                        class="chart-image">
                    <div class="chart-analysis">
                        <div class="chart-analysis-title"> Analisis:</div>
                        <p style="margin: 0;">
                            {{ $chartAnalyses['income_vs_expense'] ?? 'Data analisis tidak tersedia.' }}
                        </p>
                    </div>
                </div>
            @endif

            <!-- Ringkasan Per Kategori -->
            @if (isset($financial_summary['category_summary']))
                <div style="margin-top: 25px;">
                    <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 10px;">Ringkasan Per Kategori</h4>

                    <!-- Top 5 Pendapatan -->
                    <div style="margin-bottom: 15px;">
                        <h5
                            style="font-size: 13px; color: #333; font-weight: bold; margin-top: 10px; margin-bottom: 8px;">
                            Top 5 Pendapatan</h5>
                        <table class="table">
                            <tr>
                                <th>No</th>
                                <th>Kategori</th>
                                <th>Total</th>
                                <th>Transaksi</th>
                                <th>Rata-rata</th>
                            </tr>
                            @foreach ($financial_summary['category_summary']['top_income'] as $index => $cat)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $cat['category']->name }}</td>
                                    <td class="text-green">Rp {{ number_format($cat['total'], 0, ',', '.') }}</td>
                                    <td>{{ $cat['count'] }}</td>
                                    <td>Rp {{ number_format($cat['average'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </table>

                        @if (isset($financialCharts['category_income_pie']))
                            <div style="text-align: center;">
                                <img src="{{ $financialCharts['category_income_pie'] }}" alt="Category Income Chart"
                                    class="chart-image">
                            </div>
                            <div class="chart-analysis">
                                <div class="chart-analysis-title"> Analisis:</div>
                                <p style="margin: 0;">
                                    {{ $chartAnalyses['category_income'] ?? 'Data kategori pendapatan tidak tersedia.' }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Top 5 Pengeluaran -->
                    <div style="margin-bottom: 15px;">
                        <h5
                            style="font-size: 13px; color: #333; font-weight: bold; margin-top: 10px; margin-bottom: 8px;">
                            Top 5 Pengeluaran</h5>
                        <table class="table">
                            <tr>
                                <th>No</th>
                                <th>Kategori</th>
                                <th>Total</th>
                                <th>Transaksi</th>
                                <th>Rata-rata</th>
                            </tr>
                            @foreach ($financial_summary['category_summary']['top_expense'] as $index => $cat)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $cat['category']->name }}</td>
                                    <td class="text-red">Rp {{ number_format($cat['total'], 0, ',', '.') }}</td>
                                    <td>{{ $cat['count'] }}</td>
                                    <td>Rp {{ number_format($cat['average'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </table>

                        @if (isset($financialCharts['category_expense_pie']))
                            <div style="text-align: center;">
                                <img src="{{ $financialCharts['category_expense_pie'] }}"
                                    alt="Category Expense Chart" class="chart-image">
                            </div>
                            <div class="chart-analysis">
                                <div class="chart-analysis-title"> Analisis:</div>
                                <p style="margin: 0;">
                                    {{ $chartAnalyses['category_expense'] ?? 'Data kategori pengeluaran tidak tersedia.' }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Detail Proyeksi Bulanan (Forecast Results) -->
            @if ($has_forecast && $forecast_results && count($forecast_results) > 0)
                <div style="margin-top: 25px;">
                    <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 10px;">Detail Proyeksi Bulanan</h4>

                    <!-- NEW: Monthly Forecast Chart Grid -->
                    @if (!empty($monthlyForecastCharts))
                        <div style="margin-bottom: 20px;">
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                                <tr>
                                    <td style="width: 50%; padding-right: 5px;">
                                        <img src="{{ $monthlyForecastCharts['income'] }}" alt="Monthly Income"
                                            class="chart-image" style="width: 100%; height: auto;">
                                    </td>
                                    <td style="width: 50%; padding-left: 5px;">
                                        <img src="{{ $monthlyForecastCharts['expense'] }}" alt="Monthly Expense"
                                            class="chart-image" style="width: 100%; height: auto;">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width: 50%; padding-right: 5px; padding-top: 10px;">
                                        <img src="{{ $monthlyForecastCharts['profit'] }}" alt="Monthly Profit"
                                            class="chart-image" style="width: 100%; height: auto;">
                                    </td>
                                    <td style="width: 50%; padding-left: 5px; padding-top: 10px;">
                                        <img src="{{ $monthlyForecastCharts['margin'] }}" alt="Monthly Margin"
                                            class="chart-image" style="width: 100%; height: auto;">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endif

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Bulan</th>
                                <th>Prediksi Pendapatan</th>
                                <th>Prediksi Pengeluaran</th>
                                <th>Prediksi Laba Bersih</th>
                                <th>Kepercayaan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($forecast_results as $result)
                                <tr>
                                    <td>{{ date('F Y', mktime(0, 0, 0, $result['month'], 1, $result['year'])) }}</td>
                                    <td class="text-green">Rp
                                        {{ number_format($result['forecast_income'] ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td class="text-red">Rp
                                        {{ number_format($result['forecast_expense'] ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td
                                        class="{{ ($result['forecast_profit'] ?? 0) >= 0 ? 'text-green' : 'text-red' }}">
                                        Rp {{ number_format($result['forecast_profit'] ?? 0, 0, ',', '.') }}
                                    </td>
                                    <td>{{ number_format($result['confidence_level'] ?? 0, 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- NEW: Ringkasan Proyeksi Tahunan (5 Tahun) -->
                <!-- DEBUG: Check variables -->
                @php
                    error_log(
                        '🔍 BLADE DEBUG - yearly_projections: ' .
                            (isset($yearly_projections) ? 'SET (' . count($yearly_projections) . ' items)' : 'NOT SET'),
                    );
                    error_log(
                        '🔍 BLADE DEBUG - yearly_projection_chart: ' .
                            (isset($yearly_projection_chart)
                                ? 'SET (' . strlen($yearly_projection_chart) . ' chars)'
                                : 'NOT SET'),
                    );
                    error_log('🔍 BLADE DEBUG - empty check: ' . (empty($yearly_projections) ? 'EMPTY' : 'NOT EMPTY'));
                    error_log(
                        '🔍 BLADE DEBUG - chart empty check: ' .
                            (empty($yearly_projection_chart) ? 'EMPTY' : 'NOT EMPTY'),
                    );
                @endphp
                @if (!empty($yearly_projections))
                    <div style="margin-top: 25px; margin-bottom: 30px; page-break-inside: avoid;">
                        <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 15px; color: #2c5aa0;">Ringkasan
                            Proyeksi Tahunan ({{ $projectedYears ?? 5 }} Tahun Kedepan)</h4>

                        <!-- NEW: Yearly Forecast Chart Grid -->
                        @if (!empty($yearlyForecastCharts))
                            <div style="margin-bottom: 20px;">
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                                    <tr>
                                        <td style="width: 50%; padding-right: 5px;">
                                            <img src="{{ $yearlyForecastCharts['income'] }}" alt="Yearly Income"
                                                class="chart-image" style="width: 100%; height: auto;">
                                        </td>
                                        <td style="width: 50%; padding-left: 5px;">
                                            <img src="{{ $yearlyForecastCharts['expense'] }}" alt="Yearly Expense"
                                                class="chart-image" style="width: 100%; height: auto;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="width: 50%; padding-right: 5px; padding-top: 10px;">
                                            <img src="{{ $yearlyForecastCharts['profit'] }}" alt="Yearly Profit"
                                                class="chart-image" style="width: 100%; height: auto;">
                                        </td>
                                        <td style="width: 50%; padding-left: 5px; padding-top: 10px;">
                                            <img src="{{ $yearlyForecastCharts['margin'] }}" alt="Yearly Margin"
                                                class="chart-image" style="width: 100%; height: auto;">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        @elseif(isset($yearly_projection_chart) && !empty($yearly_projection_chart))
                            <!-- Fallback to original single chart if grid is not available -->
                            <div style="text-align: center; margin-bottom: 20px;">
                                <img src="{{ $yearly_projection_chart }}" alt="Grafik Proyeksi Tahunan"
                                    class="chart-image" style="max-width: 100%; width: 550px;">
                            </div>
                        @endif

                        <!-- Tabel Proyeksi Tahunan -->
                        <div style="margin-top: 15px;">
                            <p style="font-size: 11px; margin-bottom: 10px; color: #666;">
                                <strong>Proyeksi Berbasis Prediksi AI:</strong> Tabel berikut menampilkan ringkasan
                                proyeksi
                                keuangan per tahun berdasarkan hasil prediksi AI. Data dihitung dari agregasi bulanan
                                untuk
                                memberikan gambaran tren jangka panjang.
                            </p>
                            <table class="table" style="margin-top: 10px;">
                                <thead>
                                    <tr>
                                        <th>Tahun</th>
                                        <th>Total Pendapatan</th>
                                        <th>Total Pengeluaran</th>
                                        <th>Total Laba</th>
                                        <th>Margin (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($yearly_projections as $yearData)
                                        <tr>
                                            <td><strong>{{ $yearData['year'] }}</strong></td>
                                            <td class="text-green">Rp
                                                {{ number_format($yearData['income'], 0, ',', '.') }}
                                            </td>
                                            <td class="text-red">Rp
                                                {{ number_format($yearData['expense'], 0, ',', '.') }}
                                            </td>
                                            <td class="{{ $yearData['profit'] >= 0 ? 'text-green' : 'text-red' }}">
                                                Rp {{ number_format($yearData['profit'], 0, ',', '.') }}
                                            </td>
                                            <td>
                                                @php
                                                    $margin =
                                                        $yearData['income'] > 0
                                                            ? ($yearData['profit'] / $yearData['income']) * 100
                                                            : 0;
                                                @endphp
                                                <span class="{{ $margin >= 0 ? 'text-green' : 'text-red' }}">
                                                    {{ number_format($margin, 2) }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <!-- Total Row -->
                                    @php
                                        $totalIncome = collect($yearly_projections)->sum('income');
                                        $totalExpense = collect($yearly_projections)->sum('expense');
                                        $totalProfit = collect($yearly_projections)->sum('profit');
                                        $avgMargin = $totalIncome > 0 ? ($totalProfit / $totalIncome) * 100 : 0;
                                    @endphp
                                    <tr style="background-color: #f0f4f8; font-weight: bold;">
                                        <td>TOTAL</td>
                                        <td class="text-green">Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
                                        <td class="text-red">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
                                        <td class="{{ $totalProfit >= 0 ? 'text-green' : 'text-red' }}">
                                            Rp {{ number_format($totalProfit, 0, ',', '.') }}
                                        </td>
                                        <td>
                                            <span class="{{ $avgMargin >= 0 ? 'text-green' : 'text-red' }}">
                                                {{ number_format($avgMargin, 2) }}%
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Analysis Box -->
                        <div
                            style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 4px; margin-top: 15px;">
                            <p style="margin: 0; font-size: 10px; line-height: 1.6;">
                                📊 <strong>Analisis:</strong> Proyeksi menunjukkan total pendapatan sebesar
                                <strong>Rp {{ number_format($totalIncome, 0, ',', '.') }}</strong> dan total
                                pengeluaran <strong>Rp {{ number_format($totalExpense, 0, ',', '.') }}</strong> dalam
                                {{ count($yearly_projections) }}
                                tahun ke depan, dengan margin rata-rata
                                <strong>{{ number_format($avgMargin, 2) }}%</strong>.
                                @php
                                    if (count($yearly_projections) >= 2) {
                                        $firstYearIncome = $yearly_projections[0]['income'];
                                        $lastYearIncome = $yearly_projections[count($yearly_projections) - 1]['income'];
                                        $growthRate =
                                            $firstYearIncome > 0
                                                ? (($lastYearIncome - $firstYearIncome) / $firstYearIncome) * 100
                                                : 0;
                                        $growthTrend = $growthRate > 0 ? 'positif' : 'negatif';
                                    } else {
                                        $growthRate = 0;
                                        $growthTrend = 'stabil';
                                    }
                                @endphp
                                Grafik menunjukkan tren keuangan yang <strong>{{ $growthTrend }}</strong> dari tahun
                                ke tahun.
                            </p>
                        </div>
                    </div>
                @endif

            @endif
        </div>
    </div>

    <!-- Section 16: Proyeksi Keuangan 5 Tahun -->
    @if (!empty($financial_data['projections']) && count($financial_data['projections']) > 0)
        <div class="page">
            <div class="header">
                <div class="company-name">{{ $data['business_background']->name }}</div>
                <div class="document-title">16. PROYEKSI KEUANGAN 5 TAHUN</div>
            </div>

            <div class="section">
                <div style="margin-bottom: 20px;">
                    <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Ringkasan Proyeksi</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                        @foreach ($financial_data['projections'] as $projection)
                            <div class="{{ $projection->scenario_type == 'optimistic' ? 'box-optimistic' : ($projection->scenario_type == 'realistic' ? 'box-realistic' : 'box-pessimistic') }}"
                                style="border-radius: 4px; padding: 15px;">
                                <div
                                    style="font-size: 11px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase;">
                                    @if ($projection->scenario_type == 'optimistic')
                                        Optimistik
                                    @elseif($projection->scenario_type == 'realistic')
                                        Realistik
                                    @else
                                        Pesimistik
                                    @endif
                                </div>
                                <div style="margin: 6px 0; font-size: 9px;">
                                    <span style="color: #666;">NPV:</span>
                                    <span class="{{ $projection->npv >= 0 ? 'text-green' : 'text-red' }}"
                                        style="font-weight: bold; float: right;">
                                        {{ $projection->formatted_npv ?? 'Rp ' . number_format($projection->npv, 0, ',', '.') }}
                                    </span>
                                    <div style="clear: both;"></div>
                                </div>
                                <div style="margin: 6px 0; font-size: 9px;">
                                    <span style="color: #666;">ROI:</span>
                                    <span style="font-weight: bold; color: #3b82f6; float: right;">
                                        {{ $projection->formatted_roi ?? number_format($projection->roi, 2) . '%' }}
                                    </span>
                                    <div style="clear: both;"></div>
                                </div>
                                <div style="margin: 6px 0; font-size: 9px;">
                                    <span style="color: #666;">IRR:</span>
                                    <span style="font-weight: bold; float: right;">
                                        {{ $projection->formatted_irr ?? number_format($projection->irr, 2) . '%' }}
                                    </span>
                                    <div style="clear: both;"></div>
                                </div>
                                <div style="margin: 6px 0; font-size: 9px;">
                                    <span style="color: #666;">Payback Period:</span>
                                    <span style="font-weight: bold; float: right;">
                                        {{ $projection->formatted_payback ?? number_format($projection->payback_period, 1) . ' tahun' }}
                                    </span>
                                    <div style="clear: both;"></div>
                                </div>
                                <div style="margin: 6px 0; font-size: 9px;">
                                    <span style="color: #666;">Growth Rate:</span>
                                    <span
                                        style="font-weight: bold; float: right;">{{ number_format($projection->growth_rate, 1) }}%</span>
                                    <div style="clear: both;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 8px;">Detail Proyeksi Bulanan Per
                        Tahun</h4>
                    @foreach ($financial_data['projections'] as $projection)
                        <div style="margin-bottom: 15px;">
                            <strong style="font-size: 10px;">
                                Skenario {{ ucfirst($projection->scenario_type) }} (Growth:
                                {{ number_format($projection->growth_rate, 1) }}%)
                            </strong>
                            <table class="table" style="margin-top: 8px;">
                                <thead>
                                    <tr>
                                        <th>Tahun</th>
                                        <th>Pendapatan Proyeksi</th>
                                        <th>Pengeluaran Proyeksi</th>
                                        <th>Laba Bersih</th>
                                        <th>Kumulatif Profit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $years = $projection->yearly_projections ?? [];
                                        $cumulativeProfit = 0;
                                    @endphp
                                    @foreach ($years as $year)
                                        @php $cumulativeProfit += $year['net_profit']; @endphp
                                        <tr>
                                            <td>Tahun {{ $year['year'] }}</td>
                                            <td class="text-green">Rp
                                                {{ number_format($year['revenue'], 0, ',', '.') }}
                                            </td>
                                            <td class="text-red">Rp
                                                {{ number_format($year['cost'], 0, ',', '.') }}
                                            </td>
                                            <td class="{{ $year['net_profit'] >= 0 ? 'text-green' : 'text-red' }}">
                                                Rp {{ number_format($year['net_profit'], 0, ',', '.') }}
                                            </td>
                                            <td class="text-blue">Rp
                                                {{ number_format($cumulativeProfit, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Section 17: Ringkasan Eksekutif Keuangan -->
    <div class="page">
        <div class="header">
            <div class="company-name">{{ $data['business_background']->name }}</div>
            <div class="document-title">17. RINGKASAN EKSEKUTIF KEUANGAN</div>
        </div>

        <div class="section">
            <!-- Executive Summary Text -->
            <div style="margin-bottom: 20px;">
                @php
                    $execSummaryParagraphs = splitLongText(
                        $financial_summary['executive_summary'] ?? 'Data ringkasan eksekutif tidak tersedia',
                        2,
                    );
                @endphp
                @foreach ($execSummaryParagraphs as $para)
                    <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                        {{ $para }}
                    </p>
                @endforeach
            </div>

            <!-- Ringkasan Eksekutif Forecast (Jika ada) -->
            @if ($has_forecast)
                <div class="subsection" style="margin-top: 25px;">
                    <div class="subsection-title">Ringkasan Eksekutif Forecast</div>
                    @php
                        $forecastSummaryParagraphs = splitLongText($forecast_summary ?? '', 2);
                    @endphp
                    @foreach ($forecastSummaryParagraphs as $para)
                        <p style="margin-bottom: 12px; text-align: justify; line-height: 1.6;">
                            {{ $para }}
                        </p>
                    @endforeach
                </div>
            @endif

            <!-- Summary Cards -->
            @if (isset($financial_summary['summary_cards']))
                <div style="margin-top: 20px;">
                    <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 10px;">Ringkasan Metrik Keuangan</h4>
                    <table class="table">
                        <tr>
                            <th style="width: 50%;">Metrik</th>
                            <th style="width: 50%;">Nilai</th>
                        </tr>
                        <tr>
                            <td><strong>Total Pendapatan</strong></td>
                            <td class="text-green">
                                Rp
                                {{ number_format($financial_summary['summary_cards']['total_income'], 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Total Pengeluaran</strong></td>
                            <td class="text-red">
                                Rp
                                {{ number_format($financial_summary['summary_cards']['total_expense'], 0, ',', '.') }}
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Laba Bersih</strong></td>
                            <td
                                class="{{ $financial_summary['summary_cards']['net_profit'] >= 0 ? 'text-green' : 'text-red' }}">
                                <strong>
                                    Rp
                                    {{ number_format($financial_summary['summary_cards']['net_profit'], 0, ',', '.') }}
                                </strong>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Saldo Kas</strong></td>
                            <td
                                class="{{ $financial_summary['summary_cards']['cash_balance'] >= 0 ? 'text-green' : 'text-red' }}">
                                Rp
                                {{ number_format($financial_summary['summary_cards']['cash_balance'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </table>
                </div>
            @endif

            <!-- Chart Income vs Expense -->
            @if (isset($financialCharts['income_vs_expense']))
                <div style="margin-top: 15px;">
                    <h4 style="font-weight: bold; font-size: 13px; margin-bottom: 6px;">Grafik Pendapatan vs
                        Pengeluaran</h4>
                    <img src="{{ $financialCharts['income_vs_expense'] }}" alt="Income vs Expense Chart"
                        class="chart-image">
                    <div class="chart-analysis" style="margin-top: 8px;">
                        <div class="chart-analysis-title"> Analisis:</div>
                        <p style="margin: 0;">
                            {{ $chartAnalyses['income_vs_expense'] ?? 'Data analisis tidak tersedia.' }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Section 18: Auto Insights & Analisis -->
    @if ($has_forecast && $forecast_insights && count($forecast_insights) > 0)
        <div class="page">
            <div class="header">
                <div class="company-name">{{ $data['business_background']->name }}</div>
                <div class="document-title">18. AUTO INSIGHTS & ANALISIS</div>
            </div>

            <div class="section">
                <div style="text-align: justify; line-height: 1.8; margin-bottom: 20px; font-size: 11px; color: #333;">
                    <p style="margin-bottom: 12px;">
                        Berikut adalah insight otomatis yang dihasilkan oleh sistem berdasarkan analisis data keuangan
                        dan proyeksi bisnis.
                        Insight ini dikategorikan berdasarkan tingkat prioritas untuk membantu Anda mengambil keputusan
                        strategis.
                    </p>
                </div>

                @php
                    $criticalInsights = collect($forecast_insights)->where('severity', 'critical')->all();
                    $warningInsights = collect($forecast_insights)->where('severity', 'warning')->all();
                    $positiveInsights = collect($forecast_insights)->where('severity', 'positive')->all();
                    $infoInsights = collect($forecast_insights)
                        ->whereNotIn('severity', ['critical', 'warning', 'positive'])
                        ->all();
                @endphp

                @if (count($criticalInsights) > 0)
                    <div style="margin-bottom: 20px;">
                        <h4 style="font-weight: bold; font-size: 12px; margin-bottom: 10px; color: #dc2626;"> Insight
                            Kritis</h4>
                        @foreach ($criticalInsights as $insight)
                            <div
                                style="margin-bottom: 12px; padding: 12px; border-left: 3px solid #dc2626; background: #fef2f2; font-size: 11px;">
                                <div style="font-weight: bold; margin-bottom: 5px; color: #dc2626;">
                                    {{ $insight['title'] ?? 'Insight' }}
                                </div>
                                <div style="color: #333;">{{ $insight['description'] ?? '-' }}</div>
                                @if (isset($insight['value']) && $insight['value'])
                                    <div style="margin-top: 6px; font-weight: bold; color: #dc2626;">
                                        {{ $insight['value'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (count($warningInsights) > 0)
                    <div style="margin-bottom: 20px;">
                        <h4 style="font-weight: bold; font-size: 12px; margin-bottom: 10px; color: #f59e0b;">
                            Peringatan</h4>
                        @foreach ($warningInsights as $insight)
                            <div
                                style="margin-bottom: 12px; padding: 12px; border-left: 3px solid #f59e0b; background: #fffbeb; font-size: 11px;">
                                <div style="font-weight: bold; margin-bottom: 5px; color: #f59e0b;">
                                    {{ $insight['title'] ?? 'Insight' }}
                                </div>
                                <div style="color: #333;">{{ $insight['description'] ?? '-' }}</div>
                                @if (isset($insight['value']) && $insight['value'])
                                    <div style="margin-top: 6px; font-weight: bold; color: #f59e0b;">
                                        {{ $insight['value'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (count($positiveInsights) > 0)
                    <div style="margin-bottom: 20px;">
                        <h4 style="font-weight: bold; font-size: 12px; margin-bottom: 10px; color: #10b981;"> Insight
                            Positif</h4>
                        @foreach ($positiveInsights as $insight)
                            <div
                                style="margin-bottom: 12px; padding: 12px; border-left: 3px solid #10b981; background: #f0fdf4; font-size: 11px;">
                                <div style="font-weight: bold; margin-bottom: 5px; color: #10b981;">
                                    {{ $insight['title'] ?? 'Insight' }}
                                </div>
                                <div style="color: #333;">{{ $insight['description'] ?? '-' }}</div>
                                @if (isset($insight['value']) && $insight['value'])
                                    <div style="margin-top: 6px; font-weight: bold; color: #10b981;">
                                        {{ $insight['value'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if (count($infoInsights) > 0)
                    <div style="margin-bottom: 20px;">
                        <h4 style="font-weight: bold; font-size: 12px; margin-bottom: 10px; color: #3b82f6;">ℹ
                            Informasi Umum</h4>
                        @foreach ($infoInsights as $insight)
                            <div
                                style="margin-bottom: 12px; padding: 12px; border-left: 3px solid #3b82f6; background: #eff6ff; font-size: 11px;">
                                <div style="font-weight: bold; margin-bottom: 5px; color: #3b82f6;">
                                    {{ $insight['title'] ?? 'Insight' }}
                                </div>
                                <div style="color: #333;">{{ $insight['description'] ?? '-' }}</div>
                                @if (isset($insight['value']) && $insight['value'])
                                    <div style="margin-top: 6px; font-weight: bold; color: #3b82f6;">
                                        {{ $insight['value'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <div
                    style="margin-top: 20px; padding: 15px; background: #f3f4f6; border-radius: 4px; font-size: 10px; color: #666; line-height: 1.7; border-left: 4px solid #6b7280;">
                    <strong>Catatan:</strong> Insight ini dihasilkan secara otomatis berdasarkan analisis data historis
                    dan proyeksi keuangan.
                    Harap gunakan sebagai referensi dan pertimbangkan faktor-faktor eksternal lainnya dalam pengambilan
                    keputusan bisnis Anda.
                </div>
            </div>
        </div>
    @endif

    <!-- Jika tidak ada forecast, tampilkan no data page -->
    @if (!$has_forecast)
        <div class="page">
            <div class="header">
                <div class="company-name">{{ $data['business_background']->name }}</div>
                <div class="document-title">LAPORAN FORECAST</div>
            </div>

            <div class="section">
                <div style="text-align: center; padding: 60px 20px; background: #f9fafb; border-radius: 8px;">
                    <div style="font-size: 48px; color: #e5e7eb; margin-bottom: 20px;"></div>
                    <h3 style="font-size: 18px; color: #666; margin-bottom: 10px;">Data Forecast Belum Tersedia</h3>
                    <p style="font-size: 12px; color: #999;">
                        Belum ada data forecast untuk periode {{ $period_label }}.<br>
                        Silakan buat forecast terlebih dahulu untuk melihat proyeksi keuangan.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Footer untuk semua halaman -->
    <div class="footer">
        <div style="float: left;">
            {{ $data['business_background']->name }} - Laporan Lengkap ({{ $period_label }}) - Mode:
            {{ $mode === 'pro' ? 'PRO' : 'FREE' }}
        </div>
        <div style="float: right;" class="page-number"></div>
        <div style="clear: both;"></div>

        <!-- Disclaimer -->
        <div
            style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #999; text-align: justify; line-height: 1.4;">
            <strong>Disclaimer:</strong> Laporan ini disusun secara otomatis berdasarkan data yang dimasukkan oleh
            pengguna.
            Grapadi Strategix tidak bertanggung jawab atas keakuratan input maupun hasil keputusan bisnis yang diambil
            dari dokumen ini.
        </div>
    </div>
</body>

</html>
