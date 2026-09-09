<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ $settings->logo_url ?: asset('gudang.png') }}">
    <title>Laporan Mutasi Stok - {{ $settings->app_name ?? 'Stockify' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #111827;
            background: #fff;
            margin: 0;
            padding: 24px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            color: #1e40af;
        }
        .header p {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 11px;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 11px;
            color: #374151;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background-color: #f3f4f6;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 10px;
            color: #374151;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .type-in { color: #059669; font-weight: bold; }
        .type-out { color: #d97706; font-weight: bold; }
        .type-adj { color: #2563eb; font-weight: bold; }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }
        .signature-box {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            margin-top: 60px;
            border-bottom: 1px solid #111827;
        }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px; text-align: right;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">
            🖨️ Cetak / Simpan ke PDF
        </button>
    </div>

    <div class="header">
        @if(isset($settings) && ($settings->logo_base64 || $settings->logo_url))
            <img src="{{ $settings->logo_base64 ?: $settings->logo_url }}" alt="Logo" style="max-height: 52px; margin-bottom: 8px; object-contain: contain;">
        @endif
        <h1>{{ strtoupper($settings->company_name ?? 'STOCKIFY INVENTORY MANAGEMENT') }}</h1>
        <p>LAPORAN RIWAYAT MUTASI & PERGERAKAN STOK BARANG</p>
        @if(isset($settings) && $settings->company_address)
            <p style="font-size: 10px; color: #4b5563; margin-top: 2px;">
                {{ $settings->company_address }}
                @if($settings->company_phone) | Telp: {{ $settings->company_phone }} @endif
                @if($settings->company_email) | Email: {{ $settings->company_email }} @endif
            </p>
        @endif
    </div>

    <div class="meta-info">
        <div>
            <strong>Periode Transaksi:</strong> {{ date('d/m/Y', strtotime($startDate)) }} s/d {{ date('d/m/Y', strtotime($endDate)) }}<br>
            <strong>Filter Tipe:</strong> {{ $type ? strtoupper($type) : 'SEMUA TIPE' }}
            @if(isset($status) && $status) | <strong>Status:</strong> {{ strtoupper($status) }} @endif
        </div>
        <div style="text-align: right;">
            <strong>Dicetak Oleh:</strong> {{ $generatedBy }}<br>
            <strong>Tanggal Cetak:</strong> {{ $generatedAt }}
        </div>
    </div>

    @if(isset($totalIn) || isset($totalOut))
    <div style="display: flex; gap: 12px; margin-bottom: 16px;">
        <div style="flex: 1; padding: 10px 14px; border: 1px solid #d1fae5; background: #f0fdf4; border-radius: 8px;">
            <div style="font-size: 10px; font-weight: bold; color: #047857; text-transform: uppercase;">Total Masuk (Diterima)</div>
            <div style="font-size: 16px; font-weight: bold; color: #065f46; margin-top: 2px;">+{{ number_format($totalIn ?? 0, 0, ',', '.') }} Unit</div>
        </div>
        <div style="flex: 1; padding: 10px 14px; border: 1px solid #dbeafe; background: #eff6ff; border-radius: 8px;">
            <div style="font-size: 10px; font-weight: bold; color: #1d4ed8; text-transform: uppercase;">Total Keluar (Dikeluarkan)</div>
            <div style="font-size: 16px; font-weight: bold; color: #1e40af; margin-top: 2px;">-{{ number_format($totalOut ?? 0, 0, ',', '.') }} Unit</div>
        </div>
        <div style="flex: 1; padding: 10px 14px; border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 8px;">
            <div style="font-size: 10px; font-weight: bold; color: #4b5563; text-transform: uppercase;">Net Arus Barang</div>
            <div style="font-size: 16px; font-weight: bold; color: #111827; margin-top: 2px;">
                {{ (($totalIn ?? 0) - ($totalOut ?? 0)) > 0 ? '+' : '' }}{{ number_format(($totalIn ?? 0) - ($totalOut ?? 0), 0, ',', '.') }} Unit
            </div>
        </div>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 70px;">Tanggal</th>
                <th style="width: 80px;">SKU</th>
                <th>Nama Produk</th>
                <th class="text-center" style="width: 65px;">Tipe</th>
                <th class="text-center" style="width: 65px;">Jumlah</th>
                <th class="text-center" style="width: 85px;">Stok (Sblm→Ssdh)</th>
                <th style="width: 90px;">Dicatat Oleh</th>
                <th style="width: 90px;">Dikonfirmasi</th>
                <th class="text-center" style="width: 75px;">Status</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $tx)
            @php
                $statusNormalized = match ($tx->status) {
                    'completed' => ($tx->type === 'out' ? 'Dikeluarkan' : 'Diterima'),
                    'cancelled' => 'Ditolak',
                    'pending' => 'Pending',
                    default => $tx->status,
                };
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-mono">{{ $tx->date ? $tx->date->format('d/m/Y') : '-' }}</td>
                <td class="font-mono">{{ $tx->product->sku ?? '-' }}</td>
                <td style="font-weight: 600;">{{ $tx->product->name ?? 'Produk Dihapus' }}</td>
                <td class="text-center">
                    @if($tx->type === 'in')
                        <span class="type-in">MASUK</span>
                    @elseif($tx->type === 'out')
                        <span class="type-out">KELUAR</span>
                    @else
                        <span class="type-adj">OPNAME</span>
                    @endif
                </td>
                <td class="text-center font-mono" style="font-weight: bold;">
                    {{ $tx->type === 'in' ? '+' : ($tx->type === 'out' ? '-' : '') }}{{ $tx->quantity }}
                </td>
                <td class="text-center font-mono" style="font-size: 10px;">
                    {{ $tx->stock_before }} → {{ $tx->stock_after }}
                </td>
                <td>{{ $tx->createdBy->name ?? ($tx->user->name ?? '-') }}</td>
                <td>
                    @if($tx->confirmedBy)
                        {{ $tx->confirmedBy->name }}
                    @elseif($statusNormalized === 'Pending')
                        <span style="color: #d97706; font-weight: bold;">Menunggu</span>
                    @else
                        -
                    @endif
                </td>
                <td class="text-center">
                    <span style="font-size: 9px; font-weight: bold; text-transform: uppercase;">{{ $statusNormalized }}</span>
                </td>
                <td style="color: #4b5563;">{{ $tx->notes ?: '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center" style="padding: 24px; color: #9ca3af;">
                    Tidak ada data transaksi mutasi pada periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div>
            {{ $settings->footer_note ?? 'Catatan: Laporan audit mutasi ini dikeluarkan secara resmi oleh sistem Stockify.' }}
        </div>
        <div class="signature-box">
            <span>{{ $settings->signee_title ?? 'Penanggung Jawab Gudang,' }}</span>
            <div class="signature-line"></div>
            <span>{{ $settings->signee_name ?? $generatedBy }}</span>
        </div>
    </div>
</body>
</html>
