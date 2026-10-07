@extends('layouts.app')
@section('title', 'Laporan Laba Rugi')
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
                <input type="text" id="periode_from" class="form-control form-control flatpickr-input" placeholder="Periode Awal" style="width: 150px;" />
                <input type="text" id="periode_to" class="form-control form-control flatpickr-input" placeholder="Periode Awal" style="width: 150px;" />
                @if(auth()->user()->level != "entitas")
                {{-- 🔽 Filter Entitas --}}
                <select id="filter_entitas" class="form-select form-select-sm entitas" style="width:200px">
                    <option value="">Semua Entitas</option>
                </select>
                @endif
                <select id="filter_cabang" class="form-select form-select-sm cabang" style="width:200px">
                    <option value="">Semua Cabang</option>
                </select>
                @canAccess('pbl.export')
                {{-- 📤 Tombol Export Excel --}}
                <button id="btnExportExcel" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
                @endcanAccess
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
    console.log('JS Laporan Laba Rugi mulai');
    @if(auth()->user()->level != "entitas")
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
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('.card-header'),
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true
    });
    @endif
      // 🔹 Flatpickr Month Picker dengan default bulan ini
    const now = new Date();
    let periodeFrom = null;
    let periodeTo = null;
     // 🔧 Inisialisasi Flatpickr Month Picker
    periodeFrom = flatpickr("#periode_from", {
        altInput: true,
        altFormat: "F Y",
        dateFormat: "Y-m",

        plugins: [
            new monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "F Y"
            })
        ],

        static: true,
        allowInput: false,
        locale: "id",

        onChange: function(selectedDates) {

            if (selectedDates.length > 0) {

                // To tidak boleh sebelum From
                periodeTo.set("minDate", selectedDates[0]);

                // Jika To saat ini lebih kecil dari From
                if (
                    periodeTo.selectedDates.length > 0 &&
                    periodeTo.selectedDates[0] < selectedDates[0]
                ) {
                    periodeTo.clear();
                }
            }
        }
    });


    periodeTo = flatpickr("#periode_to", {
        altInput: true,
        altFormat: "F Y",
        dateFormat: "Y-m",

        plugins: [
            new monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "F Y"
            })
        ],

        static: true,
        allowInput: false,
        locale: "id",

        onChange: function(selectedDates) {

            if (selectedDates.length > 0) {
                // From tidak boleh setelah To
                periodeFrom.set("maxDate", selectedDates[0]);
                // Jika From saat ini lebih besar dari To
                if (
                    periodeFrom.selectedDates.length > 0 &&
                    periodeFrom.selectedDates[0] > selectedDates[0]
                ) {
                    periodeFrom.clear();
                }
            }
        }
    });

     // 🔧 Inisialisasi Flatpickr Month Picker
    

    $('#filter_cabang').select2({
        ajax: {
            url: '{{ route("cabang.select") }}',
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
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
        dropdownParent: $('.card-header'),
        // placeholder: "-- Pilih Entitas --",
        // allowClear: true
    });

    @canAccess('pbl.view')
    console.log('Mulai init DataTable');
    const table = $('#tb_data').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: "{{ route('laporan.laba_rugi.data') }}",
            data: function(d) {
                d.entitas_id = $('#filter_entitas').val();
                d.periode_from = $('#periode_from').val();
                d.periode_to = $('#periode_to').val();
                d.cabang_id = $('#filter_cabang').val();
                console.log('FILTER:', d);
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
     console.log('DataTable berhasil init');
    // Reload saat filter berubah
    $('#filter_entitas, #periode_from,#periode_to, #filter_cabang').on('change', function() {
        console.log(
                'Filter berubah:',
                this.id,
                $(this).val()
            );
        table.ajax.reload();
    });
    @endcanAccess
    @canAccess('pbl.export')
    // Export Excel
    $('#btnExportExcel').click(function() {
        let entitas = $('#filter_entitas').val();
        let periode_from = $('#periode_from').val();
        let periode_to = $('#periode_to').val();
        let cabang_id = $('#filter_cabang').val();
        @if(auth()->user()->level != "entitas")
            window.location.href = "{{ route('laporan.laba_rugi.export') }}?entitas_id=" + entitas + "&periode_from=" + periode_from +"&periode_to=" + periode_to + "&cabang_id=" + cabang_id;
        @else
            window.location.href = "{{ route('laporan.laba_rugi.export') }}?periode=" + "&periode_from=" + periode_from +"&periode_to=" + periode_to +  "&cabang_id=" + cabang_id;
        @endif
    });
    @endcanAccess
});
</script>
@endsection
