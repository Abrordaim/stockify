<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Mutasi Stok - Stockify</title>
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
        <h1>STOCKIFY INVENTORY MANAGEMENT</h1>
        <p>LAPORAN RIWAYAT MUTASI & PERGERAKAN STOK BARANG</p>
    </div>

    <div class="meta-info">
        <div>
            <strong>Periode Transaksi:</strong> {{ date('d/m/Y', strtotime($startDate)) }} s/d {{ date('d/m/Y', strtotime($endDate)) }}<br>
            <strong>Filter Tipe:</strong> {{ $type ? strtoupper($type) : 'SEMUA TIPE' }}
        </div>
        <div style="text-align: right;">
            <strong>Dicetak Oleh:</strong> {{ $generatedBy }}<br>
            <strong>Tanggal Cetak:</strong> {{ $generatedAt }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th style="width: 80px;">Tanggal</th>
                <th style="width: 100px;">SKU</th>
                <th>Nama Produk</th>
                <th class="text-center" style="width: 100px;">Tipe Mutasi</th>
                <th class="text-center" style="width: 80px;">Kuantitas</th>
                <th style="width: 100px;">Petugas</th>
                <th class="text-center" style="width: 80px;">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $tx)
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
                    {{ $tx->type === 'in' ? '+' : ($tx->type === 'out' ? '-' : '') }}{{ $tx->quantity }} Unit
                </td>
                <td>{{ $tx->user->name ?? '-' }}</td>
                <td class="text-center">
                    <span style="font-size: 10px; font-weight: bold; text-transform: uppercase;">{{ $tx->status }}</span>
                </td>
                <td style="color: #4b5563;">{{ $tx->notes ?: '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center" style="padding: 24px; color: #9ca3af;">
                    Tidak ada data transaksi mutasi pada periode ini.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        <div>
            Catatan: Laporan audit mutasi ini dikeluarkan secara resmi oleh sistem Stockify.
        </div>
        <div class="signature-box">
            <span>Penanggung Jawab Gudang,</span>
            <div class="signature-line"></div>
            <span>{{ $generatedBy }}</span>
        </div>
    </div>
</body>
</html>
