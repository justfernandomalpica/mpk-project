# RaccoOn

Plantilla PHP simple basada en MVC para iniciar proyectos pequeños sin cargar un framework completo. Esta base incluye un bootstrap claro, router propio, controladores, helpers, respuestas JSON, logger local opcional y una capa ActiveRecord ligera para proyectos que usen MySQL con `mysqli`.

Esta arquitectura esta pensada para sitios, APIs pequenas, prototipos funcionales, paneles internos o aplicaciones de baja complejidad. No intenta ser una solucion enterprise: es un punto de partida practico, facil de leer y facil de modificar.

## Requisitos

- PHP 8.2 o superior.
- Composer.
- Extension `mysqli` si vas a usar la capa de base de datos.
- Servidor local con el servidor integrado de PHP (`php -S`).

## Instalacion

```bash
git clone https://github.com/justfernandomalpica/mpk-project.git mi-proyecto
cd mi-proyecto
composer install
composer dump-autoload
```

Si necesitas variables de entorno locales:

```bash
cp .env.example .env
```

En Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

## Ejecucion Local

El servidor debe levantarse desde la carpeta `public/`:

```bash
cd public
php -S localhost:8000 router.php
```

Despues abre `http://localhost:8000` en el navegador. La ruta demo muestra una pagina simple y `http://localhost:8000/api/health` devuelve una respuesta JSON de health check.

## Estructura

- `src/`: codigo de aplicacion y piezas base bajo el namespace `App\`.
- `src/Controllers/`: controladores HTTP.
- `src/Middleware/`: middleware opcional para rutas.
- `src/Models/`: modelos de dominio o modelos ActiveRecord.
- `src/Services/`: servicios de aplicacion.
- `src/Routing/`: router y definicion de rutas.
- `src/Http/`: helpers de respuesta HTTP.
- `src/Database/`: conexion y ActiveRecord ligero.
- `src/Support/`: utilidades compartidas como el logger.
- `config/`: bootstrap, constantes, helpers, fecha/hora y configuracion de base de datos.
- `public/`: document root del servidor web.
- `public/assets/`: CSS, JS e imagenes publicas.
- `routes/`: definicion de rutas de la aplicacion.
- `storage/`: logs, cache y archivos temporales generados en runtime.
- `views/`: vistas HTML organizadas en `layouts/`, `pages/` y `partials/`.
- `vendor/`: dependencias instaladas por Composer.

## Flujo De Una Peticion

1. `public/router.php` deja pasar archivos estaticos y envia las demas peticiones a `public/index.php`.
2. `public/index.php` carga `config/app.php`.
3. `config/app.php` registra constantes, Composer, helpers, variables `.env`, fecha/hora, logger, base de datos opcional y crea el `$router`.
4. `public/index.php` carga `routes/web.php`.
5. El router busca una ruta compatible con el metodo HTTP y la URI.
6. El controlador ejecuta la accion correspondiente.
7. La accion responde con HTML, JSON o usando `App\Http\Response`.

## Crear Una Ruta

Edita `routes/web.php`:

```php
use App\Controllers\PageController;

$router->get('/about', [PageController::class, 'about']);
$router->post('/contact', [PageController::class, 'contact']);
```

Las rutas pueden tener parametros:

```php
$router->get('/users/{id}', [UserController::class, 'show'])
    ->where('id', '\d+');
```

Los parametros llegan al controlador como arreglo:

```php
public static function show(array $params): void
{
    Response::json(['id' => $params['id']], JSON_SUCCESS);
}
```

## Crear Un Controlador

Crea un archivo en `src/Controllers`, por ejemplo `PageController.php`:

```php
<?php declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;

class PageController
{
    public static function about(): void
    {
        Response::html('<h1>About</h1>');
    }
}
```

## Renderizar Vistas

El scaffold incluye un render simple para HTML con layouts, paginas y partials.

Estructura esperada:

- `views/layouts/`: layouts base.
- `views/pages/`: vistas de pagina.
- `views/partials/`: piezas reutilizables sin layout.

Ejemplo de controlador:

```php
<?php declare(strict_types=1);

namespace App\Controllers;

use App\Http\Response;
use App\Rendering\Partial;
use App\Rendering\Render;
use App\Rendering\View;

class PageController
{
    public static function home(): void
    {
        $status = Render::partial(
            (new Partial('status'))->data([
                'message' => 'Render activo',
            ])
        );

        $view = (new View('home'))->data([
            'title' => 'Inicio',
            'status' => $status,
        ]);

        Response::html(Render::view(layout: 'main', view: $view));
    }
}
```

En una vista, los datos de `View` estan disponibles con prefijo `$data_`:

```php
<h1><?= s($data_title) ?></h1>
<?= $data_status ?>
```

En un partial, los datos de `Partial` estan disponibles con prefijo `$data_`:

```php
<span><?= s($data_message) ?></span>
```

El layout recibe el HTML de la pagina en `$content`:

```php
<main>
    <?= $content ?>
</main>
```

## Crear Un Modelo Con ActiveRecord

Activa la base de datos en `.env`:

```env
APP_USE_DATABASE=true
```

Define las credenciales `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` y `DB_PASS`.

Ejemplo de modelo:

```php
<?php declare(strict_types=1);

namespace App\Models;

use App\Database\ActiveRecord;

class User extends ActiveRecord
{
    protected static string $table = 'users';
    protected static array $columns = ['id', 'name', 'email', 'created_at', 'updated_at'];
    protected static array $columnsToSync = ['name', 'email'];

    protected ?int $id = null;
    public ?string $name = null;
    public ?string $email = null;

    public function name(string $name): void
    {
        $this->name = trim($name);
    }

    public function email(string $email): void
    {
        $this->email = trim($email);
    }
}
```

Uso basico:

```php
$user = (new User())->sync([
    'name' => 'Ada',
    'email' => 'ada@example.com',
]);

$user->save();
```

## Variables De Entorno

El repositorio incluye `.env.example` como plantilla segura. El archivo `.env` real no debe subirse al repositorio.

Opciones principales:

- `APP_ENV`: entorno actual, por ejemplo `local` o `production`.
- `APP_DEBUG`: bandera para uso de la aplicacion.
- `APP_TIMEZONE`: zona horaria usada por las constantes de fecha.
- `APP_LOG_ENABLED`: habilita logs en `storage/logs`.
- `APP_USE_DATABASE`: carga `config/database.php` y conecta ActiveRecord.
- `APP_CORS_ENABLED`: agrega headers CORS basicos.

## Demo

La ruta `/` usa `App\Rendering\Render` con un layout, una vista y un partial para confirmar que el scaffold arranca correctamente. `/api/health` devuelve JSON. Puedes eliminar `src/Controllers/DemoController.php`, sus vistas demo y sus rutas en `routes/web.php` y `routes/api.php` cuando empieces tu proyecto.

## Notas

Esta plantilla favorece claridad sobre abstraccion. Para proyectos pequenos funciona bien como base directa; para aplicaciones grandes conviene evaluar herramientas mas completas, testing formal, migraciones, validacion robusta y manejo de errores mas avanzado.
