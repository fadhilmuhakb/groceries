@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
        <div class="mb-2">
            <h4 class="mb-1">Konfirmasi Stock Opname</h4>
            <div class="text-muted small">
                @if(!empty($summary['store_name']))
                    Toko: {{ $summary['store_name'] }}
                @endif
                @if(!empty($summary['submitted_at']))
                    <span class="mx-2">|</span> Waktu: {{ $summary['submitted_at'] }}
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-secondary"
               href="{{ route('inventory.index', array_filter(['store_id' => $summary['store_id'] ?? null, 'back' => 1])) }}">
                Kembali
            </a>
            <form method="POST" action="{{ route('inventory.adjustStockBulkV3') }}" data-csrf-refresh="1">
                @csrf
                <input type="hidden" name="use_session_items" value="1">
                <input type="hidden" name="preview_token" value="{{ $previewToken ?? '' }}">
                <button type="submit" class="btn btn-primary">
                    Adjust
                </button>
            </form>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-warning">
        <div class="fw-semibold">Apakah Anda yakin ingin menyimpan penyesuaian stok ini?</div>
        <div class="small text-muted">Jika kembali, Anda bisa mengubah jumlah fisik.</div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Total Item Disesuaikan</div>
                    <div class="fs-5 fw-semibold">
                        {{ number_format((int)($summary['changed_items'] ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Total Produk Toko</div>
                    <div class="fs-5 fw-semibold">
                        {{ number_format((int)($summary['total_items'] ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Total Minus (Rp)</div>
                    <div class="fs-5 fw-semibold">
                        Rp {{ number_format((int)($summary['total_minus_value'] ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Total Plus (Rp)</div>
                    <div class="fs-5 fw-semibold">
                        Rp {{ number_format((int)($summary['total_plus_value'] ?? 0), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <span class="text-muted">Netto (Plus - Minus):</span>
        <span class="fw-semibold">
            Rp {{ number_format((int)($summary['net_value'] ?? 0), 0, ',', '.') }}
        </span>
    </div>

    @if(empty($changes))
        <div class="alert alert-info">
            Tidak ada item dengan selisih stok. Anda bisa langsung menyimpan atau kembali untuk revisi.
        </div>
    @else
        <div class="text-muted mb-2">
            Menampilkan item dengan selisih stok (plus/minus).
        </div>
        <div class="mb-3">
            <input type="text" id="search-preview" class="form-control" placeholder="Cari nama/kode produk...">
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="result-table">
                <thead>
                    <tr>
                        <th style="width:40px;">No.</th>
                        <th>Produk</th>
                        <th class="text-end">Stok Sistem</th>
                        <th class="text-end">Stok Fisik</th>
                        <th class="text-end">Selisih (+/-)</th>
                        <th class="text-end">Minus (unit)</th>
                        <th class="text-end">Plus (unit)</th>
                        <th class="text-end">Harga Beli</th>
                        <th class="text-end">Total Minus (Rp)</th>
                        <th class="text-end">Total Plus (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($changes as $index => $row)
                        <tr data-name="{{ strtolower($row['product_name'] ?? '') }}"
                            data-code="{{ strtolower($row['product_code'] ?? '') }}">
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $row['product_name'] ?? '-' }}</div>
                                @if(!empty($row['product_code']))
                                    <div class="text-muted small">Kode: {{ $row['product_code'] }}</div>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format((int)($row['system_stock'] ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((int)($row['physical_quantity'] ?? 0), 0, ',', '.') }}</td>
                            @php
                                $diffQty = (int)($row['physical_quantity'] ?? 0) - (int)($row['system_stock'] ?? 0);
                                $diffText = ($diffQty > 0 ? '+' : '').number_format($diffQty, 0, ',', '.');
                            @endphp
                            <td class="text-end">{{ $diffText }}</td>
                            <td class="text-end">{{ number_format((int)($row['minus_qty'] ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format((int)($row['plus_qty'] ?? 0), 0, ',', '.') }}</td>
                            <td class="text-end">
                                {{ number_format((int)($row['purchase_price'] ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="text-end">
                                Rp {{ number_format((int)($row['minus_value'] ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="text-end">
                                Rp {{ number_format((int)($row['plus_value'] ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end">TOTAL</td>
                        @php
                            $totalDiffQty = (int)($summary['total_plus_qty'] ?? 0) - (int)($summary['total_minus_qty'] ?? 0);
                            $totalDiffText = ($totalDiffQty > 0 ? '+' : '').number_format($totalDiffQty, 0, ',', '.');
                        @endphp
                        <td class="text-end">{{ $totalDiffText }}</td>
                        <td class="text-end">
                            {{ number_format((int)($summary['total_minus_qty'] ?? 0), 0, ',', '.') }}
                        </td>
                        <td class="text-end">
                            {{ number_format((int)($summary['total_plus_qty'] ?? 0), 0, ',', '.') }}
                        </td>
                        <td></td>
                        <td class="text-end">
                            Rp {{ number_format((int)($summary['total_minus_value'] ?? 0), 0, ',', '.') }}
                        </td>
                        <td class="text-end">
                            Rp {{ number_format((int)($summary['total_plus_value'] ?? 0), 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="button" class="btn btn-success mt-2" id="downloadExcelBtn">
            Download Excel (Selisih)
        </button>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const csrfRefreshUrl = "{{ route('inventory.refreshCsrf') }}";
    let csrfRefreshPromise = null;
    const setCsrfToken = (token) => {
        if (!token) return;
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) meta.setAttribute('content', token);
        document.querySelectorAll('input[name="_token"]').forEach(input => {
            input.value = token;
        });
    };
    const refreshCsrfToken = () => {
        if (csrfRefreshPromise) return csrfRefreshPromise;
        csrfRefreshPromise = fetch(csrfRefreshUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(async res => {
            if (!res.ok) {
                throw new Error('Gagal memperbarui CSRF token.');
            }
            const data = await res.json().catch(() => ({}));
            if (!data || !data.token) {
                throw new Error('Response token tidak valid.');
            }
            setCsrfToken(data.token);
            return data.token;
        }).finally(() => {
            csrfRefreshPromise = null;
        });
        return csrfRefreshPromise;
    };

    const keepAliveIntervalMs = 5 * 60 * 1000;
    setInterval(() => {
        refreshCsrfToken().catch(() => {});
    }, keepAliveIntervalMs);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshCsrfToken().catch(() => {});
        }
    });
    document.querySelectorAll('form[data-csrf-refresh="1"]').forEach(formEl => {
        formEl.addEventListener('submit', (e) => {
            if (e.defaultPrevented || formEl.dataset.csrfRefreshing === '1') return;
            e.preventDefault();
            formEl.dataset.csrfRefreshing = '1';
            refreshCsrfToken().catch(() => {}).finally(() => {
                formEl.submit();
            });
        });
    });

    const btn = document.getElementById('downloadExcelBtn');
    const table = document.getElementById('result-table');
    const searchInput = document.getElementById('search-preview');
    if (!btn || !table) return;

    btn.addEventListener('click', () => {
        const wb = XLSX.utils.book_new();
        const ws = XLSX.utils.table_to_sheet(table);
        XLSX.utils.book_append_sheet(wb, ws, 'Selisih Stock Opname');
        XLSX.writeFile(wb, 'Stock_Opname_Selisih_' + new Date().toISOString().slice(0,10) + '.xlsx');
    });

    if (!searchInput) return;
    const rows = table.querySelectorAll('tbody tr');
    const filterRows = (term) => {
        const keyword = (term || '').trim().toLowerCase();
        rows.forEach(tr => {
            const name = tr.dataset.name || '';
            const code = tr.dataset.code || '';
            const matches = !keyword || name.includes(keyword) || code.includes(keyword);
            tr.style.display = matches ? '' : 'none';
        });
    };

    searchInput.addEventListener('input', () => filterRows(searchInput.value));
});
</script>
@endsection
