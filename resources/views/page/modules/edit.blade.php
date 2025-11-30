@extends('layouts.app')
@section('title','Update Data Module')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Update Data Module</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('module') }}">Module</a></li>
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
        <div class="col-12">
            <div class="card card-success card-outline mb-4">
                <div class="card-header"><div class="card-title">Update Data Module</div></div>
                <!--end::Header-->
                <!--begin::Form-->
                <form action='javascript:void(0)'  id="form_data">
                    @csrf
                    @method("put")
                    <input type="hidden" value="{{ $id }}" id='id' name='id'>
                    <!--begin::Body-->
                    <div class="card-body">
                        <div class="row mb-3">
                            <label for="nama" class="col-sm-3 col-form-label">Nama <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text" value="{{ $data->name }}" class="form-control" id="nama" name="nama" placeholder="Nama Module" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="slug" class="col-sm-3 col-form-label">Slug <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text" value="{{ $data->slug }}" class="form-control" id="slug" name="slug" placeholder="Slug" />
                            </div>
                        </div>

                        <div class="row mb-3">
                            <label for="group" class="col-sm-3 col-form-label">Group</label>
                            <div class="col-sm-9">
                                <input type="text" value="{{ $data->group }}" class="form-control" id="group" name="group" placeholder="Group" />
                            </div>
                        </div>
                       <h4>Permisssions</h4>
                       <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="permissionTable">
                                <thead>
                                    <tr>
                                        <th width="35%">Permission Name</th>
                                        <th width="35%">Slug</th>
                                        <th width="5%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($permissions as $i => $p)
                                    <tr>
                                        <td>
                                            <input type="hidden" name="permissions[{{ $i }}][id]" value="{{ $p->id }}">
                                            <input type="text" 
                                                name="permissions[{{ $i }}][name]" 
                                                class="form-control perm-name"
                                                value="{{ $p->name }}"
                                                required>
                                        </td>

                                        <td>
                                            <input type="text" 
                                                name="permissions[{{ $i }}][slug]" 
                                                class="form-control perm-slug"
                                                value="{{ $p->slug }}">
                                        </td>

                                        <td class="text-center">
                                            <button type="button" 
                                                    class="btn btn-danger btn-sm removeRow">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5">
                                            <button type="button" class="btn btn-success btn-sm" id="addRow">
                                                <i class="fas fa-plus"></i> Tambah Baris
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                    <div class="card-footer">
                      <a href="{{ route('module') }}"  class="btn btn-danger btn-flat btn-sm"><i class="fa fa-mail-reply"></i> Kembali</a>
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
    let rowIndex = {{ count($permissions) }};

    // Tambah Row
    $('#addRow').click(function () {
        let html = `
            <tr>
                <td>
                    <input type="hidden" name="permissions[${rowIndex}][id]" value="">
                    <input type="text" name="permissions[${rowIndex}][name]" 
                        class="form-control perm-name" placeholder="Permission Name" required>
                </td>
                <td>
                    <input type="text" name="permissions[${rowIndex}][slug]" 
                        class="form-control perm-slug" placeholder="Otomatis">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm removeRow">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;

        $('#permissionTable tbody').append(html);
        rowIndex++;
    });

    // Hapus row
    $(document).on('click', '.removeRow', function () {
        $(this).closest('tr').remove();
    });
    proses_data = function(){
        let iData = $("#form_data").serialize();
        var id = $("#id").val();
        $.ajax({
            type    : "POST",
            url     : "{{ route('module.update', ':id') }}".replace(':id', id),
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
                    pesan = result.messages;
                    title = "Updated!";
                    info(title,pesan,icons,position);
                    $("#btn-submit").html("<i class='fa fa-save'></i> Simpan")
                    $("#btn-submit").prop("disabled",false);
                    setTimeout(() => {
                        window.location.href = "{{ route('module') }}";
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

