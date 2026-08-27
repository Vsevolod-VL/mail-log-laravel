@extends('mail-log::layout', ['title' => 'Leer'])

@section('content')
    <x-mail-log::empty-state :filtered="false" />
@endsection
