@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">

                <!-- Filter Form -->
             <form method="GET" action="{{ route('home') }}" class="d-flex gap-2 mb-4 align-items-center">
    @if(Auth::user()->roles == 'superadmin')
        <select name="store" class="form-select" onchange="this.form.submit()">
            <option value="">-- Semua Toko --</option>
            @foreach($stores as $store)
                <option value="{{ $store->id }}" {{ $selectedStoreId == $store->id ? 'selected' : '' }}>
                    {{ $store->store_name }}
                </option>
            @endforeach
        </select>
    @endif

  
    {{-- Specific date range --}}
    <input type="date" name="date_from" class="form-control"
           value="{{ request('date_from') }}" onchange="this.form.submit()">
    <span class="mx-1">s/d</span>
    <input type="date" name="date_to" class="form-control"
           value="{{ request('date_to') }}" onchange="this.form.submit()">
</form>

                <!-- Summary Cards -->
                <div class="d-flex justify-content-around mb-4">
                    <div class="card text-center p-3" style="width: 30%;">
                        <h5>Omset</h5>
                        <p class="fs-4">Rp {{ number_format($totalOmset, 0, ',', '.') }}</p>
                    </div>
                    <div class="card text-center p-3" style="width: 30%;">
                        <h5>HPP</h5>
                        <p class="fs-4">Rp {{ number_format($totalHpp, 0, ',', '.') }}</p>
                    </div>
                    <div class="card text-center p-3" style="width: 30%;">
                        <h5>Laba</h5>
                        <p class="fs-4">Rp {{ number_format($totalLaba, 0, ',', '.') }}</p>
                    </div>
                </div>

                <!-- Profit Chart -->
                <div class="card mb-4">
                    <div class="card-header">Grafik Omset, HPP, & Laba</div>
                    <div class="card-body">
                        <canvas id="profitChart" height="100"></canvas>
                    </div>
                </div>
                <!-- Top 5 Produk Paling Laku -->
                <div class="card mb-4">
                    <div class="card-header">Top 5 Produk Paling Laku</div>
                    <div class="card-body">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Jumlah Terjual</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $product)
                                    <tr>
                                        <td>{{ $product->product_name }}</td>
                                        <td>{{ $product->total_sold }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">Tidak ada data penjualan</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('profitChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($labels),
                datasets: [
                    {
                        label: 'Omset',
                        data: @json($omsetData),
                        backgroundColor: 'rgba(54, 162, 235, 0.7)'
                    },
                    {
                        label: 'HPP',
                        data: @json($hppData),
                        backgroundColor: 'rgba(255, 99, 132, 0.7)'
                    },
                    {
                        label: 'Laba',
                        data: @json($labaData),
                        backgroundColor: 'rgba(75, 192, 192, 0.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: val => 'Rp ' + val.toLocaleString('id-ID')
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: ctx => `${ctx.dataset.label}: Rp ${ctx.parsed.y.toLocaleString('id-ID')}`
                        }
                    }
                }
            }
        });
    </script>
@endsection