@extends('layouts.app')
@section('title','Create New Saldo Awal Akun GL')

@section('breadcrumb')
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6"><h5 class="mb-2">Create New Saldo Awal Akun GL</h5></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('saldo_awal') }}">Saldo Awal Akun GL</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-8">
            <div class="card card-success card-outline mb-4">
                <div class="card-header"><div class="card-title">Create New Saldo Awal Akun GL</div></div>
                
                <form action="javascript:void(0)" enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("post")

                    <div class="card-body">

                        <!-- 🔄 Ganti Tahun → Periode -->
                        <div class="row mb-3">
                            <label for="periode" class="col-sm-3 col-form-label">Periode <b class="text-danger">*</b></label>
                            <div class="col-sm-9">
                                <input type="text" id="periode" name="periode" class="form-control" 
                                       placeholder="Pilih periode (YYYY-MM)" readonly required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="akun_gl_id" class="col-sm-3 col-form-label">Akun GL</label>
                            <div class="col-sm-9">
                                <select name="akun_gl_id" id="akun_gl_id" class="form-control akun_gl">
                                    <option value="">-- Pilih Akun GL --</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="entitas_id" class="col-sm-3 col-form-label">Entitas <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="entitas_id" id="entitas_id" class="form-control entitas">
                                    <option value="">-- Pilih Entitas --</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="saldo" class="col-sm-3 col-form-label">Saldo <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text" onkeyup="formatRupiah(this)" class="form-control" id="saldo" name="saldo" placeholder="Saldo" />
                            </div>
                        </div>

                    </div>

                    <div class="card-footer">
                        <a href="{{ route('saldo_awal') }}" class="btn btn-danger btn-flat btn-sm">
                            <i class="fa fa-mail-reply"></i> Kembali
                        </a>
                        <button type="submit" id="btn-submit" class="btn btn-success btn-flat btn-sm float-end">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>  
        </div>
    </div>    
</div>

@endsection

@section('js')

<script>
$(document).ready(function() {

    // 🔧 Inisialisasi Flatpickr Month Picker
    flatpickr("#periode", {
        altInput: true,
        altFormat: "F Y",   // tampil misalnya: Oktober 2025
        dateFormat: "Y-m",  // dikirim ke backend: 2025-10
        plugins: [
            new monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "F Y"
            })
        ],
        allowInput: false,
        locale: "id"
    });

    // 🔽 Select2 Akun GL
    $('.akun_gl').select2({
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

    // 🔽 Select2 Entitas
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

    // 🧠 Submit Form
    $("#form_data").submit(function(e){
        e.preventDefault();
        proses_data();
    });
});

function proses_data(){
    let iData = new FormData(document.getElementById("form_data"));
    $.ajax({
        type: "POST",
        url: "{{ route('saldo_awal.save') }}",
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
                    window.location.href = "{{ route('saldo_awal') }}";
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
