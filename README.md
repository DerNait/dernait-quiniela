# ⚽ Quiniela del Mundial

App de quinielas para una fiesta: cada partido es su **propia quiniela** con un sistema
de puntajes rico (12 categorías) para minimizar empates, comodín **x2**, marcador **en
vivo** y ranking que se actualiza solo. Mobile-first, liviana (≤50 usuarios), sin
websockets ni Redis.

**Stack:** Laravel 11 (API + tokens Sanctum) · Vue 3 + Vite + Tailwind v4 · MySQL 8 · Docker.

---

## Puesta en marcha (Docker)

```bash
cp .env.example .env            # ya viene configurado para Docker
docker compose up -d --build    # nginx + php-fpm + mysql + vite(dev)
docker compose exec app composer install
docker compose exec app php artisan migrate:fresh --seed
```

- App: **http://localhost:8088**
- Vite (HMR en desarrollo): http://localhost:5173 (lo levanta el servicio `node`)
- MySQL en el host: puerto **33061**

> Si esos puertos están ocupados, cámbialos en `docker-compose.yml` (y `APP_URL` en `.env`).

### Producción (assets compilados, sin el servicio `node`)

```bash
npm install && npm run build    # genera public/build
docker compose up -d nginx app db
```

---

## Cuentas sembradas

| Rol | Correo | Clave |
|-----|--------|-------|
| Admin (anfitrión) | `admin@quiniela.test` | `password` |
| Invitados demo | `ana@quiniela.test`, `beto@…`, `caro@…` | `password` |

Los invitados también pueden **crear su propia cuenta** (email + clave, sin verificación).

---

## Sistema de puntajes

Cada quiniela trae estas categorías (puntos editables desde el panel admin). Mientras
más difícil acertar, más puntos:

| Categoría | Pts | | Categoría | Pts |
|-----------|----:|-|-----------|----:|
| Ganador (1X2) | 3 | | Total de goles exacto | 4 |
| Ambos equipos marcan | 2 | | Marcador exacto medio tiempo | 5 |
| Ganador al medio tiempo | 2 | | Marcador exacto final | 6 |
| Equipo del primer gol | 3 | | **Jugador del primer gol** | **10** |
| Diferencia de goles | 3 | | Tarjeta roja / Penal (Sí/No) | 2 c/u |
| Minuto del 1er gol (cercanía) | 5/3/1 | | | |

- **Comodín x2:** cada participante elige **una** categoría para duplicar sus puntos.
- **Minuto del 1er gol:** exacto = puntos completos, ±2 min = 60 %, ±5 min = 20 %.
- **Desempate final:** a igualdad de puntos gana quien **envió su predicción primero**.

La lógica vive en `app/Services/ScoringService.php` (motor puro, sin DB) y está cubierta
por tests unitarios.

---

## Cómo operar el día de la fiesta (admin)

1. Entra como admin → **Panel admin** (botón arriba a la derecha).
2. Cada partido empieza **Abierto**: la gente predice hasta la hora de inicio.
3. Al arrancar el partido, cambia el estado a **En vivo** (cierra las predicciones).
4. Registra cada jugada con **Registrar jugada** (gol + goleador + minuto + tiempo,
   tarjeta roja, penal). El ranking de todos se recalcula al instante.
5. Al terminar, pon **Final**.
6. **📺 Modo TV** (`/tv/:id`): vista a pantalla completa con marcador + ranking, ideal
   para castear a una tele. Se refresca sola.

El marcador se entra **a mano** (confiable y gratis). Si configuras `API_FOOTBALL_KEY`
y el `fixture id` de la quiniela, el botón **Sincronizar marcador** trae el marcador
desde API-Football (el goleador se asigna a mano de todos modos).

---

## Desarrollo

```bash
docker compose exec app php artisan test     # backend (PHPUnit)
npm run build                                 # compila el frontend
```

Estructura clave:

- `app/Enums/ScoringCategory.php` — categorías, etiquetas y puntos por defecto.
- `app/Services/` — `ScoringService` (puntaje), `ResultService` (resultado desde
  eventos en vivo), `LeaderboardService` (ranking + recálculo), `ApiFootballService`.
- `app/Http/Controllers/` — público (`Quiniela`, `Prediction`, `Auth`) y `Admin/`.
- `routes/api.php` — toda la API (auth → participante → `admin` con gate `can:admin`).
- `resources/js/` — SPA Vue (vistas, componentes, stores Pinia, router).

Polling en vivo: el frontend consulta `/live` y `/leaderboard` cada ~8–9 s; el backend
cachea el ranking 10 s.
