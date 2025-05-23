@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">

                <!-- Export Button -->

                <!-- Filter -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <form method="GET" action="{{ route('home') }}" class="d-flex align-items-center gap-2 mb-0">
                        <label for="range" class="col-form-label mb-0">Tampilkan:</label>
                        <select name="range" id="range" class="form-select" onchange="this.form.submit()">
                            <option value="daily" {{ request('range') == 'daily' ? 'selected' : '' }}>Per Hari</option>
                            <option value="weekly" {{ request('range') == 'weekly' ? 'selected' : '' }}>Per Minggu</option>
                            <option value="monthly" {{ request('range') == 'monthly' ? 'selected' : '' }}>Per Bulan</option>
                            <option value="yearly" {{ request('range') == 'yearly' ? 'selected' : '' }}>Per Tahun</option>
                        </select>
                    </form>

                    @if(Auth::check())
                        <a href="{{ route('home.export.penjualan') }}" class="btn btn-success">
                            Export Penjualan (Excel)
                        </a>
                    @endif
                </div>



                <!-- Chart Total -->
                <div class="card mb-4">
                    <div class="card-header">Grafik Penjualan & Pengeluaran (Total)</div>
                    <div class="card-body">
                        <canvas id="salesChart" height="100"></canvas>
                    </div>
                </div>
                <!-- Pie Chart Per Toko -->
                @if(Auth::user()->roles == 'superadmin')
                    <div class="row">
                        @foreach($storeSales as $store => $salesValue)
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-header">Distribusi {{ $store }}</div>
                                    <div class="card-body">
                                        <canvas id="pieChart_{{ Str::slug($store, '_') }}"></canvas>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Bar Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: @json($months),
                datasets: [
                    {
                        label: 'Penjualan',
                        data: @json($sales),
                        backgroundColor: 'rgba(54, 162, 235, 0.7)'
                    },
                    {
                        label: 'Pengeluaran',
                        data: @json($expenses),
                        backgroundColor: 'rgba(255, 99, 132, 0.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'Rp ' + value.toLocaleString('id-ID')
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: context =>
                                context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID')
                        }
                    }
                }
            }
        });

        // Pie Chart per store
        @if(Auth::user()->roles == 'superadmin')
            @foreach($storeSales as $store => $sale)
                const ctxPie_{{ Str::slug($store, '_') }} = document.getElementById('pieChart_{{ Str::slug($store, '_') }}').getContext('2d');
                new Chart(ctxPie_{{ Str::slug($store, '_') }}, {
                    type: 'pie',
                    data: {
                        labels: ['Penjualan', 'Pengeluaran'],
                        datasets: [{
                            data: [{{ $sale }}, {{ $storeExpenses[$store] ?? 0 }}],
                            backgroundColor: ['#36A2EB', '#FF6384']
                        }]
                    },
                    options: {
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: context =>
                                        context.label + ': Rp ' + context.parsed.toLocaleString('id-ID')
                                }
                            }
                        }
                    }
                });
            @endforeach
        @endif
    </script>
@endsection