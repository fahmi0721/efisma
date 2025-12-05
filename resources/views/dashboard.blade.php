{{-- resources/views/dashboard/keuangan.blade.php --}}
@extends('layouts.app')

@section('title','Dashboard Keuangan')

@section('css')

@endsection

@section('content')
<div class="container-fluid">
    <div class="mb-3 d-flex align-items-center mt-4">
    <h5 class="mb-0">Dashboard Keuangan</h5>

    <div class="ms-auto d-flex gap-2">
        <input type="text" id="periode" class="form-control  w-auto" readonly />
        <select id="filter_entitas" class="form-select  w-auto">
            <option value="">Semua Entitas</option>
        </select>

        <select id="filter_cabang" class="form-select  w-auto">
            <option value="">Semua Cabang</option>
        </select>
        <!-- <button id="btnExport" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Export
        </button> -->
    </div>
</div>

{{-- Ringkasan KPI --}}
<div class="row g-2 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="info-box">
            <span class="info-box-icon text-bg-primary shadow-sm">
            <i class="bi bi-building"></i>
            </span>
            <div class="info-box-content">
            <span class="info-box-text">Total Aset</span>
            <span class="info-box-number" id="kpi_aset">-</span>
            </div>
            <!-- /.info-box-content -->
        </div>
        
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="info-box">
            <span class="info-box-icon text-bg-danger shadow-sm">
            <i class="bi bi-bank"></i>
            </span>
            <div class="info-box-content">
            <span class="info-box-text">Total Liabilitas</span>
            <span class="info-box-number" id="kpi_liabilitas">-</span>
            </div>
            <!-- /.info-box-content -->
        </div>
       
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="info-box">
            <span class="info-box-icon text-bg-success shadow-sm">
            <i class="bi bi-cash-stack"></i>
            </span>
            <div class="info-box-content">
            <span class="info-box-text">Kas</span>
            <span class="info-box-number" id="kpi_kas">-</span>
            </div>
            <!-- /.info-box-content -->
        </div>
    </div>

    <div class="col-lg-3 col-md-6">
        <div class="info-box">
            <span class="info-box-icon text-bg-warning shadow-sm">
            <i class="bi bi-receipt"></i>
            </span>
            <div class="info-box-content">
            <span class="info-box-text">Piutang</span>
            <span class="info-box-number" id="kpi_piutang">-</span>
            </div>
            <!-- /.info-box-content -->
        </div>
        
    </div>
</div>
{{-- Charts --}}
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <h6>Pendapatan & Beban (Bulan Berjalan)</h6>
                <canvas id="chartPendapatanBeban" height="120"></canvas>
            </div>
        </div>
         <div class="card mb-3">
            <div class="card-body">
                <h6>Laba/Rugi (Bulan Berjalan)</h6>
                <canvas id="chartLabaRugi" height="120"></canvas>
            </div>
        </div>
        
        <div class="mb-4 p-3 border rounded bg-white">
            <h6>Arus Kas Bulanan (per hari bulan berjalan)</h6>
            <canvas id="chartCashflow" height="120"></canvas>
        </div>

        <div class="p-3 border rounded bg-white">
            <h6>Top Aging Piutang</h6>
            <div id="agingList"></div>
        </div>

    </div>

    <div class="col-lg-4">
        
        <div class="mb-4 p-3 border rounded bg-white">
            <h6>Komposisi Aset</h6>
            <canvas id="chartAssets" height="180"></canvas>
        </div>

        <div class="p-3 border rounded bg-white">
            <h6>Komposisi Liabilitas</h6>
            <canvas id="chartLiabs" height="180"></canvas>
        </div>

    </div>
</div>

</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // === GLOBAL PLUGIN: Tampilkan teks "Tidak ada data" jika dataset kosong ===
    const noDataPlugin = {
        id: 'noData',
        afterDraw(chart, args, options) {
            const datasets = chart.data.datasets;

            // Jika tidak ada dataset
            if (!datasets || datasets.length === 0) return;

            // Cek jika semua dataset kosong
            let allEmpty = true;
            datasets.forEach(ds => {
                if (ds.data && ds.data.some(v => Number(v) !== 0)) {
                    allEmpty = false;
                }
            });

            if (allEmpty) {
                const { ctx, chartArea: { width, height, left, top } } = chart;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#999';
                ctx.font = 'bold 14px Arial';
                ctx.fillText('Tidak ada data', left + width / 2, top + height / 2);
                ctx.restore();
            }
        }
    };
    Chart.register(noDataPlugin);
    // Inisialisasi periode (bulan berjalan)
    let pendapatanBebanChart = null;
    let labaRugiChart = null;
    const periodeInput = document.getElementById('periode');
    const now = new Date();
    const y = now.getFullYear();
    const m = (now.getMonth()+1).toString().padStart(2,'0');
    const Defperiode = `${y}-${m}`;
    const periode = document.getElementById('periode').value;
     // 🔧 Inisialisasi Flatpickr Month Picker
    flatpickr("#periode", {
        altInput: true,
        altFormat: "F Y",   // tampil misalnya: Oktober 2025
        dateFormat: "Y-m",  // dikirim ke backend: 2025-10
        defaultDate: Defperiode,
        plugins: [
            new monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "F Y"
            })
        ],
        static: true, 
        allowInput: false,
        locale: "id"
    });
    // Select2 entitas dengan "Semua Entitas"
    $('#filter_entitas').select2({
        ajax: {
            url: "{{ route('entitas.select') }}",
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text:  q.nama};
                    })
                };
            },
        },
        width: '200px',
        theme: 'bootstrap4',
        minimumResultsForSearch: 10,
        // placeholder: 'Pilih Entitas',
        // allowClear: true
    });

    $('#filter_cabang').select2({
        ajax: {
            url: "{{ route('cabang.select') }}",
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text:  q.nama};
                    })
                };
            },
        },
        width: '200px',
        theme: 'bootstrap4',
        minimumResultsForSearch: 10,
        // placeholder: 'Pilih Entitas',
        // allowClear: true
    });

    function showLoading() {
        const loader = '<i class="fa-solid fa-spinner fa-spin"></i>';

        // KPI loading
        document.getElementById('kpi_aset').innerHTML = loader;
        document.getElementById('kpi_liabilitas').innerHTML = loader;
        document.getElementById('kpi_kas').innerHTML = loader;
        document.getElementById('kpi_piutang').innerHTML = loader;

        // Aging list
        document.getElementById('agingList').innerHTML = loader;

        // Reduce opacity chart canvas
        document.getElementById('chartCashflow').style.opacity = 0.3;
        document.getElementById('chartAssets').style.opacity = 0.3;
        document.getElementById('chartLiabs').style.opacity = 0.3;
        document.getElementById('chartPendapatanBeban').style.opacity = 0.3;
        document.getElementById('chartLabaRugi').style.opacity = 0.3;
    }

    function hideLoading() {
        document.getElementById('chartCashflow').style.opacity = 1;
        document.getElementById('chartAssets').style.opacity = 1;
        document.getElementById('chartLiabs').style.opacity = 1;
        document.getElementById('chartPendapatanBeban').style.opacity = 1;
        document.getElementById('chartLabaRugi').style.opacity = 1;

    }

    // Chart instances
    let cashflowChart = null, assetsChart = null, liabsChart = null;

    function currency(n){ return new Intl.NumberFormat('id-ID').format(Math.round(n)); }
    async function loadPendapatanBeban() {
        const entitas = $('#filter_entitas').val() || '';
        const cabang_id = $('#filter_cabang').val() || '';
        const periode = document.getElementById('periode').value;

        const res = await fetch(
            "{{ route('dashboard.keuangan.pendapatan_beban') }}"
            + "?cabang_id=" + cabang_id
            + "&periode=" + periode
            + "&entitas_id=" + entitas
        );

        const json = await res.json();
        const ctx = document.getElementById('chartPendapatanBeban').getContext('2d');

        if (pendapatanBebanChart) pendapatanBebanChart.destroy();

        pendapatanBebanChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: json.labels,
                datasets: [
                    {
                        label: 'Pendapatan',
                        data: json.pendapatan,
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22,163,74,0.2)',
                        tension: 0.3
                    },
                    {
                        label: 'Beban',
                        data: json.beban,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220,38,38,0.2)',
                        tension: 0.3
                    }
                ]
            },
            options: { responsive: true }
        });
    }

    async function loadLabaRugiHarian() {
        const entitas = $('#filter_entitas').val() || '';
        const cabang_id = $('#filter_cabang').val() || '';
        const periode = document.getElementById('periode').value;

        const res = await fetch(
            "{{ route('dashboard.keuangan.labarugi_harian') }}"
            + "?cabang_id=" + cabang_id
            + "&entitas_id=" + entitas
            + "&periode=" + periode
        );

        const json = await res.json();
        const ctx = document.getElementById('chartLabaRugi').getContext('2d');

        if (labaRugiChart) labaRugiChart.destroy();

        labaRugiChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: json.labels,
                datasets: [
                    {
                        label: 'Laba / Rugi',
                        data: json.laba,
                        backgroundColor: '#3b82f6'
                    }
                ]
            },
            options: { responsive: true }
        });
    }

    async function loadSummary() {
        const entitas = $('#filter_entitas').val() || '';
        const cabang_id = $('#filter_cabang').val() || '';
        const periode = document.getElementById('periode').value;
        const res = await fetch(
            "{{ route('dashboard.keuangan.summary') }}" 
            + "?cabang_id=" + cabang_id 
            + "&entitas_id=" + entitas 
            + "&periode=" + periode
        );

        const json = await res.json();
        document.getElementById('kpi_aset').innerText = currency(json.aset);
        document.getElementById('kpi_liabilitas').innerText = currency(json.liabilitas);
        document.getElementById('kpi_kas').innerText = currency(json.kas);
        document.getElementById('kpi_piutang').innerText = currency(json.piutang);
    }

    async function loadCashflow() {
        const entitas = $('#filter_entitas').val() || '';
        const cabang_id = $('#filter_cabang').val() || '';
        const periode = document.getElementById('periode').value; // ⬅ nilai terbaru
        const res = await fetch(
            "{{ route('dashboard.keuangan.cashflow') }}"
            +"?cabang_id=" + cabang_id 
            +"&entitas_id=" + entitas 
            + "&periode=" + periode);

        const json = await res.json();

        const ctx = document.getElementById('chartCashflow').getContext('2d');
        if (cashflowChart) cashflowChart.destroy();

        cashflowChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: json.labels,
                datasets: [{
                    label: 'Net Cash (debit - kredit)',
                    data: json.values,
                    fill: true,
                    tension: 0.25
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { ticks: { callback: v => currency(v) } } }
            }
        });
        }

    async function loadComposition() {
        const entitas = $('#filter_entitas').val() || '';
        const cabang_id = $('#filter_cabang').val() || '';
        const periode = document.getElementById('periode').value;

        const res = await fetch(
            "{{ route('dashboard.keuangan.composition') }}" 
            + "?cabang_id=" + cabang_id 
            + "&entitas_id=" + entitas 
            + "&periode=" + periode
        );
        const json = await res.json();

        // Assets
        const asetLabels = json.assets.map(a => a.nama);
        const asetValues = json.assets.map(a => Number(a.val));
        const ctxA = document.getElementById('chartAssets').getContext('2d');
        if (assetsChart) assetsChart.destroy();
        assetsChart = new Chart(ctxA, {
            type: 'doughnut',
            data: { labels: asetLabels, datasets: [{ data: asetValues }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });

        // Liabs
        const liLabels = json.liabs.map(a => a.nama);
        const liValues = json.liabs.map(a => Number(a.val));
        const ctxL = document.getElementById('chartLiabs').getContext('2d');
        if (liabsChart) liabsChart.destroy();
        liabsChart = new Chart(ctxL, {
            type: 'doughnut',
            data: { labels: liLabels, datasets: [{ data: liValues }] },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    async function loadAgingTop() {
        const entitas = $('#filter_entitas').val() || '';
        const cabang_id = $('#filter_cabang').val() || '';
        const res = await fetch(
            "{{ route('dashboard.keuangan.aging_top') }}"
            +"?cabang_id=" + cabang_id 
            +"&entitas_id=" + entitas 
            + "&limit=10");
        const json = await res.json();
        const container = document.getElementById('agingList');
        container.innerHTML = '';
        if (json.data.length === 0) {
            container.innerHTML = '<div class="text-muted">Tidak ada piutang</div>';
            return;
        }
        let html = '<div class="list-group">';
        json.data.forEach(row => {
            html += `<div class="list-group-item d-flex justify-content-between align-items-start">
                <div>
                    <div class="fw-bold">${row.partner_nama}</div>
                    <small class="text-muted">0-14: ${new Intl.NumberFormat('id-ID').format(row.aging_0_14)} • 15-30: ${new Intl.NumberFormat('id-ID').format(row.aging_15_30)}</small>
                </div>
                <div class="text-end">
                    <div class="fw-bold">Rp ${new Intl.NumberFormat('id-ID').format(row.total_piutang)}</div>
                    <small class="text-muted">>60: ${new Intl.NumberFormat('id-ID').format(row.aging_60_plus)}</small>
                </div>
            </div>`;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    // initial load
    async function reloadAll() {
        showLoading();

        await Promise.all([
            loadSummary(),
            loadCashflow(),
            loadComposition(),
            loadAgingTop(),
            loadPendapatanBeban(),
            loadLabaRugiHarian()
        ]);

        hideLoading();
    }
    reloadAll();

    // reload on filter change
    $('#filter_entitas,#periode,#filter_cabang').on('change', function() {
        reloadAll();
    });

    
});
</script>
@endsection
