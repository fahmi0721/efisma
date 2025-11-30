@extends('layouts.app')
@section('title','Update Users')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Update Users</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('users') }}">Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
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
                <div class="card-header"><div class="card-title">Update Users</div></div>
                <!--end::Header-->
                <!--begin::Form-->
                <form action='javascript:void(0)' enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("put")
                    <input type="hidden" name='id' value='{{ $id }}'>
                    <!--begin::Body-->
                    <div class="card-body">
                        <div class="row mb-3">
                            <label for="nama" class="col-sm-3 col-form-label">Nama <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $data->nama }}" id="nama" name="nama" placeholder="Nama" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="jabatan" class="col-sm-3 col-form-label">Jabatan</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $data->jabatan }}" id="jabatan" name="jabatan" placeholder="Jabatan" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="email" class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $data->email }}" id="email" name="email" placeholder="Email" />
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="entitas_id" class="col-sm-3 col-form-label">Entitas <b class='text-danger'>**</b></label>
                            <div class="col-sm-9">
                                <select id="entitas_id" name="entitas_id" class="form-select form-control entitas_id">
                                    <option value="">..:: Pilih Entitas ::..</option>
                                </select>
                            </div>
                        </div>
                        <hr />
                        <div class="row mb-3">
                            <label for="username" class="col-sm-3 col-form-label">Username <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" disabled id="username" value="{{ $data->username }}" name="username" placeholder="Username" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="level" class="col-sm-3 col-form-label">Level <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <select name="level" id="level" class='form-select form-control'>
                                    <option value="">..:: Pilih Level ::..</option>
                                    <option value="admin" {{ $data->level == "admin" ? "selected" : "" }}>Admin</option>
                                    <option value="pusat" {{ $data->level == "pusat" ? "selected" : "" }}>Pusat</option>
                                    <option value="entitas" {{ $data->level == "entitas" ? "selected" : "" }}>Entitas</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="password" class="col-sm-3 col-form-label"></label>
                            <div class="col-sm-9">
                                <input type="checkbox" name="change" value="1"  id="change" name="change" />
                                Cahnge Password
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="password" class="col-sm-3 col-form-label">Password</label>
                            <div class="col-sm-9">
                                <input type="password" disabled class="form-control" id="password" name="password" placeholder="Password" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="password_confirmation"  class="col-sm-3 col-form-label">Password Komfirmasi </label>
                            <div class="col-sm-9">
                                <input type="password" disabled class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Password Komfirmasi" />
                            </div>
                        </div>

                        
                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                    <div class="card-footer">
                      <a href="{{ route('users') }}" class="btn btn-danger btn-flat btn-sm"><i class="fa fa-mail-reply"></i> Kembali</a>
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
    $('.entitas_id').select2({
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
        minimumResultsForSearch: -1, // -1 = search box selalu disembunyikan
        escapeMarkup: markup => markup
    });
    @if(!empty($entitas_id))
        var entitas_id = "{{ $entitas_id->id }}";
        var option = new Option("{{ $entitas_id->id }} - {{ $entitas_id->nama }}", {{ $entitas_id->id }}, true, true);
        $(".entitas_id").append(option).trigger('change');    
    @endif

    $("#change").click(function(){
        if($(this).is(':checked')){
            $("#password").prop("disabled",false);
            $("#password_confirmation").prop("disabled",false);
        }else{
            $("#password").prop("disabled",true);
            $("#password_confirmation").prop("disabled",true);
        }
    })
    proses_data = function(){
        $("#username").prop("disabled",false);
        var id = $("#id").val();
        let iData = new FormData(document.getElementById("form_data"));
        $.ajax({
            type    : "POST",
            url     : "{{ route('users.update', ':id') }}".replace(':id', id),
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
                        window.location.href = "{{ route('users') }}";
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

