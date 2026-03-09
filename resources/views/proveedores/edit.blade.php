@extends('layouts.app')

@section('content')
<div class="section-body">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fas fa-truck mr-2"></i>Editar Proveedor</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('proveedores.update', $proveedor->prov_id) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="prov_nombre" class="form-control" value="{{ $proveedor->prov_nombre }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Razón Social</label>
                                    <input type="text" name="prov_razon_social" class="form-control" value="{{ $proveedor->prov_razon_social }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>NIT</label>
                                    <input type="text" name="prov_nit" class="form-control" value="{{ $proveedor->prov_nit }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Teléfono</label>
                                    <input type="text" name="prov_telefono" class="form-control" value="{{ $proveedor->prov_telefono }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="prov_email" class="form-control" value="{{ $proveedor->prov_email }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Dirección</label>
                                    <input type="text" name="prov_direccion" class="form-control" value="{{ $proveedor->prov_direccion }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Persona de Contacto</label>
                                    <input type="text" name="prov_contacto" class="form-control" value="{{ $proveedor->prov_contacto }}">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                        <a href="{{ route('proveedores.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
