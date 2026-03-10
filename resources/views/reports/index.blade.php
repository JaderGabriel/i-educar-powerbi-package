@extends('layout.default')

@section('content')
<div class="container-fluid">
    <h2 class="mb-4">BI - Relatórios</h2>

    <div class="row">
        @foreach($relatorios as $relatorio)
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">{{ $relatorio['nome'] }}</h5>
                        <p class="card-text text-muted">{{ $relatorio['descricao'] }}</p>
                        <a href="{{ $relatorio['rota'] }}" class="btn btn-primary">Acessar</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
