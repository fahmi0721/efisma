@extends('layouts.app')
@section('title', 'resources/views/page/laporan/neraca.blade.php')
@section('css')
<style>
</style>
@endsection

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Laporan Laba Rugi</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Laporan Laba Rugi</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="card card-outline card-success">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
            <h5 class="mb-0">Laporan Laba Rugi</h5>
            <div class="d-flex align-items-center gap-2 ms-auto">
                {{-- 🔽 Filter Entitas --}}
                <select id="filter_entitas" class="form-select form-select-sm entitas" style="width:250px">
                    <option value="">Semua Entitas</option>
                </select>

                <input type="text" id="periode" class="form-control form-control flatpickr-input" placeholder="Pilih Periode" style="width: 200px;" />
                {{-- 📤 Tombol Export Excel --}}
                <button id="btnExportExcel" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="tb_data" class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>No Akun</th>
                            <th>Nama Akun</th>
                            <th>Debit</th>
                            <th>Kredit</th>
                            <th>Saldo Akhir</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="4"  style='text-align:right !important'>Total Pendapatan</td>
                            <td id="totalPendapatan" class="text-end">0</td>
                        </tr>
                        <tr class="table-light fw-bold">
                            <td colspan="4" class="text-end">Total Beban</td>
                            <td id="totalBeban" class="text-end">0</td>
                        </tr>
                        <tr class="table-success fw-bold">
                            <td colspan="4" class="text-end">Laba / (Rugi) Bersih</td>
                            <td id="labaBersih" class="text-end">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(function() {
     $('#filter_entitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
            dataType: 'json',
            delay: 250,
             processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text:  q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        width: 'resolve',
        minimumResultsForSearch: Infinity, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('.card-header'),
        // placeholder: "-- Pilih Entitas --",
        allowClear: true
    });

      // 🔹 Flatpickr Month Picker dengan default bulan ini
    const now = new Date();
    const defaultDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    
     // 🔧 Inisialisasi Flatpickr Month Picker
    flatpickr("#periode", {
        altInput: true,
        altFormat: "F Y",   // tampil misalnya: Oktober 2025
        dateFormat: "Y-m",  // dikirim ke backend: 2025-10
        defaultDate: defaultDate,
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
    const table = $('#tb_data').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: "{{ route('laporan.laba_rugi.data') }}",
            data: function(d) {
                d.entitas_id = $('#filter_entitas').val();
                d.periode = $('#periode').val();
            },
            dataSrc: function (json) {
                // tampilkan total di bawah tabel
                $('#totalPendapatan').text(
                    new Intl.NumberFormat('id-ID').format(json.total_pendapatan)
                );
                $('#totalBeban').text(
                    new Intl.NumberFormat('id-ID').format(json.total_beban)
                );
                $('#labaBersih').text(
                    new Intl.NumberFormat('id-ID').format(json.laba_bersih)
                );
                return json.data;
            }
        },
        columns: [
            { data: 'no_akun', className: 'text-center' },
            { 
                data: 'akun_nama',
                render: function(data, type, row) {
                    // indentasi berdasarkan level
                    let indent = '&nbsp;'.repeat((row.level - 1) * 4);
                    return indent + data;
                }
            },
            { data: 'total_debit', className: 'text-end',
                render: d => new Intl.NumberFormat('id-ID').format(d)
            },
            { data: 'total_kredit', className: 'text-end',
                render: d => new Intl.NumberFormat('id-ID').format(d)
            },
            { data: 'saldo_akhir', className: 'text-end fw-bold',
                render: d => new Intl.NumberFormat('id-ID').format(d)
            },
        ],
        // order: [[0, 'asc']],
        ordering: false,
        paging: false,
        searching: false,
        info: false,
    });

    // Reload saat filter berubah
    $('#filter_entitas, #periode').on('change', function() {
        table.ajax.reload();
    });

    // Export Excel
    $('#btnExportExcel').click(function() {
        let entitas = $('#filter_entitas').val();
        let periode = $('#periode').val();
        window.location.href = "{{ route('laporan.laba_rugi.export') }}?entitas_id=" + entitas + "&periode=" + periode;
    });
});
</script>
@endsection
