@extends('layouts.main')

@section('page-title', 'Application Docs')

@section ('app-content')
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="mt-5">
                    <h4 class="mb-0">
                        <span class="fa fa-book text-success fa-fw"></span>
                        Docs
                    </h4>
                    <div class="list-group mt-5">
                        <a href="/" class="list-group-item list-group-item-action text-info">
                            <span class="fa fa-arrow-left"></span>
                            Back to App
                        </a>
                        @foreach ($sections as $section => $content)
                            <a href="#{{ Str::of($section)->snake()->replace('.md', '') }}" class="list-group-item list-group-item-action text-info">{{ Str::of($section)->title()->replace('.md', '') }}</a>
                        @endforeach
                        <a href="/docs/api" class="list-group-item list-group-item-action text-info">API Docs</a>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="mt-5">
                    @foreach ($sections as $section => $content)
                        <div id="{{ Str::of($section)->snake()->replace('.md', '') }}" class="card shadow-sm mb-5">
                            <div class="card-header">
                                {{ Str::of($section)->title()->replace('.md', '') }}
                            </div>
                            <div class="card-body">
                                {!! Str::of($content)->markdown() !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@stop

@push('javascript')
<script type="module">
    import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';

    mermaid.initialize({
        darkMode: (window.matchMedia('(prefers-color-scheme: dark)').matches),
        startOnLoad: true
    });

    await mermaid.run({
        querySelector: '.language-mermaid',
    });
</script>
@endpush
