# Task Manager — Менеджер задач

Веб-приложение для управления задачами. Состоит из трёх микросервисов.

## Стек

| Компонент | Технология |
|-----------|-----------|
| Backend (REST API) | PHP 8.2 + Laravel 12 |
| Frontend | Node.js 20 + Next.js 14 |
| Web server | Nginx (Alpine) |
| Database | PostgreSQL 16 |
| Cache | Redis 7 |
| DB Admin (опционально) | pgAdmin 4 |

## Возможности

- JWT-аутентификация (один тестовый пользователь)
- CRUD задач с поиском, фильтрацией по статусу выполнения и пагинацией
- Поле «исполнитель» и признак «выполнено» (переключение чекбоксом)
- Срок выполнения (опционально)
- Валидация входных данных
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
git clone <repo-url> task-manager
cd task-manager

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

Также создаются несколько демо-задач.

## Структура проекта

Корневой `.env` содержит общие настройки проекта, а `docker-compose.yml` отвечает за запуск всех сервисов.

Backend на Laravel предоставляет REST API и работает с базой данных.

Frontend на Next.js отвечает за интерфейс, через который пользователь работает с задачами.

Nginx используется как прокси и принимает запросы от клиента, после чего передаёт их нужному сервису.

## API Endpoints

### Аутентификация

| Метод | Путь | Описание |
|-------|------|----------|
| POST | `/api/login` | Вход: `{email, password}` → `{token, user}` |

Токен передавать в заголовке: `Authorization: Bearer <token>`

### Задачи (требуют JWT)

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/api/tasks` | Список |
| POST | `/api/tasks` | Создать |
| GET | `/api/tasks/{id}` | Получить одну |
| PUT | `/api/tasks/{id}` | Обновить |
| DELETE | `/api/tasks/{id}` | Удалить (мягко) |
| POST | `/api/tasks/{id}/restore` | Восстановить мягко удалённую |
| DELETE | `/api/tasks/{id}/force` | Удалить полностью (безвозвратно) |

Параметры списка: `?search=...&completed=true&page=1&per_page=15`

Поля задачи:

- `title` — название (строка, обязательно)
- `executor` — исполнитель (строка, опционально)
- `due_date` — срок выполнения (дата, опционально)
- `completed` — выполнено/нет (булево, по умолчанию `false`)

## Мягкое удаление

Обычный `DELETE` не удаляет запись физически, а ставит `deleted_at`. Такие записи не возвращаются в списке. Для восстановления и полного удаления используются отдельные endpoint'ы (см. таблицу выше).

## Аудит изменений

Каждое создание, изменение и удаление задачи логируется в таблицу `activity_logs` с фиксацией пользователя, действия и диффа полей (`before`/`after`).

```bash
# Посмотреть журнал аудита
docker compose exec db psql -U sert_user -d sert_manager -c \
  "SELECT id, action, subject_id, user_id, changes FROM activity_logs ORDER BY id DESC LIMIT 10;"
```

## Кеширование

Список задач кешируется в Redis на 60 секунд. При любом изменении (создание/обновление/удаление) кеш сбрасывается автоматически.

```bash
# Посмотреть ключи кеша
docker compose exec redis redis-cli KEYS '*'
```

## Docker Healthcheck

Все сервисы имеют healthcheck — статус виден в колонке STATUS команды `docker compose ps` (`(healthy)`).

```bash
docker compose ps

# Точный статус каждого
docker inspect --format='{{.Name}} → {{.State.Health.Status}}' \
  sert-manager-db sert-manager-redis sert-manager-backend \
  sert-manager-nginx sert-manager-frontend
```

## Тестирование

```bash
# Запустить unit-тесты
docker compose exec backend php artisan test
```

Покрытие: аутентификация, CRUD задач (поиск, фильтр, пагинация, переключение `completed`, soft delete, restore, force delete), аудит.

## CI/CD (GitHub Actions)

Пайплайн определён в `.github/workflows/ci.yml` и запускается на push/pull request в `main`. Включает два job'а:

- `backend-tests` — поднимает PostgreSQL и Redis, прогоняет миграции и `php artisan test`
- `frontend-build` — `npm ci` и `next build`

## Управление

```bash
# Запуск
docker compose up -d --build

# Остановка
docker compose down

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
```
