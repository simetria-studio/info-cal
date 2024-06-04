@extends('layouts.auth')
@section('title')
    {{ __('messages.slot_schedule') }}
@endsection
@section('content')
    <div class="container">
        <div class="row">
            <div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
                <div class="mb-5">
                    @include('layouts.errors')
                    @include('flash::message')
                </div>
                <div>
                    @php
                        $styleCss = 'style';
                    @endphp
                    <div class="w-lg-900px bg-white rounded shadow-sm">
                        <div class="h-auto">
                            <div class="border-bottom">
                                <div class="card">
                                    <h4>Pix Response</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endsection
    @push('scripts')
     
    @endpush
