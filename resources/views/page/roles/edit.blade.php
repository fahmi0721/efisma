@extends('layouts.app')
@section('title','Update Role')
@section('breadcrumb')
<div class="app-content-header">
    <!--begin::Container-->
    <div class="container-fluid">
    <!--begin::Row-->
    <div class="row">
        <div class="col-sm-6"><h5 class="mb-2">Update Role</h5></div>
        <div class="col-sm-6">
        <ol class="breadcrumb float-sm-end">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('role') }}">Role</a></li>
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
        <div class="col-12">
            <div class="card card-success card-outline mb-4">
                <div class="card-header"><div class="card-title">Update Role</div></div>
                <!--end::Header-->
                <!--begin::Form-->
                <form action='javascript:void(0)' enctype="multipart/form-data" id="form_data">
                    @csrf
                    @method("put")
                    <input type="hidden" name='id' value="{{ $id }}">
                    <!--begin::Body-->
                    <div class="card-body">
                        <div class="row mb-3">
                            <label for="nama" class="col-sm-3 col-form-label">Nama Role <b class='text-danger'>*</b></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $data->name }}" id="nama" name="nama" placeholder="Nama Role" />
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="description" class="col-sm-3 col-form-label">Deskripsi</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="{{ $data->description }}" id="description" name="description" placeholder="Deskripsi" />
                            </div>
                        </div>

                        
                        <hr/>
                         <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-success" id="btnAddPermission">
                                <i class='fa fa-plus-square'></i> Tambah Permission
                            </button>
                        </div>
                        <h4>Permissions</h4>

                        <div class="table-responsive">
                            <table class="table table-bordered align-middle" id="rolePermissionTable">
                                <thead>
                                    <tr>
                                         <th width="25%">Module</th>
                                        <th width="25%">Permission Name</th>
                                        <th width="25%">Slug</th>
                                        <th width="10%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($permissions as $p)
                                    <tr>
                                        <td>{{ $p->module_name }}</td>
                                        <td>{{ $p->name }}</td>
                                        <td>{{ $p->slug }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm btn-remove" data-id="{{ $p->id }}">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                        <input type="hidden" name="permissions[]" value="{{ $p->id }}">
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            

                        </div>

                    </div>
                    <!--end::Body-->
                    <!--begin::Footer-->
                    <div class="card-footer">
                      <a href="{{ route('role') }}" class="btn btn-danger btn-flat btn-sm"><i class="fa fa-mail-reply"></i> Kembali</a>
                      <button type="submit" id="btn-submit" class="btn btn-success btn-flat btn-sm float-end"><i class="fa fa-save"></i> Simpan</button>
                  </div>
                    <!--end::Footer-->
                </form>
                <!--end::Form-->
            </div>  
        </div>
    </div>    
</div>

{{-- MODAL SELECT PERMISSION --}}
<div class="modal fade" id="modalPermission" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Tambah Permission Berdasarkan Module</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                {{-- SELECT MODULE --}}
                <div class="mb-3">
                    <label>Pilih Module</label>
                    <select id="selectModule" class="selectModule form-control">
                        <option value="">-- Pilih module --</option>
                        
                    </select>
                </div>

                {{-- LIST PERMISSION --}}
                <div id="permissionArea"></div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-success" id="btnAddSelected">Tambahkan</button>
            </div>

        </div>
    </div>
</div>
@endsection
@section('js')
<script>
    
    let selectedPermissions = [
        @foreach($permissions as $p)
            {{ $p->id }},
        @endforeach
    ];
    $('#btnAddPermission').click(function () {
        $('#modalPermission').modal('show');
        $('#selectModule').val('');
        $('.selectModule').select2({
            ajax: {
                url: '{{ route("module.select") }}',
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data.map(function(q){
                            return {id: q.id, text: q.name};
                        })
                    };
                },
                cache: true
            },
            dropdownParent: $('#modalPermission'),
            theme: 'bootstrap4',
            width: '100%',
            placeholder: "-- Pilih Module --",
            allowClear: true
        });
        $('#permissionArea').html('');
    });


    // LOAD PERMISSION BERDASARKAN MODULE
    $('.selectModule').on('select2:select', function(e) {
        let moduleId = $(this).val();

        if (!moduleId) {
            $('#permissionArea').html('');
            return;
        }

        $.get("{{ route('module.detail_permission') }}", { id: moduleId }, function (res) {

            let html = `
                <h5>Permissions:</h5>
                <div class="row">
            `;
            let m = res.data;
            res.permissions.forEach(p => {
                html += `
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input perm-check" 
                                type="checkbox" 
                                data-id="${p.id}"
                                data-module="${m.name}"
                                data-name="${p.name}"
                                data-slug="${p.slug}">
                            <label class="form-check-label">
                                ${p.name} (${p.slug})
                            </label>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;

            $('#permissionArea').html(html);
        });
    });
    
    // TAMBAHKAN PERMISSION KE TABLE ROLE
    $('#btnAddSelected').click(function () {
        

        $('.perm-check:checked').each(function () {
            let id     = $(this).data('id');
            let module = $(this).data('module');
            let name   = $(this).data('name');
            let slug   = $(this).data('slug');

            // CEK DUPLIKAT
            if (selectedPermissions.includes(id)) return;

            selectedPermissions.push(id);

            $('#rolePermissionTable tbody').append(`
                <tr>
                    <td>${module}</td>
                    <td>${name}</td>
                    <td>${slug}</td>

                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm btn-remove" data-id="${id}">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>

                    <input type="hidden" name="permissions[]" value="${id}">
                </tr>
            `);
        });

        $('#modalPermission').modal('hide');
    });

    // REMOVE ROW
    $(document).on('click', '.btn-remove', function () {
        let id = $(this).data('id');
        selectedPermissions = selectedPermissions.filter(pid => pid !== id);
        $(this).closest('tr').remove();
    });

    proses_data = function(){
        var id = $("#id").val();
        let iData = new FormData(document.getElementById("form_data"));
        $.ajax({
            type    : "POST",
            url     : "{{ route('role.update', ':id') }}".replace(':id', id),
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
                        window.location.href = "{{ route('role') }}";
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

