@extends('layouts.admin')

@section('title', 'Error')
@section('page-title', 'Error')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-danger text-white">Error</div>
            <div class="card-body">
                <p>{{ $message ?? 'Ha ocurrido un error en el servidor.' }}</p>
                <p>
                    <a href="{{ route('login') }}" class="btn btn-primary">Ir al login</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
