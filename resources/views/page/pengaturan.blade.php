@extends('layouts.app')
@section('title','Pengaturan Umum')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Pengaturan Umum</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active" aria-current="page">Pengaturan Umum</li>
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
                <div class="card-header"><div class="card-title">Pengaturan Umum</div></div>
                <!--end::Header-->
                <!--begin::Form-->
                <form action='javascript:void(0)' enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("post")
                <!--begin::Body-->
                <div class="card-body">
                    <div class="row mb-3">
                    <label for="nama_aplikasi" class="col-sm-3 col-form-label">Nama Aplikasi <b class='text-danger'>*</b></label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" value="{{ getNamaAplikasi() }}" id="nama_aplikasi" name="nama_aplikasi" placeholder="Nama Aplikasi" />
                    </div>
                    </div>

                    <div class="row mb-3">
                    <label for="logo" class="col-sm-3 col-form-label">Logo</label>
                        <div class="col-sm-9">
                            <div class="input-group mb-3">
                            <input type="file" class="form-control" accept='image/*' name='logo' placeholder='logo' id="logo" />
                            <label class="input-group-text" for="logo">Upload</label>
                        </div>
                    </div>
                    </div>

                    <div class="row mb-3">
                    <label for="favicon" class="col-sm-3 col-form-label">Favicon</label>
                        <div class="col-sm-9">
                            <div class="input-group mb-3">
                            <input type="file" class="form-control" accept='image/*' name='favicon' placeholder='Favicon' id="favicon" />
                            <label class="input-group-text" for="favicon">Upload</label>
                        </div>
                    </div>
                    </div>

                    <div class="row mb-3">
                        <label for="favicon" class="col-sm-3 col-form-label">Akun GL Laba Rugi Tahun Berjalan</label>
                        <div class="col-sm-9">
                            <div class="input-group mb-3">
                                <select class="form-select akun-gl-lb-rugi-berjalan" name="akun_gl_laba_rugi_berjalan">
                                    <option value="">-- Pilih Akun GL Laba Tahun Rugi Berjalan --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="favicon" class="col-sm-3 col-form-label">Akun GL Laba Rugi Tahun Lalu</label>
                        <div class="col-sm-9">
                            <div class="input-group mb-3">
                                <select class="form-select akun-gl-lb-rugi-lalu" name="akun_gl_laba_rugi_lalu">
                                    <option value="">-- Pilih Akun GL Laba Tahun Rugi Lalu --</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <!--end::Body-->
                <!--begin::Footer-->
                <div class="card-footer">
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
    $(document).ready(function(){
        $('.akun-gl-lb-rugi-berjalan').select2({
            ajax: {
                url: '{{ route("m_akun.search") }}',
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
            placeholder: "-- Pilih Akun GL Laba Rugi Tahun Berjalan --",
            allowClear: true
        });

        $('.akun-gl-lb-rugi-lalu').select2({
            ajax: {
                url: '{{ route("m_akun.search") }}',
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
            placeholder: "-- Pilih Akun GL Laba Rugi Tahun Lau --",
            allowClear: true
        });

        @if(!empty($akun_lr_berjalan))
            var option = new Option("{{ $akun_lr_berjalan->no_akun }} - {{ $akun_lr_berjalan->nama }}", {{ $akun_lr_berjalan->id }}, true, true);
            $(".akun-gl-lb-rugi-berjalan").append(option).trigger('change');    
        @endif

        @if(!empty($akun_lr_tahun_lalu))
            var option = new Option("{{ $akun_lr_tahun_lalu->no_akun }} - {{ $akun_lr_tahun_lalu->nama }}", {{ $akun_lr_tahun_lalu->id }}, true, true);
            $(".akun-gl-lb-rugi-lalu").append(option).trigger('change');    
        @endif
    })
    proses_data = function(){
        let iData = new FormData(document.getElementById("form_data"));
        $.ajax({
            type    : "POST",
            url     : "{{ route('setting.save') }}",
            data    : iData,
            cache   : false,
            processData: false,
            contentType: false,
            beforeSend  : function (){
                $("#btn-submit").html("<i class='fa fa-spinner fa-spin'></i>  Simpan..")
                $("#btn-submit").prop("disabled",true);
            },
            success: function(result){
                console.log(result)
                if(result.status == "success"){
                    position = "bottom-left";
                    icons = result.status;
                    pesan = result.messages;
                    title = "Saved!";
                    info(title,pesan,icons,position);
                    $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                    $("#btn-submit").prop("disabled",false);
                    setTimeout(() => {
                        window.location.href = "{{ route('setting') }}";
                    }, 2000);
                    
                }
            },
            error: function(e){
                console.log(e)
                $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                $("#btn-submit").prop("disabled",false);
                error_message(e,'Proses Data Error');
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

