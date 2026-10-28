@extends('layouts.app')
@section('title', 'Kaetun Akun')
@section('css')
<style>

</style>
@endsection

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h4 class="mb-2">Kartu Akun</h4></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Kartu Akun</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container">
    <!-- FILTER -->
    <div class="row g-2 mb-4">
        @if(auth()->user()->level != "entitas")
        <div class="col-md-2">
            <select id="filter_entitas" name="entitas" class="form-select">
                <option value="">Semua Entitas</option>
            </select>
        </div>
        @endif
        <div class="col-md-2">
            <select id="filter_akun" name="akun_gl_id" class="form-select">
                <option value="">..:: Pillih Akun GL</option>
            </select>
        </div>
        <div class="col-md-2">
            <select id="filter_partner" class="form-select form-select-sm partner">
                <option value="">Semua Partner</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="text" id="tanggal_awal" class="form-control form-control flatpickr-input" placeholder="Tanggal Awal" style="width: 200px;" />
        </div>
        <div class="col-md-2">
            <input type="text" id="tanggal_akhir" class="form-control form-control flatpickr-input" placeholder="Tanggal Akhir" style="width: 200px;" />
        </div>

        <div class="col-md-2">
            <div class='btn-group'>
            @canAccess('kartuakun.view')
            <button id='btn-filter' class="btn btn-primary">Tampilkan</button>
            @endcanAccess
            @canAccess('kartuakun.export')
            <button id='btnExportExcel' data-toggle='tooltip' title='Export Excel' class="btn btn-success">
                <i class="fas fa-file-excel"></i> 
            </button>
            @endcanAccess
            </div>
        </div>
    </div>


    <!-- TABLE RESULT -->
    <!-- LOADER -->
    <div class="text-center my-3 d-none" id="loader">
        <div class="spinner-border text-primary"></div>
        <div>Loading data...</div>
    </div>

    <!-- TABEL -->
    <div class="card">
    <div class="card-body">
        <div class='table-striped'>
        <table id="t_data" class="table table-bordered table-striped w-100">
            <thead class="bg-light">
                <tr>
                    <th>No</th>
                    <th>Kode Jurnal</th>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Debet</th>
                    <th>Kredit</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    </div>
</div>

</div>
@endsection

@section('js')
<script>

let table;
let isLoaded = false;
const now = new Date();
    let periodeFrom = null;
    let periodeTo = null;
    const defaultDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
     // 🔧 Inisialisasi Flatpickr Month Picker
    periodeFrom = flatpickr("#tanggal_awal", {
        altInput: true,
        altFormat: "d F Y",      // Tampilan: 13 Oktober 2026
        dateFormat: "Y-m-d", 
        defaultDate: defaultDate,
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


    periodeTo = flatpickr("#tanggal_akhir", {
        altInput: true,
        altFormat: "d F Y",      // Tampilan: 13 Oktober 2026
        dateFormat: "Y-m-d",
        defaultDate: defaultDate,
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
@canAccess('kartuakun.view')
 table =    $('#t_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "{{ route('laporan.kartuakun') }}",
            data: function (d) {
                d.entitas_id = $('#filter_entitas').val();
                d.akun_gl_id = $('#filter_akun').val();
                d.partner_id = $('#filter_partner').val();
                d.periode_from = $('#tanggal_awal').val();
                d.periode_to = $('#tanggal_akhir').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'kode_jurnal', name: 'kode_jurnal',orderable:false, },
            { data: 'tanggal', name: 'tanggal',orderable:false, },
            { data: 'keterangan', name: 'keterangan',orderable:false, },
            { 
                data: 'debit', 
                name: 'debit',
                orderable:false,
                className: 'text-end',
                render: function(data) {
                    if (!data) return '-';
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR',minimumFractionDigits: 0, }).format(data);
                },orderable:false, 
            },
            { 
                data: 'kredit', 
                name: 'kredit',
                orderable:false,
                className: 'text-end',
                render: function(data) {
                    if (!data) return '-';
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR',minimumFractionDigits: 0, }).format(data);
                },orderable:false, 
            },
            { 
                data: 'saldo', 
                name: 'saldo',
                orderable:false,
                className: 'text-end',
                render: function(data) {
                    if (!data) return '-';
                    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR',minimumFractionDigits: 0, }).format(data);
                },orderable:false, 
            },
        ],
        // order: [[2, 'desc']],
    });
@endcanAccess
$(function() {
    $("[data-toggle='tooltip']").tooltip();
       // 🔹 Flatpickr Month Picker dengan default bulan ini
    
    $('.partner').select2({
        ajax: {
            url: '{{ route("partner.select") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    jenis:'all',
                    @if(auth()->user()->level != "entitas")
                    entitas_id: $('#entitas_id').val()
                    @endif
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text:q.nama};
                    })
                };
            },
            cache: true
        },
        allowClear: true,
        theme: 'bootstrap4',
        placeholder: "-- Pilih Partner --",
        width: 'resolve',
        minimumResultsForSearch: 0, // sembunyikan search box kalau sedikit opsi
       
    });
    // 🔽 Select2 Akun GL
    $('#filter_akun').select2({
        ajax: {
            url: '{{ route("saldo_awal.akun_gl") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text: q.nama};
                    })
                };
            },
            cache: true
        },
        theme: 'bootstrap4',
        width: '100%',
        placeholder: "-- Pilih Akun GL --",
        allowClear: true
    });
    @if(auth()->user()->level != "entitas")
    $('#filter_entitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
            dataType: 'json',
            delay: 250,
            processResults: data => ({
                results: data.map(q => ({ id: q.id, text: q.nama }))
            }),
            cache: true
        },
        theme: 'bootstrap4',
        width: 'resolve',
        placeholder: "-- Pilih Entitas --",
        allowClear: true,
        minimumResultsForSearch: 0, // -1 = search box selalu disembunyikan
        escapeMarkup: markup => markup
    });
    @endif

});


@canAccess('kartuakun.view')
$('#filter_entitas').val('').trigger('change');

 // Reload saat filter berubah
$('#btn-filter').on('click', function() {
    if (!$('#filter_akun').val()) {
        position = "bottom-left";
        icons = "warning";
        pesan = "Akun GL tidak boleh kosong";
        title = "Warning!";
        info(title,pesan,icons,position);
        return;
    }
    isLoaded = true;
    table.ajax.reload();
});

table.on('draw', function () {
    $('#loader').addClass('d-none');
});
@endcanAccess
@canAccess('kartuakun.export')
$('#btnExportExcel').on('click', function () {
    let akun    = $('#filter_akun').val();
    if (!akun) {
        alert('Pilih akun terlebih dahulu');
        return;
    }

    const params = {
        @if(auth()->user()->level != "entitas")
        entitas_id: $('#filter_entitas').val() || '',
        @endif
        partner_id: $('#filter_partner').val() || '',
        akun_gl_id: $('#filter_akun').val() || '',
        periode_from: $('#tanggal_awal').val() || '',
        periode_to: $('#tanggal_akhir').val() || '',
    };
    const query = new URLSearchParams(params).toString();
    window.location.href = "{{ route('laporan.kartuakun.export') }}?" + query;
});
@endcanAccess
function rupiah(x) {
    return 'Rp ' + Number(x).toLocaleString('id-ID');
}





</script>
@endsection
