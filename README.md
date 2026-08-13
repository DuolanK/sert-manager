# Sert Manager — Certificate Management System

Веб-приложение для управления подарочными сертификатами. Состоит из трёх микросервисов.

## Стек

| Компонент | Технология |
|-----------|-----------|
| Backend (REST API) | PHP 8.2 + Laravel 12 |
| Frontend | Node.js 20 + Next.js 14 |
| Worker (фон) | Go 1.21 |
| Web server | Nginx (Alpine) |
| Database | PostgreSQL 16 |
| Cache | Redis 7 |
| DB Admin (опционально) | pgAdmin 4 |

## Возможности

- JWT-аутентификация (один тестовый пользователь)
- CRUD сертификатов с поиском, фильтрацией по статусу и пагинацией
- Валидация входных данных
- Фоновый worker: авто-перевод просроченных сертификатов в `expired`
- Мягкое удаление (soft delete) с восстановлением и полным удалением
- Аудит изменений (логирование create/update/delete с диффом)
- Кеширование списка через Redis
- Docker Healthcheck для всех сервисов
- Unit-тесты (PHPUnit)
- CI-пайплайн (GitHub Actions)

## Требования

- Docker и Docker Compose v2
- Свободные порты: `6162` (API), `3000` (Frontend), `5050` (pgAdmin)

## Быстрый старт

```bash
# 1. Клонировать репозиторий
git clone <repo-url> sert-manager
cd sert-manager

# 2. Создать .env из примера
cp .env.example .env
# Отредактировать .env — сменить пароли и JWT_SECRET

# 3. Запустить все сервисы
docker compose up -d --build

# 4. Готово
# Frontend:  http://localhost:3000
# API:       http://localhost:6162/api
# pgAdmin:   http://localhost:5050
```

## Тестовый пользователь

После запуска автоматически создаётся тестовый пользователь:

- Email: `test@example.com`
- Password: `password`

## Структура проекта

Корневой `.env` содержит общие настройки проекта, а `docker-compose.yml` отвечает за запуск всех сервисов.

Backend на Laravel предоставляет REST API и работает с базой данных.

Frontend на Next.js отвечает за интерфейс, через который пользователь работает с сертификатами.

Worker на Go работает в фоне и периодически проверяет сертификаты на предмет истечения срока действия.

Nginx используется как прокси и принимает запросы от клиента, после чего передаёт их нужному сервису.

## API Endpoints

### Аутентификация

| Метод | Путь | Описание |
|-------|------|----------|
| POST | `/api/login` | Вход: `{email, password}` → `{token, user}` |

Токен передавать в заголовке: `Authorization: Bearer <token>`

### Сертификаты (требуют JWT)

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/api/certificates` | Список |
| POST | `/api/certificates` | Создать |
| GET | `/api/certificates/{id}` | Получить один |
| PUT | `/api/certificates/{id}` | Обновить |
| DELETE | `/api/certificates/{id}` | Удалить (мягко) |
| POST | `/api/certificates/{id}/restore` | Восстановить мягко удалённый |
| DELETE | `/api/certificates/{id}/force` | Удалить полностью (безвозвратно) |

Параметры списка: `?search=...&status=active&page=1&per_page=15`

Поля сертификата:
- `name` — название (строка, обязательно)
- `price` — стоимость (число > 0, обязательно)
- `expires_at` — срок действия (дата > сегодня, обязательно)
- `status` — статус: `active`, `expired`, `redeemed`

Статусы: `active`, `expired`, `redeemed`

## Мягкое удаление

Обычный `DELETE` не удаляет запись физически, а ставит `deleted_at`. Такие записи не возвращаются в списке. Для восстановления и полного удаления используются отдельные endpoint'ы (см. таблицу выше).

## Аудит изменений

Каждое создание, изменение и удаление сертификата логируется в таблицу `activity_logs` с фиксацией пользователя, действия и диффа полей (`before`/`after`).

```bash
# Посмотреть журнал аудита
docker compose exec db psql -U sert_user -d sert_manager -c \
  "SELECT id, action, subject_id, user_id, changes FROM activity_logs ORDER BY id DESC LIMIT 10;"
```

## Кеширование

Список сертификатов кешируется в Redis на 60 секунд. При любом изменении (создание/обновление/удаление) кеш сбрасывается автоматически.

```bash
# Посмотреть ключи кеша
docker compose exec redis redis-cli KEYS '*'
```

## Фоновый worker

Раз в минуту проверяет все сертификаты со статусом `active` и датой истечения меньше текущей — автоматически меняет статус на `expired`. Результат логируется.

```bash
# Посмотреть логи воркера
docker compose logs -f worker
```

## Docker Healthcheck

Все сервисы имеют healthcheck — статус виден в колонке STATUS команды `docker compose ps` (`(healthy)`).

```bash
docker compose ps

# Точный статус каждого
docker inspect --format='{{.Name}} → {{.State.Health.Status}}' \
  sert-manager-db sert-manager-redis sert-manager-backend \
  sert-manager-nginx sert-manager-frontend sert-manager-worker
```

## Тестирование

```bash
# Запустить unit-тесты
docker compose exec backend php artisan test
```

Покрытие: аутентификация, CRUD сертификатов (поиск, фильтр, пагинация, soft delete, restore, force delete), аудит.

## CI/CD (GitHub Actions)

Пайплайн определён в `.github/workflows/ci.yml` и запускается на push/pull request в `main`. Включает три job'а:

- `backend-tests` — поднимает PostgreSQL и Redis, прогоняет миграции и `php artisan test`
- `worker-build` — собирает Go-бинарник
- `frontend-build` — `npm ci` и `next build`

## Управление

```bash
# Запуск
docker compose up -d --build

# Остановка
docker compose down

# Перезапуск одного сервиса
docker compose restart worker

# Логи конкретного сервиса
docker compose logs -f backend
docker compose logs -f frontend

# Полный сброс (удалит все данные БД)
docker compose down -v
```

## Переменные окружения

Все учётные данные вынесены в корневой `.env`:

```ini
DB_HOST=db                  # Хост БД (внутри Docker — db)
DB_PORT=5432
DB_DATABASE=sert_manager
DB_USERNAME=sert_user
DB_PASSWORD=changeme        # Пароль БД

JWT_SECRET=your-secret      # Секрет для JWT-токенов
APP_KEY=base64:...          # Ключ шифрования Laravel
CACHE_STORE=redis           # Кеш-хранилище
REDIS_HOST=redis            # Хост Redis (внутри Docker — redis)

PGADMIN_EMAIL=admin@example.com
PGADMIN_PASSWORD=changeme   # Пароль pgAdmin

# Опциональные порты
BACKEND_PORT=6162
FRONTEND_PORT=3000
PGADMIN_PORT=5050
NEXT_PUBLIC_API_URL=http://localhost:6162/api
```
