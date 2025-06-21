<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анализатор страниц</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .navbar-success {
            background-color: #198754 !important;
        }
        .footer-success {
            background-color: #198754;
            color: white;
        }
        .alert-success {
            background-color:rgb(255, 255, 255);
            border-color:rgb(255, 255, 255);
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .nav-link.active {
            background-color:rgb(12, 110, 64);
            border-radius: 4px;
        }
    </style>
</head>
<body class="min-vh-100 d-flex flex-column">
    <header class="flex-shrink-0">
        <nav class="navbar navbar-expand-md navbar-dark navbar-success px-3">
            <a class="navbar-brand" href="/">Анализатор страниц</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" 
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentRoute == '/' ? 'active' : '' ?>"
                           href="/">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentRoute == 'urls.index' ? 'active' : '' ?>"
                           href="/urls">Сайты</a>
                    </li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="flex-grow-1">
        <?= $content ?>
    </main>
</body>
</html>