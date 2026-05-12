<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= s($data_title ?? 'PHP MVC Scaffold') ?></title>
    <?= vite() . PHP_EOL ?>
</head>
<body>
    <main>
        <?= $content ?? '' ?>
    </main>
</body>
</html>
