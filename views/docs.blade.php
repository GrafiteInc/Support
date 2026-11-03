<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

        <title>Application Docs</title>

        <script>
            (function () {
                let _query = window.matchMedia('(prefers-color-scheme: dark)');
                let _setTheme = function () {
                    document.documentElement.setAttribute('data-bs-theme', _query.matches ? 'dark' : 'light');
                };

                _setTheme();
                _query.addEventListener('change', _setTheme);
            })();
        </script>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/flatly/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    </head>
    <body>
        <div id="app" class="container mb-5">
            <div class="row">
                <div class="col-md-4">
                    <div class="mt-5">
                        <h4 class="mb-0">
                            <span class="fa fa-solid fa-book text-success fa-fw"></span>
                            Docs
                        </h4>
                        <div class="list-group mt-5">
                            <a href="{{ url('/') }}" class="list-group-item list-group-item-action text-info">
                                <span class="fa fa-solid fa-arrow-left"></span>
                                Back to App
                            </a>
                            @foreach ($sections as $section => $content)
                                <a href="#{{ Str::of($section)->snake()->replace('.md', '') }}" class="list-group-item list-group-item-action text-info">{{ Str::of($section)->title()->replace('.md', '') }}</a>
                            @endforeach
                            <a href="{{ url('/docs/api') }}" class="list-group-item list-group-item-action text-info">API Docs</a>
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

        <script type="module">
            import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';

            mermaid.initialize({
                theme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'default',
                startOnLoad: false
            });

            await mermaid.run({
                querySelector: '.language-mermaid',
            });
        </script>
    </body>
</html>
