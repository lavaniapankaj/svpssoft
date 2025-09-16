<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ config('myconfig.favIcon') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
</head>

<body>
    <div class="container py-5">
        <div class="col-md-12">
            <div class="row">
                <div class="col-md-4 mt-3">
                    <a href="{{ route('admin.login') }}" class="ml-5 fs-1  text-decoration-none" style="font-size: -webkit-xxx-large;">
                        <div class="card shadow border-0 h-100">
                            <div class="card-body d-flex flex-column justify-content-between align-items-center">
                                <img src="{{ config('myconfig.admin') }}" alt="Admin">
                                <h4 class="text-center mt-2 text-dark">Admin</h4>
                            </div>
                        </div>
                        
                    </a>
                </div>
                <div class="col-md-4 mt-3">
                    <a href="{{ route('fee.login') }}" class="ml-5fs-1  text-decoration-none" style="font-size: -webkit-xxx-large;">
                         <div class="card shadow border-0 h-100">
                            <div class="card-body d-flex flex-column justify-content-between align-items-center">
                                <img src="{{ config('myconfig.fee') }}" alt="Fee">
                                <h4 class="text-center mt-2 text-dark">Fee</h4>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mt-3">
                    <a href="{{ route('student.login') }}" class="ml-5 fs-1  text-decoration-none" style="font-size: -webkit-xxx-large; ">
                         <div class="card shadow border-0 h-100">
                            <div class="card-body d-flex flex-column justify-content-between align-items-center">
                                <img src="{{ config('myconfig.student') }}" alt="Std">
                                <h4 class="text-center mt-2 text-dark">Student</h4>
                            </div>
                        </div>
                    </a>
                </div>
               
                    <div class="col-md-4 mt-3">
                        <a href="{{ route('marks.login') }}" class="ml-5 fs-1  text-decoration-none" style="font-size: -webkit-xxx-large;">
                            <div class="card shadow border-0 h-100">
                                <div class="card-body d-flex flex-column justify-content-between align-items-center">
                                    <img src="{{ config('myconfig.marks') }}" alt="Marks">
                                    <h4 class="text-center mt-2 text-dark">Marks</h4>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mt-3">
                        <a href="{{ route('inventory.login') }}" class="ml-5 fs-1 text-decoration-none"
                            style="font-size: -webkit-xxx-large;">
                            <div class="card  shadow border-0 h-100">
                                <div class="card-body d-flex flex-column justify-content-between align-items-center">
                                    <img src="{{ config('myconfig.inventory') }}" alt="Inventory">
                                    <h4 class="text-center mt-2 text-dark">Inventory</h4>
                                </div>
                            </div>
                        </a>
                    </div>
               
            </div>

        </div>
    </div>
    <script>
        window.onload = function() {
            if ('caches' in window) {
                caches.keys().then(function(cacheNames) {
                    cacheNames.forEach(function(cacheName) {
                        caches.delete(cacheName);
                    });
                });
            }
        };
    </script>
</body>

</html>
