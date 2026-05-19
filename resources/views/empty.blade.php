@extends('mail-log::layout', ['title' => 'Empty'])

@section('content')
    <x-mail-log::empty-state :filtered="false" />
@endsection
