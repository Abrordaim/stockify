<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok Barang - Stockify</title>
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
        .status-low { color: #dc2626; font-weight: bold; }
        .status-safe { color: #059669; font-weight: bold; }
        .total-row {
            background-color: #f9fafb;
            font-weight: bold;
        }
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
        <p>LAPORAN POSISI STOK BARANG & VALUASI ASET GUDANG</p>
    </div>

    <div class="meta-info">
        <div>
            <strong>Dicetak Oleh:</strong> {{ $generatedBy }}<br>
            <strong>Tanggal Laporan:</strong> {{ $generatedAt }}
        </div>
        <div style="text-align: right;">
            <strong>Total Produk:</strong> {{ $products->count() }} Item<br>
            <strong>Total Stok Fisik:</strong> {{ $products->sum('current_stock') }} Unit
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 30px;" class="text-center">No</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Supplier</th>
                <th class="text-right">Harga Beli</th>
                <th class="text-center">Min</th>
                <th class="text-center">Stok</th>
                <th class="text-right">Total Nilai Aset</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAssetValue = 0; @endphp
            @foreach($products as $index => $p)
            @php
                $assetVal = $p->purchase_price * $p->current_stock;
                $totalAssetValue += $assetVal;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="font-mono">{{ $p->sku }}</td>
                <td style="font-weight: 600;">{{ $p->name }}</td>
                <td>{{ $p->category->name ?? '-' }}</td>
                <td>{{ $p->supplier->name ?? '-' }}</td>
                <td class="text-right font-mono">Rp {{ number_format($p->purchase_price, 0, ',', '.') }}</td>
                <td class="text-center font-mono">{{ $p->minimum_stock }}</td>
                <td class="text-center font-mono" style="font-weight: bold;">{{ $p->current_stock }}</td>
                <td class="text-right font-mono">Rp {{ number_format($assetVal, 0, ',', '.') }}</td>
                <td class="text-center">
                    @if($p->is_low_stock)
                        <span class="status-low">MENIPIS</span>
                    @else
                        <span class="status-safe">AMAN</span>
                    @endif
                </td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="7" class="text-right">TOTAL KESELURUHAN ASET:</td>
                <td class="text-center font-mono">{{ $products->sum('current_stock') }} Unit</td>
                <td class="text-right font-mono">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <div>
            Catatan: Laporan ini dihasilkan secara otomatis oleh sistem Stockify.
        </div>
        <div class="signature-box">
            <span>Penanggung Jawab Gudang,</span>
            <div class="signature-line"></div>
            <span>{{ $generatedBy }}</span>
        </div>
    </div>
</body>
</html>
