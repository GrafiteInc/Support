<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">

        <title>Application Up</title>

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

        <style>
            @media (prefers-reduced-motion: no-preference) {
                .tada {
                    animation: tada 1s ease-in-out both;
                }
            }

            @keyframes tada {
                0% { transform: scale3d(1, 1, 1); }
                10%, 20% { transform: scale3d(.9, .9, .9) rotate3d(0, 0, 1, -3deg); }
                30%, 50%, 70%, 90% { transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, 3deg); }
                40%, 60%, 80% { transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, -3deg); }
                100% { transform: scale3d(1, 1, 1); }
            }
        </style>
    </head>
    <body>
        <div id="app" class="min-vh-100 d-flex align-items-center">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 offset-md-3">
                        <div class="card shadow-sm tada">
                            <div class="card-body bg-body-tertiary rounded text-center">
                                <h2>
                                    <span class="fa fa-solid fa-heart-pulse text-success"></span>
                                    Application Up
                                </h2>
                                <p class="mb-0">The application is running smoothly.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
