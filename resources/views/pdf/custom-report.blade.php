<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan {{ ucfirst($type) }} - SITOKO2</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a1a1a;
            margin: 0;
            padding: 20px;
            font-size: 13px;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1a1a1a;
            margin-bottom: 30px;
            padding-bottom: 10px;
        }
        .header-left {
            text-align: left;
            vertical-align: bottom;
        }
        .header-right {
            text-align: right;
            vertical-align: bottom;
        }
        .brand {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin: 0;
            color: #1a1a1a;
        }
        .report-title {
            font-size: 18px;
            font-weight: 600;
            margin: 5px 0 0 0;
            color: #4b5563;
        }
        .meta-text {
            font-size: 12px;
            color: #6b7280;
            margin: 2px 0;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table.data-table th, table.data-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        table.data-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            text-align: left;
            border-top: 1px solid #e5e7eb;
            border-bottom: 2px solid #d1d5db;
        }
        table.data-table tbody tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .text-right {
            text-align: right !important;
        }
        .text-center {
            text-align: center !important;
        }
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #9ca3af;
            font-style: italic;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
            padding-top: 10px;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="header-left">
                <h1 class="brand">SITOKO<span style="color: #4f46e5;">2</span></h1>
                <h2 class="report-title">
                    Laporan {{ ucfirst($type) }} (Group By: {{ ucfirst($groupBy) }})
                </h2>
            </td>
            <td class="header-right">
                <p class="meta-text">
                    <strong>Periode:</strong> {{ $startDate->format('d M Y') }} - {{ $endDate->format('d M Y') }}
                </p>
                <p class="meta-text">
                    <strong>Dicetak pada:</strong> {{ now()->format('d M Y H:i:s') }}
                </p>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                @foreach ($headers as $index => $header)
                    <th class="{{ $index > 0 && preg_match('/(Jumlah|Total|Revenue|Qty|%|Rp)/i', $header) ? 'text-right' : '' }}">
                        {{ $header }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $index => $cell)
                        <td class="{{ $index > 0 && (is_numeric(str_replace(['.', ',', '%', 'Rp '], '', $cell)) || str_contains(strtolower($headers[$index] ?? ''), 'revenue')) ? 'text-right' : '' }}">
                            {{ is_numeric($cell) && strpos($cell, '.') === false ? number_format($cell, 0, ',', '.') : $cell }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="empty-state">
                        Tidak ada data yang ditemukan untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Sistem SITOKO2. Validasi internal.
    </div>

</body>
</html>
