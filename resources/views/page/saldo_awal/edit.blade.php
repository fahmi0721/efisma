@extends('layouts.app')
@section('title','Update Data Akun GL')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Update Data Akun GL</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('m_akun') }}">Akun GL</a></li>
            <li class="breadcrumb-item active" aria-current="page">Update</li>
        </ol>
        </div>
    </div>
    <!--end::Row-->
    </div>
    <!--end::Container-->
</div>
@endsection
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-8">
            <div class="card card-success card-outline mb-4">
                <div class="card-header"><div class="card-title">Update Data Akun GL </div></div>
                <!--end::Header-->
                <!--begin::Form-->
                <form action='javascript:void(0)'  id="form_data">
                    @csrf
                    @method("put")
                    <input type="hidden" value="{{ $id }}" id='id' name='id'>
                    <!--begin::Body-->
                    <div class="card-body">
                        <div class="row mb-3">
                            <label for="periode" class="col-sm-3 col-form-label">Periode <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text" value="{{ $data->periode }}" class="form-control" id="periode" name="periode" placeholder="Periode" />
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
                                <input type="text" value="{{ number_format($data->saldo, 0, ',', '.') }}" onkeyup="formatRupiah(this)" class="form-control" id="saldo" name="saldo" placeholder="Saldo" />
                            </div>
                        </div>
                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                    <div class="card-footer">
                      <a href="{{ route('saldo_awal') }}" class="btn btn-danger btn-flat btn-sm"><i class="fa fa-mail-reply"></i> Kembali</a>
                      <button type="submit" id="btn-submit" class="btn btn-success btn-flat btn-sm float-end"><i class="fa fa-save"></i> Simpan</button>
                  </div>
                    <!--end::Footer-->
                </form>
                <!--end::Form-->
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

        $('.entitas').select2({
            ajax: {
                url: '{{ route("saldo_awal.entitas") }}',
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
        
        @if(!empty($akun_selected))
            var akun_selected = "{{ $akun_selected->id }}";
            var option = new Option("{{ $akun_selected->nama }}", {{ $akun_selected->id }}, true, true);
            $(".akun_gl").append(option).trigger('change');    
        @endif

        @if(!empty($entitas_selected))
            var entitas_selected = "{{ $entitas_selected->id }}";
            var option = new Option("{{ $entitas_selected->nama }}", {{ $entitas_selected->id }}, true, true);
            $(".entitas").append(option).trigger('change');    
        @endif

        
    });
    proses_data = function(){
        let iData = $("#form_data").serialize();
        var id = $("#id").val();
        $.ajax({
            type    : "POST",
            url     : "{{ route('saldo_awal.update', ':id') }}".replace(':id', id),
            data    : iData,
            cache   : false,
            beforeSend  : function (){
                $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i>  Simpan..")
                $("#btn-submit").prop("disabled",true);
            },
            success: function(result){
                console.log(result)
                if(result.status == "success"){
                    position = "bottom-left";
                    icons = result.status;
                    pesan = result.message;
                    title = "Updated!";
                    info(title,pesan,icons,position);
                    $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                    $("#btn-submit").prop("disabled",false);
                    setTimeout(() => {
                        window.location.href = "{{ route('saldo_awal') }}";
                    }, 2000);
                    
                }
            },
            error: function(e){
                console.log(e)
                $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                $("#btn-submit").prop("disabled",false);
                error_message(e,'Server Error!');
            }
        })
    }

    $(function() {
        $("#form_data").submit(function(e){
            e.preventDefault();
            proses_data();
        });
    });
</script>
@endsection

