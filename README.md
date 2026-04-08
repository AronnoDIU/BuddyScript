# BuddyScript Full Stack (Symfony + React)

BuddyScript is a full-stack social feed application built from the original static UI and upgraded into a production-style architecture.

- **Backend:** Symfony 7 API (`backend/`)
- **Frontend:** React + Vite (`frontend/`)
- **Database:** MySQL 8
- **Unified local startup:** `npm run dev`

## Tech Stack

- PHP `8.5+`
- Composer `2+`
- Node.js `18+` and npm
- MySQL `8+`
- JWT authentication (LexikJWTAuthenticationBundle)

## Prerequisites

Before starting, ensure the PHP CLI binary you use has these extensions enabled:

- `pdo_mysql` (required for Doctrine + MySQL)
- `xml` (required by Symfony serializer components)

Verification commands:

```bash
php -m | grep pdo_mysql
php -r 'var_dump(defined("XML_PI_NODE"));'
```

## Formal Setup Guide (After Cloning)

From the project root, complete the steps below in order.

### 1) Create local environment file

```bash
cp backend/.env backend/.env.local
```

Then update `backend/.env.local` with your database credentials (`username`, `password`, host, port, and database name) in `DATABASE_URL`.

### 2) Create the database

Create the database configured in `DATABASE_URL`.

Example (MySQL):

```bash
cd backend
php bin/console doctrine:database:create
cd ..
```

### 3) Install backend dependencies

```bash
cd backend
composer install
cd ..
```

### 4) Generate JWT key pair

```bash
cd backend
php bin/console lexik:jwt:generate-keypair
cd ..
```

### 5) Run the full project with one command

```bash
npm run dev
```

This command orchestrates the local stack and will:

- install missing backend/frontend dependencies
- run database migrations
- start backend at `http://127.0.0.1:8000`
- start frontend at `http://127.0.0.1:5173`

## Core Features

- JWT-based authentication and authorization
- User registration (`firstName`, `lastName`, `email`, `password`)
- Login and protected feed access
- Feed ordered by newest posts first
- Post creation with text and optional image upload
- Public/private post visibility
- Post like/unlike
- Comment and reply creation
- Comment/reply like/unlike
- "Who liked" support for posts, comments, and replies
- Profile page with visibility-aware stats and recent timeline posts

## API Overview

### Public endpoints

- `POST /api/v1/register`
- `POST /api/auth/login_check`
- `POST /api/v1/refresh`
- `POST /api/v1/logout`

### Protected endpoints (Bearer token)

- `GET /api/v1/me`
- `GET /api/v1/feed`
- `POST /api/v1/posts`
- `POST /api/v1/posts/{id}/likes/toggle`
- `GET /api/v1/posts/{id}/likes`
- `POST /api/v1/posts/{id}/comments`
- `POST /api/v1/comments/{id}/replies`
- `POST /api/v1/comments/{id}/likes/toggle`
- `GET /api/v1/comments/{id}/likes`
- `GET /api/v1/profiles/{id}`
- `GET /api/v1/social/overview`
- `POST /api/v1/social/requests`
- `POST /api/v1/social/requests/{id}/respond`
- `GET /api/v1/reactions/catalog`
- `POST /api/v1/reactions/toggle`
- `GET /api/v1/reactions?targetType={post|comment|reply}&targetId={uuid}`
- `GET /api/v1/notifications`
- `POST /api/v1/notifications/{id}/read`
- `POST /api/v1/notifications/read-all`

## Operational Notes

- Uploaded post images are stored in `backend/public/uploads/posts`.
- Existing BuddyScript design assets are reused from `frontend/public/assets`.
- Access JWTs are auto-refreshed via HttpOnly refresh-token cookies when the API responds with `401 Expired JWT Token`.

## Troubleshooting

- If migrations fail with `could not find driver`, install the PHP MySQL extension for your CLI version (for example, `php8.5-mysql` on Debian/Ubuntu).
- If backend requests fail with `Undefined constant "XML_PI_NODE"`, install the PHP XML extension for your CLI version (for example, `php8.5-xml` on Debian/Ubuntu).

