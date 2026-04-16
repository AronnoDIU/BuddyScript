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
- Messenger conversations with near real-time updates, attachments, and read states
- **Phase 4 Community Features:**
  - Groups with role-based permissions (admin, moderator, member)
  - Pages with admin/editor/member roles
  - Events with organizer/coorganizer/speaker/attendee roles
  - Group posts with comments and likes
  - Pages screen (`/pages`) with create page + page posting workflow
  - Events screen (`/events`) with create event + attendance + event posting workflow
  - Community moderation and member management
  - Group visibility settings (public, private, secret)
- **Phase 5 Commerce/Safety Features:**
  - Marketplace listing creation, browsing, search, and seller management
  - Listing lifecycle controls (active/sold/archived/delete)
  - Trust & safety reporting for abusive users/content/listings
  - User blocking and unblock management
  - Privacy checkup recommendations + editable privacy controls
  - End-to-end TOTP 2FA setup/confirm/disable + login challenge verification

## API Overview

### Public endpoints

- `POST /api/v1/register`
- `POST /api/auth/login_check`
- `POST /api/v1/2fa/verify`
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
- `POST /api/v1/reactions/summaries`
- `GET /api/v1/reactions?targetType={post|comment|reply}&targetId={uuid}`
- `GET /api/v1/notifications`
- `POST /api/v1/notifications/{id}/read`
- `POST /api/v1/notifications/read-all`
- `GET /api/v1/messenger/conversations`
- `GET /api/v1/messenger/conversations/{id}/messages`
- `POST /api/v1/messenger/messages`
- `POST /api/v1/messenger/stream-token`
- `POST /api/v1/messenger/conversations/{id}/read`
- `POST /api/v1/messenger/conversations/{id}/pin`
- `POST /api/v1/messenger/conversations/{id}/mute`
- `POST /api/v1/messenger/conversations/{id}/archive`
- `GET /api/v1/messenger/updates`
- `GET /api/v1/messenger/stream`
- `GET /api/v1/marketplace/listings`
- `POST /api/v1/marketplace/listings`
- `GET /api/v1/marketplace/listings/{id}`
- `PUT /api/v1/marketplace/listings/{id}`
- `DELETE /api/v1/marketplace/listings/{id}`
- `POST /api/v1/marketplace/listings/{id}/mark-sold`
- `GET /api/v1/marketplace/my/listings`
- `POST /api/v1/safety/reports`
- `GET /api/v1/safety/reports/me`
- `POST /api/v1/safety/blocks/{userId}`
- `DELETE /api/v1/safety/blocks/{userId}`
- `GET /api/v1/safety/blocks`
- `GET /api/v1/privacy-checkup`
- `PUT /api/v1/privacy-checkup`
- `GET /api/v1/2fa/status`
- `POST /api/v1/2fa/setup/init`
- `POST /api/v1/2fa/setup/confirm`
- `POST /api/v1/2fa/disable`

### Community endpoints (Groups)

- `POST /api/v1/groups` - Create a new group
- `GET /api/v1/groups` - List user's groups (with search)
- `GET /api/v1/groups/public` - List public groups
- `GET /api/v1/groups/{id}` - Get group details
- `PUT /api/v1/groups/{id}` - Update group (admin only)
- `POST /api/v1/groups/{id}/join` - Join a group
- `POST /api/v1/groups/{id}/leave` - Leave a group
- `GET /api/v1/groups/{id}/members` - List group members
- `PUT /api/v1/groups/{id}/members/{userId}` - Update member role (admin only)
- `DELETE /api/v1/groups/{id}/members/{userId}` - Remove member (admin only)

### Community endpoints (Pages)

- `POST /api/v1/pages` - Create a new page
- `GET /api/v1/pages` - List user's pages (with search)
- `GET /api/v1/pages/public` - List public pages
- `GET /api/v1/pages/{id}` - Get page details
- `POST /api/v1/pages/{id}/follow` - Follow a page
- `POST /api/v1/pages/{id}/unfollow` - Unfollow a page
- `GET /api/v1/pages/{id}/members` - List page members
- `PUT /api/v1/pages/{id}/members/{userId}` - Update member role (admin only)
- `DELETE /api/v1/pages/{id}/members/{userId}` - Remove member (admin only)
- `GET /api/v1/pages/{id}/posts` - List page posts
- `POST /api/v1/pages/{id}/posts` - Create page post

### Community endpoints (Events)

- `POST /api/v1/events` - Create a new event
- `GET /api/v1/events` - List user's events (with search)
- `GET /api/v1/events/public` - List public upcoming events
- `GET /api/v1/events/{id}` - Get event details
- `POST /api/v1/events/{id}/join` - Join an event
- `POST /api/v1/events/{id}/leave` - Leave an event
- `GET /api/v1/events/{id}/members` - List event members
- `PUT /api/v1/events/{id}/members/{userId}` - Update member role (organizer only)
- `DELETE /api/v1/events/{id}/members/{userId}` - Remove member (organizer only)
- `GET /api/v1/events/{id}/posts` - List event posts
- `POST /api/v1/events/{id}/posts` - Create event post

### Group Posts endpoints

- `POST /api/v1/groups/{id}/posts` - Create group post
- `GET /api/v1/groups/{id}/posts` - List group posts (with search)
- `GET /api/v1/group-posts/{id}` - Get group post details
- `DELETE /api/v1/group-posts/{id}` - Delete your own group post
- `POST /api/v1/group-posts/{id}/likes/toggle` - Toggle like on group post
- `POST /api/v1/group-posts/{id}/comments` - Add comment to group post
- `POST /api/v1/group-post-comments/{id}/likes/toggle` - Toggle like on comment

### Pages endpoints (frontend integration)

- Frontend now calls `GET/POST /api/v1/pages`, `GET /api/v1/pages/public`, `POST /api/v1/pages/{id}/follow`, `POST /api/v1/pages/{id}/unfollow`, `GET/POST /api/v1/pages/{id}/posts`, and the page member management endpoints above.
- `frontend/src/api/pages.js` still keeps a local browser-storage fallback for offline/dev scenarios.

### Events endpoints (frontend integration)

- Frontend now calls `GET/POST /api/v1/events`, `GET /api/v1/events/public`, `POST /api/v1/events/{id}/join`, `POST /api/v1/events/{id}/leave`, and `GET/POST /api/v1/events/{id}/posts`.
- `frontend/src/api/events.js` keeps a local browser-storage fallback for offline/dev scenarios.

### Feed posts endpoints

- `POST /api/v1/posts` - Create post
- `DELETE /api/v1/posts/{id}` - Delete your own post

### Marketplace endpoints

- `GET /api/v1/marketplace/listings` - Browse listings with optional search/category
- `POST /api/v1/marketplace/listings` - Create listing
- `GET /api/v1/marketplace/listings/{id}` - Get listing details
- `PUT /api/v1/marketplace/listings/{id}` - Update own listing
- `DELETE /api/v1/marketplace/listings/{id}` - Delete own listing
- `POST /api/v1/marketplace/listings/{id}/mark-sold` - Mark own listing as sold
- `GET /api/v1/marketplace/my/listings` - List your listings

### Trust & Safety endpoints

- `POST /api/v1/safety/reports` - Submit trust/safety report
- `GET /api/v1/safety/reports/me` - List your submitted reports
- `POST /api/v1/safety/blocks/{userId}` - Block user
- `DELETE /api/v1/safety/blocks/{userId}` - Unblock user
- `GET /api/v1/safety/blocks` - List blocked users

### Privacy Checkup endpoints

- `GET /api/v1/privacy-checkup` - Get privacy checklist + recommendations
- `PUT /api/v1/privacy-checkup` - Update privacy settings

### Two-Factor Authentication endpoints

- `GET /api/v1/2fa/status` - Current 2FA status for signed-in user
- `POST /api/v1/2fa/setup/init` - Start authenticator setup and return secret/otpauth URI
- `POST /api/v1/2fa/setup/confirm` - Confirm setup with 6-digit TOTP code
- `POST /api/v1/2fa/disable` - Disable 2FA with current TOTP code
- `POST /api/v1/2fa/verify` - Complete login challenge for 2FA-enabled accounts

## Operational Notes

- Uploaded post images are stored in `backend/public/uploads/posts`.
- Marketplace listing images are stored in `backend/public/uploads/marketplace`.
- Group avatars are stored in `backend/public/uploads/groups`.
- Page avatars are stored in `backend/public/uploads/pages`.
- Group post images are stored in `backend/public/uploads/group-posts`.
- Page post images are stored in `backend/public/uploads/page-posts`.
- Event avatars are stored in `backend/public/uploads/events`.
- Event post images are stored in `backend/public/uploads/event-posts`.
- Existing BuddyScript design assets are reused from `frontend/public/assets`.
- Access JWTs are auto-refreshed via HttpOnly refresh-token cookies when the API responds with `401 Expired JWT Token`.

## Troubleshooting

- If migrations fail with `could not find driver`, install the PHP MySQL extension for your CLI version (for example, `php8.5-mysql` on Debian/Ubuntu).
- If backend requests fail with `Undefined constant "XML_PI_NODE"`, install the PHP XML extension for your CLI version (for example, `php8.5-xml` on Debian/Ubuntu).

