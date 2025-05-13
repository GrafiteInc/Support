<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

        <title>@yield('page-title', 'Grafene')</title>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/flatly/bootstrap.min.css">
    </head>
    <body>
        <div id="app" class="min-vh-100">
            <div class="w-100 bmx-overflow-x-hidden min-vh-100">
                <div class="container">
                    <div class="row mt-5">
                        <div class="col-md-6 offset-md-3">
                            <div class="mt-5">
                                <div class="card shadow-sm border-danger">
                                    <div class="card-body bg-body-tertiary rounded">
                                        <div>
                                        ::CLOUDFLARE_ERROR_500S_BOX::
                                        </div>
                                        <div class="text-center">
                                            <a href="{{ route('dashboard') }}" class="btn mt-4">
                                                <span class="fa fa-solid fa-house"></span>
                                                Home
                                            </a>
                                            @if (config('mission-control.api_uuid'))
                                                <a href="{{ 'https://missioncontrolapp.io/status/'.config('mission-control.api_uuid') }}" class="btn mt-4">
                                                    <span class="fa fa-solid fa-house"></span>
                                                    Uptime Monitor
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let _theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                document.documentElement.setAttribute('data-bs-theme', _theme);
            });
        </script>
    </body>
</html>
