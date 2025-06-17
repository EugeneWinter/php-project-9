# Анализатор страниц (URL Checker)

[![Actions Status](https://github.com/EugeneWinter/php-project-9/actions/workflows/hexlet-check.yml/badge.svg)](https://github.com/EugeneWinter/php-project-9/actions)
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=EugeneWinter_php-project-9&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=EugeneWinter_php-project-9)
[![Maintainability](https://sonarcloud.io/api/project_badges/measure?project=EugeneWinter_php-project-9&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=EugeneWinter_php-project-9)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=EugeneWinter_php-project-9&metric=coverage)](https://sonarcloud.io/summary/new_code?id=EugeneWinter_php-project-9)

# Описание проекта

Проект представляет собой веб-приложение для анализа веб-страниц. Пользователи могут добавлять URL-адреса сайтов, которые затем проверяются на доступность, а также анализируется их содержимое (заголовки, мета-описания и т.д.).

## Функциональность

- Добавление URL-адресов для мониторинга
- Проверка доступности сайтов
- Анализ содержимого страниц:
  - Код состояния HTTP
  - Заголовок h1
  - Title страницы
  - Мета-описание
- Просмотр истории проверок для каждого сайта

## Технологии

- PHP 8.3
- Slim Framework (микрофреймворк для веб-приложений)
- PostgreSQL (хранение данных)
- GuzzleHTTP (HTTP-клиент для проверки URL)
- Carbon (работа с датами)
- Valitron (валидация данных)
- Bootstrap 5 (интерфейс пользователя)

## Установка и запуск

### Требования

- Docker и Docker Compose
- PHP 8.3 (если запускаете без Docker)
- Composer
- PostgreSQL

### Запуск с помощью Docker (рекомендуемый способ)

1. Клонируйте репозиторий:
   ```bash
   git clone https://github.com/your-repo/url-checker.git
   cd url-checker
   ```

2. Запустите приложение:
   ```bash
   make docker-setup
   ```

Приложение будет доступно по адресу: `http://localhost:8080`

### Запуск без Docker

1. Установите зависимости:
   ```bash
   make install
   ```

2. Запустите встроенный PHP-сервер:
   ```bash
   make start
   ```

## Структура проекта

```
.
├── public/              # Публичные файлы
├── src/                 # Исходный код приложения
├── templates/           # Шаблоны представлений
│   ├── index.phtml      # Главная страница
│   ├── urls/           # Шаблоны для работы с URL
│   │   ├── index.phtml # Список URL
│   │   └── show.phtml  # Детальная страница URL
├── Makefile            # Команды для управления проектом
├── Dockerfile          # Конфигурация Docker
├── docker-compose.yml  # Конфигурация Docker Compose
└── composer.json       # Зависимости PHP
```

## Команды

- `make start` - запуск встроенного PHP-сервера
- `make lint` - проверка кода на соответствие PSR-12
- `make lint-fix` - автоматическое исправление стиля кода
- `make test` - запуск тестов
- `make docker-setup` - сборка и запуск контейнеров Docker