# Tattoo Swap API

API REST desarrollada en Laravel 13 con autenticación mediante Laravel Passport, sistema de roles, y un cliente HTML/CSS/JS que consume la API. Proyecto académico que reutiliza y evoluciona el dominio de la aplicación **Tattoo Swap** (una plataforma de intercambio de estudio/vivienda entre tatuadores) hacia una arquitectura de API REST con testing automatizado.

## Índice

- [Descripción del dominio](#descripción-del-dominio)
- [Stack tecnológico](#stack-tecnológico)
- [Requisitos previos](#requisitos-previos)
- [Instalación](#instalación)
- [Roles y autorización](#roles-y-autorización)
- [Lógica de negocio destacada](#lógica-de-negocio-destacada)
- [Endpoints de la API](#endpoints-de-la-api)
- [Testing](#testing)
- [Cliente front-end](#cliente-front-end)
- [Estructura del proyecto](#estructura-del-proyecto)

## Descripción del dominio

Tattoo Swap conecta tatuadores que quieren viajar: cada artista puede ofrecer su estudio y su disponibilidad de fechas para intercambiar temporalmente con otro artista en otra ciudad. La aplicación gestiona perfiles, un sistema de "explorar y dar like" similar a otras apps de matching, y el flujo completo de un **swap** (intercambio), incluyendo el cálculo automático de fechas en común entre dos artistas.

## Stack tecnológico

- **Backend:** Laravel 13 (PHP 8.5)
- **Autenticación:** Laravel Passport (OAuth2)
- **Base de datos:** SQLite (desarrollo y testing)
- **Testing:** PHPUnit, con enfoque TDD (test-first)
- **Cliente:** HTML5 + CSS3 + JavaScript vanilla (sin frameworks ni build step), consumiendo la API vía `fetch`

## Requisitos previos

- PHP >= 8.2
- Composer
- Extensión `pdo_sqlite` habilitada (viene con la mayoría de instalaciones de PHP)

## Instalación

```bash
# Clonar el repositorio
git clone https://github.com/hricheri/Laravel_API_REST.git
cd Laravel_API_REST

# Instalar dependencias
composer install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Crear la base de datos SQLite (si no existe)
touch database/database.sqlite

# Ejecutar migraciones
php artisan migrate

# Instalar Passport (genera claves de encriptación y cliente OAuth)
php artisan passport:client --personal --name="Tattoo Swap Personal Client" --no-interaction

# Levantar el servidor
php artisan serve
```

La API queda disponible en `http://127.0.0.1:8000/api`, y el cliente web en `http://127.0.0.1:8000/client/login.html`.

## Roles y autorización

El sistema define dos roles sobre el modelo `User`, con una capa adicional de verificación sobre `Artist`:

| Rol | Descripción |
|---|---|
| **`artist`** | Rol por defecto al registrarse. Puede gestionar su propio perfil y disponibilidad, y explorar otros artistas. |
| **`admin`** | Puede ver el listado completo de artistas, verificarlos, y ver todos los swaps de la plataforma. |

Además, cada `Artist` tiene un flag `is_verified` (booleano, gestionado por un admin) que condiciona el acceso a ciertas acciones:

| Acción | Artist no verificado | Artist verificado |
|---|---|---|
| Ver/editar su propio perfil | ✅ | ✅ |
| Marcar/borrar su propia disponibilidad | ✅ | ✅ |
| Explorar artistas (`GET /api/artists`) | ✅ | ✅ |
| Ver la disponibilidad de **otro** artista | ❌ | ✅ |
| Dar like a otro artista | ❌ | ✅ |
| Crear un swap | ❌ | ✅ |

La autorización se implementa con dos middlewares personalizados:
- `role:{rol}` — restringe el acceso según el rol del usuario (`app/Http/Middleware/EnsureUserHasRole.php`)
- `verified.artist` — exige que el artist autenticado esté verificado (`app/Http/Middleware/EnsureArtistIsVerified.php`)

Todas las rutas protegidas requieren un token Bearer válido emitido por Passport (middleware `auth:api`).

## Lógica de negocio destacada

Más allá del CRUD estándar, el sistema implementa una regla de cálculo real al crear un **swap**:

1. Dos artistas solo pueden iniciar un swap si existe **match mutuo** (ambos se dieron like entre sí).
2. Al crear el swap, el sistema calcula automáticamente la **intersección de fechas disponibles** entre ambos artistas (comparando sus registros de `Availability`), y guarda el primer y último día de esa intersección como `start_date`/`end_date`.
3. Si no hay fechas en común, el swap se crea igual pero con fechas nulas.
4. El swap requiere **doble confirmación** (cada artista debe confirmar por separado); solo pasa a `confirmed` cuando ambos lo hicieron.
5. Un swap `pending` puede rechazarse (`rejected`); un swap `confirmed` puede cancelarse (`cancelled`) — la misma acción de "rechazar" produce un resultado distinto según el estado actual.

Esta lógica vive en `app/Models/Swap.php` (método `calculateOverlap`) y está cubierta por tests dedicados en `tests/Feature/Swaps/`.

## Endpoints de la API

### Autenticación (público)

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/api/register` | Crea un `User` + `Artist`, devuelve token |
| POST | `/api/login` | Devuelve token |
| POST | `/api/logout` | Revoca el token actual (requiere auth) |

### Perfil y artistas

| Método | Endpoint | Descripción | Acceso |
|---|---|---|---|
| GET | `/api/me` | Mi perfil completo | Autenticado |
| PUT | `/api/me` | Actualizar mi perfil (nombre, bio, ciudad, foto) | Autenticado |
| GET | `/api/artists` | Explorar artistas (con filtros) o listado completo | Autenticado (comportamiento según rol) |
| GET | `/api/artists?filter=city&city=X` | Filtrar por ciudad | Autenticado |
| PUT | `/api/artists/{artist}` | Verificar un artista | Admin |

### Disponibilidad

| Método | Endpoint | Descripción | Acceso |
|---|---|---|---|
| GET | `/api/artists/{artist}/availabilities` | Ver disponibilidad de un artista | Propia: siempre / Ajena: verificado |
| POST | `/api/artists/{artist}/availabilities` | Marcar día(s) (`date` o `dates[]`) | Dueño del perfil |
| DELETE | `/api/artists/{artist}/availabilities` | Borrar día(s) (`date` o `dates[]`) | Dueño del perfil |

### Favoritos y matching

| Método | Endpoint | Descripción | Acceso |
|---|---|---|---|
| GET | `/api/favorites` | Mis likes dados, con estado de match | Autenticado |
| POST | `/api/likes` | Dar like a un artista | Verificado |

### Swaps

| Método | Endpoint | Descripción | Acceso |
|---|---|---|---|
| GET | `/api/swaps` | Mis swaps (artist) / todos (admin) | Autenticado |
| POST | `/api/swaps` | Iniciar swap (requiere match mutuo) | Verificado |
| PUT | `/api/swaps/{swap}` | Confirmar fechas (doble confirmación) | Participante del swap |
| DELETE | `/api/swaps/{swap}/reject` | Rechazar (pendiente) o cancelar (confirmado) | Participante del swap |

## Testing

El proyecto sigue un enfoque **TDD estricto**: cada endpoint fue implementado escribiendo primero el test (rojo), y luego el código mínimo necesario para hacerlo pasar (verde). Los tests usan PHPUnit puro (clases con `extends TestCase`), sobre una base SQLite que se resetea en cada test (`RefreshDatabase`).

```bash
# Correr toda la suite
php artisan test

# Correr un archivo específico
php artisan test --filter=RegisterTest
```

La suite cubre casos de éxito, validaciones, reglas de autorización por rol/verificación, y comportamiento de la lógica de negocio (cálculo de intersección de fechas, doble confirmación, transiciones de estado del swap).

## Cliente front-end

Cliente estático en `public/client/`, servido directamente por Laravel (sin build step), que consume la API vía `fetch`. Guarda el token de Passport en `localStorage` tras el login/registro.

| Página | Descripción |
|---|---|
| `login.html` | Inicio de sesión |
| `register.html` | Registro (con bio, ciudad y foto de perfil opcionales) |
| `profile.html` | Ver y editar el perfil propio |
| `explore.html` | Explorar artistas, filtrar por ciudad, dar like |
| `favorites.html` | Ver likes dados, con indicador de match mutuo, iniciar swap |
| `swaps.html` | Ver swaps propios, confirmar o rechazar/cancelar |
| `availability.html` | Calendario para marcar disponibilidad por rango de fechas o días sueltos |

Con el servidor corriendo (`php artisan serve`), el cliente es accesible en `http://127.0.0.1:8000/client/login.html`.

## Estructura del proyecto

app/
Http/
Controllers/Api/ → AuthController, ArtistController, AvailabilityController, LikeController, SwapController
Middleware/ → EnsureUserHasRole, EnsureArtistIsVerified
Models/ → User, Artist, Availability, Like, Swap
database/
factories/ → factories para tests (ArtistFactory, AvailabilityFactory, LikeFactory, SwapFactory)
migrations/
public/
client/ → cliente HTML/CSS/JS
routes/
api.php → definición de todos los endpoints
tests/
Feature/
Auth/ → register, login, logout
Artist/ → perfil propio, explore
Admin/ → listado y verificación de artistas
Availability/ → marcar, borrar, ver disponibilidad
Likes/ → dar like, listar favoritos
Swaps/ → crear, confirmar, rechazar/cancelar, listar


## Autor

Proyecto desarrollado como trabajo académico, evolucionando el dominio de Tattoo Swap hacia una API REST con Passport, roles, TDD y cliente propio.
