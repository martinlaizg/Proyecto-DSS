@extends('layouts.master')
@section('title', 'Inicio')
@section('content')
@include('cabecera',array('section'=>'Inicio'))
<div class="contenedor row">
      <div class="col-md-6 col-md-offset-1">
            @include('tabla',array('values' => $values, 'lista' => $lista))
      </div>
      <div class="col-md-5">

      </div>
</div>
@endsection