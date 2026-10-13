@extends('layouts.app')
@section('title','Daftar Uang Muka')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Daftar Uang Muka</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Daftar Uang Muka</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
@section('css')

@endsection
@section('content')
<div class="container-fluid">
    <div class="row g-2 mb-4">
        @if(auth()->user()->level != "entitas")
        <div class="col-md-2">
            <select id="filter_entitas" class="form-select form-select-sm entitas">
                <option value="">Semua Entitas</option>
            </select>
        </div>
        @endif
        <div class="col-md-2">
            <select id="filter_akun" name="akun_gl_id" class="form-select form-select-sm">
                <option value="">..:: Pillih Akun GL</option>
            </select>
        </div>
        <div class="col-md-2">
            <select id="filter_partner" class="form-select form-select-sm partner">
                <option value="">Semua Partner</option>
            </select>
        </div>
        
        <div class="col-md-2">
            <input type="text" id="periode_from" class="form-control form-control flatpickr-input" placeholder="Periode Awal" />
        </div>
        <div class="col-md-2">
            <input type="text" id="periode_to" class="form-control form-control flatpickr-input" placeholder="Periode Akhir" />
        </div>

        <div class="col-md-2">
            <div class='btn-group'>
                <button id='btn-filter' class="btn btn-primary">Tampilkan</button>
                @canAccess('uangmuka.daftar.export')
                <button id='btnExportExcel' data-toggle='tooltip' title='Export Excel' class="btn btn-success">
                    <i class="fas fa-file-excel"></i> 
                </button>
            @endcanAccess
            </div>
        </div>
    </div>
    <div class="card card-success card-outline">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tb_data" class="table table-bordered table-striped align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th>Akun GL</th>
                            <th>Entitas</th>
                            <th>Partner<br><small>(Vendor/Pegawai)</small></th>
                            <th>Saldo Awal</th>
                            <th>Total Debit</th>
                            <th>Total Kredit</th>
                            <th>Saldo Akhir</th>

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
$(document).ready(function() {
    $('#filter_akun').select2({
        ajax: {
            url: '{{ route("uangmuka.akun_gl") }}',
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

    const now = new Date();
    let periodeFrom = null;
    let periodeTo = null;
    const defaultDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
     // 🔧 Inisialisasi Flatpickr Month Picker
    periodeFrom = flatpickr("#periode_from", {
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


    periodeTo = flatpickr("#periode_to", {
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
    $('.partner').select2({
        ajax: {
            url: '{{ route("partner.select") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    jenis:'vendor',
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
    const tb = $('#tb_data').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
         ajax: {
            url: "{{ route('uangmuka.daftar') }}",
            data: function (d) {
                @if(auth()->user()->level != "entitas")
                d.entitas_id = $('#filter_entitas').val();
                @endif
                d.partner_id = $('#filter_partner').val();
                d.akun_gl_id = $('#filter_akun').val();
                d.periode_from = $('#periode_from').val();
                d.periode_to = $('#periode_to').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable:false, searchable:false },
            { data: 'akun_gl', name: 'akun_gl' },
            { data: 'entitas', name: 'entitas' },
            { data: 'partner', name: 'partner' },
            { data: 'saldo_awal', name: 'saldo_awal' },
            { data: 'total_debit', name: 'total_debit' },
            { data: 'total_kredit', name: 'total_kredit' },
            { data: 'saldo', name: 'saldo' },
            
           
        ],
        order: [[1, 'asc']],
        language: {
            searchPlaceholder: 'Cari partner...',
            processing: '<i class="fa fa-spinner fa-spin"></i> Loading...'
        }
    });
    @if(auth()->user()->level != "entitas")
    // 🔄 Reload ketika filter berubah
    $('#filter_entitas').on('change', function() {
        $('.partner').val(null).trigger('change');
        tb.ajax.reload();
    });
    @endif
    $('#filter_partner,#filter_akun').on('change', function() {
        tb.ajax.reload();
    });
    $("#btn-filter").on("click", function(){
        tb.ajax.reload();
    })
    
    @canAccess('uangmuka.daftar.export')
    // 📤 Export Excel
    $('#btnExportExcel').click(function() {
        const params = {
            @if(auth()->user()->level != "entitas")
            entitas_id: $('#filter_entitas').val() || '',
            @endif
            partner_id: $('#filter_partner').val() || '',
            akun_gl_id: $('#filter_akun').val() || '',
            periode_from: $('#periode_from').val() || '',
            periode_to: $('#periode_to').val() || '',
        };
        const query = new URLSearchParams(params).toString();
        window.location.href = "{{ route('uangmuka.daftar.export') }}?" + query;
    });
    @endcanAccess
});
</script>
@endsection
