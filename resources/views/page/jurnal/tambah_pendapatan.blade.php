@extends('layouts.app')
@section('title','Create New Jurnal Pendapatan')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Create New Jurnal Pendapatan</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('jurnal.pendapatan') }}">Jurnal Pendapatan</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create New Jurnal Pendapatan</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card card-success card-outline mb-4">
                <div class="card-header"><div class="card-title">Create New Jurnal Pendapatan</div></div>
                
                <form action="javascript:void(0)" enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("post")

                    <div class="card-body">

                        <!-- 🔄 Ganti Tahun → Periode -->
                        <div class="row mb-3">
                            <label for="tanggal" class="col-sm-3 col-form-label">Tanggal <b class="text-danger">*</b></label>
                            <div class="col-sm-9">
                                <input type="text" id="tanggal" name="tanggal" class="form-control" 
                                       placeholder="Pilih periode (YYYY-MM-DD)" readonly required>
                            </div>
                        </div>
                        @if(auth()->user()->level != "entitas")
                        <div class="row mb-3">
                            <label for="entitas_id" class="col-sm-3 col-form-label">Entitas <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="entitas_id" id="entitas_id" class="form-control entitas">
                                    <option value="">-- Pilih Entitas --</option>
                                </select>
                            </div>
                        </div>
                        @endif
                        <div class="row mb-3">
                            <label for="cabang_id" class="col-sm-3 col-form-label">Cabang <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="cabang_id" id="cabang_id" class="form-control cabang">
                                    <option value="">-- Pilih Cabang --</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="partner_id" class="col-sm-3 col-form-label">Partner <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="partner_id" id="partner_id" class="form-control partner">
                                    <option value="">-- Pilih Customer --</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="no_invoice" class="col-sm-3 col-form-label">No Invoice <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text"   class="form-control" id="no_invoice" name="no_invoice" placeholder="No Invoice" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="tanggal_invoice" class="col-sm-3 col-form-label">Tanggal Invoice <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text"   class="form-control" id="tanggal_invoice" name="tanggal_invoice" placeholder="Tanggal Invoice" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="keterangan" class="col-sm-3 col-form-label">Keterangan </label>
                            <div class="col-sm-9">
                                <input type="text"  class="form-control" id="keterangan" name="keterangan" placeholder="Keterangan" />
                            </div>
                        </div>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="tableDetail">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30%">Akun</th>
                                        <th>Deskripsi</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Kredit</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select class="form-select akun-selectok" name="detail[0][akun_id]">
                                                <option value="">-- Pilih Akun --</option>
                                                @foreach(DB::table('m_akun_gl')->orderBy('no_akun')->get() as $akun)
                                                    <option value="{{ $akun->id }}">{{ $akun->no_akun }} - {{ $akun->nama }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="text" name="detail[0][deskripsi]" class="form-control"></td>
                                        <td><input type="text"  name="detail[0][debit]" onkeyup="formatRupiah(this)" class="form-control text-end debit-input" value="0"></td>
                                        <td><input type="text"  name="detail[0][kredit]" onkeyup="formatRupiah(this)" class="form-control text-end kredit-input" value="0"></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5">
                                            <button type="button" class="btn btn-success btn-sm" id="btnTambahBaris">
                                                <i class="fas fa-plus"></i> Tambah Baris
                                            </button>
                                        </td>
                                    </tr>
                                    <tr class="table-light">
                                        <th colspan="2" class="text-end">TOTAL</th>
                                        <th class="text-end" id="totalDebit">0</th>
                                        <th class="text-end" id="totalKredit">0</th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                            

                        </div>
                    </div>

                    <div class="card-footer">
                        <a href="{{ route('jurnal.pendapatan') }}" class="btn btn-danger btn-flat btn-sm">
                            <i class="fa fa-mail-reply"></i> Kembali
                        </a>
                        <button type="submit" id="btn-submit" class="btn btn-success btn-flat btn-sm float-end">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
                <!-- Template baris tersembunyi -->
                <table style="display:none;">
                    <tbody id="rowTemplate">
                        <tr>
                            <td>
                                <select class="form-select akun-select" name="detail[__INDEX__][akun_id]">
                                    <option value="">-- Pilih Akun --</option>
                                </select>
                            </td>
                            <td><input type="text" name="detail[__INDEX__][deskripsi]" class="form-control"></td>
                            <td><input type="text" name="detail[__INDEX__][debit]" onkeyup="formatRupiah(this)" class="form-control text-end debit-input" value="0"></td>
                            <td><input type="text" name="detail[__INDEX__][kredit]" onkeyup="formatRupiah(this)" class="form-control text-end kredit-input" value="0"></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-danger btn-sm btn-hapus"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>  
        </div>
    </div>    
</div>

@endsection

@section('js')

<script>
$(document).ready(function() {

    flatpickr("#tanggal", {
        altInput: true,
        altFormat: "d F Y",   // tampilan di input: 10 Juli 2025
        dateFormat: "Y-m-d",  // format yang dikirim ke backend: 2025-07-10
        allowInput: false,
        locale: "id"
    });

    flatpickr("#tanggal_invoice", {
        altInput: true,
        altFormat: "d F Y",   // tampilan di input: 10 Juli 2025
        dateFormat: "Y-m-d",  // format yang dikirim ke backend: 2025-07-10
        allowInput: false,
        locale: "id"
    });

    // 🔽 Select2 Akun GL
    $('.akun-selectok').select2({
        ajax: {
            url: '{{ route("saldo_awal.akun_gl") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text: q.no_akun + " - " + q.nama};
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
    // 🔽 Select2 Akun GL
    $('.entitas').select2({
        ajax: {
            url: '{{ route("entitas.select") }}',
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
        placeholder: "-- Pilih Entitas --",
        allowClear: true
    });
    @endif

    // 🔽 Select2 Akun GL
    $('.cabang').select2({
        ajax: {
            url: '{{ route("cabang.select") }}',
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
        placeholder: "-- Pilih Cabang --",
        allowClear: true
    });

    // 🔽 Select2 Customer
    $('.partner').select2({
        ajax: {
            url: '{{ route("partner.select") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term, // teks yang diketik user
                    jenis: 'customer', // teks yang diketik user
                    @if(auth()->user()->level != "entitas")
                    entitas_id: $('#entitas_id').val() || null // kirim data tambahan jika ada
                    @endif
                };
            },
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
        placeholder: "-- Pilih Customer --",
        allowClear: true
    });
    @if(auth()->user()->level != "entitas")
    // 🔁 Saat entitas diubah → reset & reload partner
    $('.entitas').on('change', function () {
        $('.partner').val(null).trigger('change'); // kosongkan value dulu
    });
    @endif

    // 🧠 Submit Form
    $("#form_data").submit(function(e){
        e.preventDefault();
        proses_data();
    });
});

// tambah baris baru
$('#btnTambahBaris').click(function() {
    let idx = $('#tableDetail tbody tr').length;
    let row = $('#rowTemplate tr').clone();

    // Ganti placeholder index
    row.find('select, input').each(function() {
        let name = $(this).attr('name').replace('__INDEX__', idx);
        $(this).attr('name', name).val('');
        if ($(this).hasClass('debit-input') || $(this).hasClass('kredit-input')) {
            $(this).val('0');
        }
    });

    // Tambahkan ke tabel utama
    $('#tableDetail tbody').append(row);

    // Inisialisasi select2 untuk baris baru saja
    row.find('.akun-select').select2({
        ajax: {
            url: '{{ route("saldo_awal.akun_gl") }}',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function(q){
                        return {id: q.id, text: q.no_akun + " - " + q.nama};
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

    reIndexRows();
});


// hapus baris
$(document).on('click', '.btn-hapus', function() {
    if ($('#tableDetail tbody tr').length > 1) {
        $(this).closest('tr').remove();
        reIndexRows();
        hitungTotal();
    }
});

// hitung total otomatis
$(document).on('input', '.debit-input, .kredit-input', function() {
    hitungTotal();
});

function hitungTotal() {
    let totalDebit = 0, totalKredit = 0;

    $('.debit-input').each(function() {
        let val = $(this).val().replace(/\./g, '').replace(/,/g, '.'); // hilangkan . ribuan, ubah , ke .
        totalDebit += parseFloat(val) || 0;
    });

    $('.kredit-input').each(function() {
        let val = $(this).val().replace(/\./g, '').replace(/,/g, '.');
        totalKredit += parseFloat(val) || 0;
    });

    $('#totalDebit').text(totalDebit.toLocaleString('id-ID'));
    $('#totalKredit').text(totalKredit.toLocaleString('id-ID'));
}

function reIndexRows() {
    $('#tableDetail tbody tr').each(function(i, tr) {
        $(tr).find('select, input').each(function() {
            let name = $(this).attr('name');
            $(this).attr('name', name.replace(/\d+/, i));
        });
    });
}

function proses_data(){
    let iData = new FormData(document.getElementById("form_data"));
    $.ajax({
        type: "POST",
        url: "{{ route('jurnal.pendapatan.save') }}",
        data: iData,
        cache: false,
        processData: false,
        contentType: false,
        beforeSend: function (){
            $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i>  Simpan..");
            $("#btn-submit").prop("disabled", true);
        },
        success: function(result){
            console.log(result);
            if(result.status == "success"){
                position = "bottom-left";
                icons = result.status;
                pesan = result.messages;
                title = "Saved!";
                info(title, pesan, icons, position);
                $("#btn-submit").html("<i class='fa fa-save'></i> Simpan");
                $("#btn-submit").prop("disabled", false);
                setTimeout(() => {
                    window.location.href = "{{ route('jurnal.pendapatan') }}";
                }, 1500);
            }
        },
        error: function(e){
            console.log(e);
            $("#btn-submit").html("<i class='fa fa-save'></i> Simpan");
            $("#btn-submit").prop("disabled", false);
            error_message(e,'Proses Data Error');
        }
    });
}
</script>
@endsection
