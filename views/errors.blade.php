@extends('layouts.main')

@section('page-title', 'Error')

@section ('app-content')
    <div class="container">
        <div class="row">
            <div class="col bmx-vh-50">
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="bmx-mt-n5">
                    <div class="card shadow-sm">
                        <div class="card-body bg-body-tertiary rounded text-center">
                            <h2>
                                <span class="fa fa-heart-pulse text-danger"></span>
                                ::CLOUDFLARE_ERROR_500S_BOX::
                            </h2>
                            <p>We're actively working on addressing any root issues...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
