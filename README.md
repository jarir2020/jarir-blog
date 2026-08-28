# Jarir Blog

A Laravel 11 + Vue 3 (SPA) + Tailwind blog boilerplate. Backend is a JSON API
backed by Eloquent; the frontend is a Vue Router SPA served from a single
Blade view and bundled by Vite.

## Stack

- **PHP 8.2+** / **Laravel 11**
- **Vue 3** + **Vue Router 4** + **Vite 5**
- **Tailwind CSS 3** with the Forms plugin
- **Breeze** for registration / login / password reset / profile
- **MySQL** for local dev (SQLite works too — see [Configuration](#configuration))

## Quick start

```bash
# Backend
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed       # optional: seed demo posts, see credentials.txt
php artisan storage:link  # required for uploaded images

# Frontend
npm install
npm run dev        # or: npm run build for production
```

Then visit `http://localhost:8000`.

A demo admin account is created by the seeder — see [credentials.txt](credentials.txt).

## Configuration

The shipped `.env` uses **MySQL** (`DB_DATABASE=blog`, `DB_USERNAME=root`,
`DB_PASSWORD=root`). If MySQL is not available, switch to SQLite by editing
`.env`:

```env
DB_CONNECTION=sqlite
# DB_HOST=...
# DB_DATABASE=blog
# DB_USERNAME=root
# DB_PASSWORD=root
```

…and creating the file:

```bash
touch database/database.sqlite
```

For tests, `phpunit.xml` already pins `DB_CONNECTION=sqlite` with
`DB_DATABASE=:memory:` so the suite never touches your dev database.

## Domain model

| Table             | Purpose                                                |
| ----------------- | ------------------------------------------------------ |
| `users`           | Authors (Breeze auth)                                  |
| `posts`           | Blog posts; `status`, `is_featured`, `published_at`    |
| `categories`      | Categories with self-referencing `parent_id`           |
| `tags`            | Free-form labels                                       |
| `category_post`   | Pivot: posts ↔ categories                              |
| `post_tag`        | Pivot: posts ↔ tags                                    |
| `cache`, `jobs`   | Laravel defaults                                       |

## API

All endpoints return JSON. Auth is not required for read endpoints.

| Method | Path                                  | Description                          |
| ------ | ------------------------------------- | ------------------------------------ |
| GET    | `/api/posts`                          | Paginated published posts            |
| GET    | `/api/posts/{slug}`                   | Single post + `related` (in payload) |
| GET    | `/api/posts/{slug}/related`           | Related posts (split out, Phase 5)   |
| GET    | `/api/posts/{slug}/comments`          | Approved comments for a post         |
| POST   | `/api/posts/{slug}/comments`          | Submit a comment                     |
| GET    | `/api/categories`                     | All categories with `posts_count`    |
| GET    | `/api/categories/{slug}/posts`        | Paginated posts in a category        |
| GET    | `/api/authors/{username}`             | Author profile                       |
| GET    | `/api/authors/{username}/posts`       | Paginated posts by author            |
| GET    | `/api/search?q=…`                     | Search posts by title/excerpt/body   |
| GET    | `/api/sidebar`                        | Recent / popular / tag cloud         |
| POST   | `/api/subscribe`                      | Newsletter signup                    |
| GET    | `/feed.xml`                           | Atom 1.0 feed of latest posts        |

Admin (`auth` + `admin` middleware):

| Method | Path                                       | Description                       |
| ------ | ------------------------------------------ | --------------------------------- |
| GET    | `/api/admin/me`                            | Current user + `is_admin` flag    |
| GET    | `/api/admin/posts`                         | All posts, including drafts       |
| POST   | `/api/admin/posts`                         | Create a post                     |
| GET    | `/api/admin/posts/{id}`                    | Show one post                     |
| PUT    | `/api/admin/posts/{id}`                    | Update a post                     |
| DELETE | `/api/admin/posts/{id}`                    | Delete a post                     |
| GET    | `/api/admin/comments`                      | Comment queue, filter by status   |
| POST   | `/api/admin/comments/{id}/approve`         | Approve a comment                 |
| POST   | `/api/admin/comments/{id}/reject`          | Reject a comment                  |
| DELETE | `/api/admin/comments/{id}`                 | Delete a comment                  |
| GET    | `/api/admin/subscribers`                   | Newsletter list                   |
| GET    | `/api/admin/tags`                          | Tag list (for the post form)      |
| POST   | `/api/admin/images`                        | Upload a featured image           |

Query parameters: `page` (default 1), `per_page` (default 10).

## Frontend routes

| Path                   | View          |
| ---------------------- | ------------- |
| `/`                    | `Home.vue`    |
| `/blog/{slug}`         | `BlogPost.vue`|
| `/category/{slug}`     | `Category.vue`|
| `/about`               | `About.vue`   |
| `/contact`             | `Contact.vue` |

Anything not matching a Vue route falls through to the SPA's `welcome` blade.

## Project layout

```
app/Http/Controllers/Api/PostController.php   # JSON API
app/Models/{Post,Category,Tag,User}.php       # Eloquent models
database/migrations/                          # Schema, including pivots
resources/js/
  app.js                                      # Vue app entry
  router/index.js                             # Vue Router
  composables/useApi.js                       # axios wrapper for /api/*
  composables/format.js                       # date + reading-time helpers
  components/PostCard.vue                     # shared post card
  views/{Home,BlogPost,Category,About,Contact}.vue
resources/views/welcome.blade.php             # SPA mount point
resources/css/app.css                         # Tailwind entry
routes/web.php                                # / + /api/* + Breeze routes
tailwind.config.js                            # scans .vue + .blade
vite.config.js                                # Vue + Laravel Vite plugins
```

## Development

```bash
# Watch the frontend
npm run dev

# Run PHP tests
php artisan test
composer test                # alias for php artisan test

# Lint PHP code (Laravel preset)
composer lint                # dry-run — fails on style issues
composer lint:fix            # apply fixes

# Lint Vue / JS
npm run lint                 # ESLint, fails on warnings
npm run lint:fix             # apply fixes
npm run format               # Prettier write
npm run format:check         # Prettier verify

# Static analysis (PHPStan, level 5)
composer stan                # requires phpstan/phpstan dev dep

# Build for production
npm run build
```

## CI

`.github/workflows/ci.yml` runs on every push and PR against `main`:

- **test** job (matrix over PHP 8.2 and 8.3): install, migrate, Pint check, PHPUnit.
- **frontend** job: Node 20, `npm ci`, `npm run lint`, `npm run build`.

PHPStan is intentionally not in CI yet — the config and `composer stan` script are wired so a maintainer can opt in once `phpstan/phpstan` is installed on a PHP version that satisfies the lock.

## Roadmap

See [improvement_plan.md](improvement_plan.md) for the full plan. In short:

- **Phase 0** ✅ Stop the bleeding (broken import, `public/hot`).
- **Phase 1** ✅ Migrations, models, Tailwind content, API wiring, README.
- **Phase 2** ✅ Factories, seeders, search, image upload, real pagination.
- **Phase 3** ✅ Sidebar, author pages, comments, newsletter, SEO, RSS.
- **Phase 4** ✅ Admin / CMS: post CRUD, comment moderation, `/admin` SPA.
- **Phase 5** ✅ ESLint, Prettier, Pint, CI workflow, related-posts endpoint, more tests.

## Test coverage summary

| Suite | Tests | Notes |
| ----- | ----- | ----- |
| Phase 0 (cleanup) | 10 | route + .git hygiene |
| Phase 1 (boot)     | 20 | migrations, models, Tailwind, API contract |
| Phase 2 (content)  | 30 | factories, seeder, search, image upload, reading time |
| Phase 3 (features) | 28 | sidebar, authors, comments, newsletter, RSS, view counts |
| Phase 4 (admin)    | 33 | role middleware, post CRUD, comment moderation, /admin SPA |
| Phase 5 (quality)  | 21 | related endpoint, route ordering, API edge cases |
| **Total phase tests** | **142** | All green. Pre-existing `EmailVerificationTest` (Breeze) is the only failure left. |

## License

MIT.
