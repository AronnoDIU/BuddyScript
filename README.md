# BuddyScript Full Stack (Symfony + React)

This project was converted from static HTML/CSS/JS into a full-stack app:

- Backend: Symfony 7 API (`backend/`)
- Frontend: React + Vite (`frontend/`)
- Database: MySQL 8
- One-command startup (no Docker): `npm run dev`

## Requirements

- PHP `8.5+`
- Composer `2+`
- Node.js `18+` and npm
- MySQL `8+` running locally

## Environment Setup

1. Copy backend environment file and adjust DB credentials if needed:

```bash
cp backend/.env backend/.env.local
```

2. Ensure `DATABASE_URL` in `backend/.env.local` points to your MySQL instance.

## Run Everything With One Command

From project root:

```bash
npm run dev
```

This single command will:

- Install backend/frontend dependencies if missing
- Run DB migrations
- Start backend on `http://127.0.0.1:8000`
- Start frontend on `http://127.0.0.1:5173`

## Features Implemented

- Authentication and authorization with JWT
- Registration (`firstName`, `lastName`, `email`, `password`)
- Login and protected feed route
- Feed sorted by newest post first
- Create post with text + image upload
- Public/private post visibility
- Post like/unlike
- Comment and reply creation
- Comment/reply like/unlike
- "Who liked" support for post/comment/reply

## API Overview

Public endpoints:

- `POST /api/auth/register`
- `POST /api/login_check`

Protected endpoints (Bearer token):

- `GET /api/me`
- `GET /api/feed`
- `POST /api/posts`
- `POST /api/posts/{id}/likes/toggle`
- `GET /api/posts/{id}/likes`
- `POST /api/posts/{id}/comments`
- `POST /api/comments/{id}/replies`
- `POST /api/comments/{id}/likes/toggle`
- `GET /api/comments/{id}/likes`

## Notes

- Uploaded post images are stored in `backend/public/uploads/posts`.
- Existing BuddyScript design assets are reused from `frontend/public/assets`.
