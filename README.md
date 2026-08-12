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
| DB Admin (опционально) | pgAdmin 4 |

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

Корневой .env содержит общие настройки проекта, а docker-compose.yml отвечает за запуск всех сервисов.

Backend на Laravel предоставляет REST API и работает с базой данных.

Frontend на Next.js отвечает за интерфейс, через который пользователь работает с сертификатами.

Worker на Go работает в фоне и периодически проверяет сертификаты, например на предмет истечения срока действия.

Nginx используется как прокси и принимает запросы от клиента, после чего передаёт их нужному сервису.

## API Endpoints

### Аутентификация

| Метод | Путь | Описание                                     |

| POST | `/api/login` | Вход: `{email, password}` -> `{token, user}` |

Токен передавать в заголовке: `Authorization: Bearer <token>`

### Сертификаты (требуют JWT)

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/api/certificates` | Список |
| POST | `/api/certificates` | Создать |
| GET | `/api/certificates/{id}` | Получить один |
| PUT | `/api/certificates/{id}` | Обновить |
| DELETE | `/api/certificates/{id}` | Удалить |

Параметры списка: `?search=...&status=active&page=1&per_page=15`

Поля сертификата:
- `name` — название (строка, обязательно)
- `price` — стоимость (число > 0, обязательно)
- `expires_at` — срок действия (дата > сегодня, обязательно)
- `status` — статус: `active`, `expired`, `redeemed`

Статусы: `active`, `expired`, `redeemed`

## Фоновый worker

Раз в минуту проверяет все сертификаты со статусом `active` и датой истечения меньше текущей — автоматически меняет статус на `expired`. Результат логируется.

```bash
# Посмотреть логи воркера
docker compose logs -f worker
```

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

PGADMIN_EMAIL=admin@example.com
PGADMIN_PASSWORD=changeme   # Пароль pgAdmin

# Опциональные порты
BACKEND_PORT=6162
FRONTEND_PORT=3000
PGADMIN_PORT=5050
NEXT_PUBLIC_API_URL=http://localhost:6162/api
```
