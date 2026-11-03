<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

        <title>Application Error</title>

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
        <div id="app" class="min-vh-100 d-flex align-items-center">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <div class="card shadow-sm border-danger">
                            <div class="card-body bg-body-tertiary rounded">
                                <div>
                                    ::CLOUDFLARE_ERROR_500S_BOX::
                                </div>
                                <div class="text-center">
                                    <a href="{{ url('/') }}" class="btn mt-4">
                                        <span class="fa fa-solid fa-house"></span>
                                        Home
                                    </a>
                                    @if (config('mission-control.api_uuid'))
                                        <a href="{{ 'https://missioncontrolapp.io/status/'.config('mission-control.api_uuid') }}" class="btn mt-4">
                                            <span class="fa fa-solid fa-heart-pulse"></span>
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
    </body>
</html>
