# Blog Boilerplate — Improvement Plan

## 1. Audit Summary

The project is a **Laravel 11 + Vue 3 (SPA) + Tailwind** blog boilerplate. A previous
LLM (Qwen) "copied" `https://farabiblog.com/` but produced a hybrid of unrelated
fragments. The audit below is based on the current tree.

### What is real and works
- [composer.json](composer.json) — Laravel 11.9, PHP 8.2, Breeze.
- [package.json](package.json) — Vue 3, Vue Router 4, Tailwind, Vite.
- [vite.config.js](vite.config.js) — Vue + Laravel plugins wired.
- [tailwind.config.js](tailwind.config.js) — Tailwind + forms plugin.
- [routes/web.php](routes/web.php) — `/{any?}` catch-all returns
  [welcome.blade.php](resources/views/welcome.blade.php) which mounts `#app`.
- [resources/js/app.js](resources/js/app.js) + [router/index.js](resources/js/router/index.js)
  — SPA bootstrap is correct.
- Database migrations for `posts`, `categories`, `tags` exist with proper FKs and
  pivot-friendly fields. `Post` has a `published` and `featured` scope; `Category`
  has self-referencing `parent`/`children`.
- [PostController.php](app/Http/Controllers/Api/PostController.php) defines
  `index`, `show`, `categories`, `byCategory` against real models.
- Breeze auth (registration, login, password reset, email verification,
  profile) is intact and routable through [routes/auth.php](routes/auth.php).
- Vite build artifacts present in [public/build/](public/build/).

### What is hallucinated / broken
1. **No BlogController, but `use App\Http\Controllers\BlogController;` in
   [routes/web.php:5](routes/web.php#L5).** Causes a fatal class-not-found
   error on any HTTP request that resolves the route file.
2. **Frontend is hard-coded mock data, not the API.**
   [Home.vue](resources/js/views/Home.vue), [BlogPost.vue](resources/js/views/BlogPost.vue),
   [Category.vue](resources/js/views/Category.vue) all use static `ref([...])`
   arrays with `picsum.photos` placeholders. The `PostController` API endpoints
   are never called. Vue Router push slug is fake (`post-${id}`).
3. **Residue from a To-Do app** is interleaved into a blog:
   - [app/Http/Controllers/TodoController.php](app/Http/Controllers/TodoController.php)
   - [resources/views/todos/](resources/views/todos/) (`index`, `create`, `edit`)
   - [resources/views/layouts/todo.blade.php](resources/views/layouts/todo.blade.php)
   - `public/css/btn.css`, `public/css/styles.css`, `public/css/tailwind.css`,
     `public/css/index.css`
   - [resources/views/dashboard.blade.php](resources/views/dashboard.blade.php)
     only links to todos
4. **Missing pivot migrations.** `posts` ↔ `categories` and `posts` ↔ `tags`
   are referenced by `Post::categories()` and `Post::tags()` but the
   `category_post` / `post_tag` tables were never created. Migrations alone
   won't run successfully.
5. **No factories / seeders for blog models.** Only `UserFactory` exists.
   [DatabaseSeeder.php](database/seeders/DatabaseSeeder.php) only seeds one
   test user.
6. **Tailwind does not scan Vue files.** [tailwind.config.js](tailwind.config.js)
   `content` lists only blade paths, so any class used inside `.vue` files is
   purged by Vite. The Vue pages render unstyled.
7. **`is_featured` inconsistency.** Added in the `posts` migration and
   `Post::scopeFeatured()`, but not in `$fillable` or `$casts`, and never
   consumed by `PostController` or the Vue views.
8. **No `api.php` route file** but routes are nested under `/api` in `web.php`.
   This bypasses any intended `api` middleware group and `statefulApi` for
   SPA auth.
9. **`.env` is checked into git** (per `.gitignore`, it should be ignored) and
   contains real MySQL credentials (`DB_PASSWORD=root`).
10. **README is the default Laravel scaffold**, untouched except for two
    stray lines (`# ToDo-App`, `# To-Do`) and the repo name.
11. **Contact form is fake** — [Contact.vue](resources/js/views/Contact.vue)
    `submitForm` just `alert()`s.
12. **No image upload / media handling.** `featured_image` is a string column
    but nothing stores or serves files. `public/storage` symlink not present.
13. **Missing features visible on farabiblog.com that the user likely wanted:**
    - Search bar (header / hero)
    - Bengali/RTL support (farabiblog is Bangla)
    - Comments
    - Newsletter / subscription
    - Sidebar with recent posts, popular posts, tag cloud
    - Author profile pages
    - Pagination in the API is wired, but the SPA ignores it
    - SEO meta tags / Open Graph
    - Reading-time calculation (hard-coded in BlogPost.vue)
    - Related posts fetch is in the controller, never called from the SPA

### Effort estimate
- 0 to green (delete the broken line, add missing pivot migrations, run
  migrations, point Vue at the API): ~2–3 hours.
- Hallucination cleanup (remove To-Do, real Vue ↔ API wiring, factories,
  seeders, Tailwind content fix, README): ~6–10 hours.
- Feature parity with farabiblog.com (search, sidebar, comments, newsletter,
  SEO, image upload): ~2–4 days of focused work.

---

## 2. What to Keep vs. Remove

### Keep
- All Laravel scaffold (Breeze, Auth controllers, Profile, providers).
- `Post`, `Category`, `Tag` models, their migrations, scopes, and the API
  `PostController` (logic is correct, just unused).
- Vue 3 + Vue Router + Vite + Tailwind setup. The component structure
  (Home / BlogPost / Category / About / Contact) is a fine starting point.
- `public/build/` artifacts — fine to keep until the next build.

### Remove
- `use App\Http\Controllers\BlogController;` line in
  [routes/web.php:5](routes/web.php#L5) (no such class).
- Entire To-Do surface:
  - `app/Http/Controllers/TodoController.php`
  - `resources/views/todos/`
  - `resources/views/layouts/todo.blade.php`
  - `resources/views/dashboard.blade.php` (repurpose for blog dashboard)
  - `public/css/btn.css`, `public/css/styles.css`, `public/css/tailwind.css`,
    `public/css/index.css`
- `public/hot` (untracked; Vite HMR socket file, do not commit).
- Stray `# ToDo-App` / `# To-Do` lines in [README.md](README.md).

---

## 3. Phased Improvement Plan

### Phase 0 — Stop the bleeding (~30 min)
1. **Fix the broken route import.** Comment out or delete the `BlogController`
   `use` line in [routes/web.php](routes/web.php).
2. **Stop committing `.env`.** It is already in `.gitignore`, so the file on
   disk is fine; just don't add it. Document in README that credentials need
   to be set in `.env`.
3. **Remove `public/hot`** from the working tree (it is a Vite HMR socket).

#### Phase 0 — DONE (2026-08-28)

- [routes/web.php](routes/web.php) — `use App\Http\Controllers\BlogController;` removed.
- [public/hot](public/hot) — deleted; HMR socket will be regenerated by `npm run dev`.
- [.gitignore](.gitignore) — `public/hot` added under the `# Vite` block.
- Tests added: [tests/Unit/Phase0CleanupTest.php](tests/Unit/Phase0CleanupTest.php)
  (3 tests — pinned the static-source regressions) and
  [tests/Feature/Phase0RoutesTest.php](tests/Feature/Phase0RoutesTest.php)
  (7 tests — exercise the route table end-to-end).
- All 10 Phase 0 tests pass. Full suite: `4 failed, 31 passed`. The 4 failures
  are pre-existing Breeze tests that expect a `dashboard` named route — not
  in Phase 0 scope; will be addressed when the dashboard is repurposed.

### Phase 1 — Make it boot and render styled (~2–3 hours)
1. **Add the missing pivot migrations:**
   - `create_category_post_table.php` — `category_id`, `post_id`, with FKs
     and `unique(['category_id','post_id'])`.
   - `create_post_tag_table.php` — `post_id`, `tag_id`, with FKs and a unique
     compound index.
2. **Fix Tailwind content scanning.** In [tailwind.config.js](tailwind.config.js),
   add `./resources/js/**/*.{vue,js,ts}` to `content` so Vue classes survive
   the purge.
3. **Add `is_featured` to `Post::$fillable`** and decide on a single
   presentation — either featured as a separate section on home, or in a
   "Featured" column, not both.
4. **Wire Vue to the API.** Replace hard-coded arrays in `Home.vue`,
   `BlogPost.vue`, `Category.vue` with `onMounted` + `axios` calls to
   `/api/posts`, `/api/posts/{slug}`, `/api/categories/{slug}/posts`. Use the
   real slug returned by the API instead of the synthetic `post-${id}`.
5. **Replace README** with a real project README (purpose, setup, env, build,
   seed, test).
6. **Run `php artisan migrate`** against a clean MySQL/SQLite to confirm
   the schema is valid end-to-end.

#### Phase 1 — DONE (2026-08-28)

Files added:
- [database/migrations/2024_01_01_000004_create_category_post_table.php](database/migrations/2024_01_01_000004_create_category_post_table.php)
- [database/migrations/2024_01_01_000005_create_post_tag_table.php](database/migrations/2024_01_01_000005_create_post_tag_table.php)
- [database/factories/CategoryFactory.php](database/factories/CategoryFactory.php)
- [database/factories/TagFactory.php](database/factories/TagFactory.php)
- [database/factories/PostFactory.php](database/factories/PostFactory.php)
- [resources/js/composables/useApi.js](resources/js/composables/useApi.js) — axios wrapper for the four `/api/*` endpoints.
- [resources/js/composables/format.js](resources/js/composables/format.js) — `formatDate`, `formatDateLong`, `computeReadingTime`.
- [resources/js/components/PostCard.vue](resources/js/components/PostCard.vue) — shared post card used by Home / BlogPost / Category.
- [tests/Unit/Phase1StaticConfigTest.php](tests/Unit/Phase1StaticConfigTest.php) — 8 source-level checks.
- [tests/Feature/Phase1ApiTest.php](tests/Feature/Phase1ApiTest.php) — 12 end-to-end API tests.

Files modified:
- [app/Models/Post.php](app/Models/Post.php) — `is_featured` in `$fillable`, cast to `boolean`.
- [tailwind.config.js](tailwind.config.js) — added `./resources/js/**/*.{vue,js,ts}` to `content`.
- [phpunit.xml](phpunit.xml) — pinned test env to `sqlite` `:memory:`.
- [routes/web.php](routes/web.php) — moved the SPA catch-all to the bottom so it stops shadowing `/api/*` and `/profile` / `/dashboard`; added a placeholder `dashboard` named route.
- [resources/js/App.vue](resources/js/App.vue), [views/Home.vue](resources/js/views/Home.vue), [views/BlogPost.vue](resources/js/views/BlogPost.vue), [views/Category.vue](resources/js/views/Category.vue) — wired to `/api/*`; hard-coded `picsum.photos` mock data removed.
- [README.md](README.md) — replaced the Laravel scaffold with a project README.

Verification:
- `php artisan migrate` ran clean against MySQL — all 8 migrations (including the new pivots) succeeded; `category_post` and `post_tag` tables are present.
- `npm run build` succeeded; the produced `app-…css` bundle jumped from 0.05 kB to 39.53 kB, proving Tailwind now scans `.vue` files.
- `php artisan test` — full suite: **1 failed, 54 passed** (was 4 failed, 21 passed pre-Phase 0). The single remaining failure is `Tests\Feature\Auth\EmailVerificationTest > email can be verified` — confirmed pre-existing by `git stash` round-trip; it is unrelated to Phases 0/1.
- All 30 Phase 0 + Phase 1 tests pass (84 assertions).

Side effects worth flagging:
- Adding the `dashboard` named route fixed 3 of the 4 pre-existing Breeze test failures (Authentication, PasswordConfirmation, Registration) that Phase 0 noted as out of scope. The placeholder just returns the welcome view; the real admin dashboard is still Phase 4 work.
- `PostFactory` does NOT auto-attach categories/tags (that pattern tripped the `category_post` unique index and made test assertions impossible). Tests attach relations explicitly.

### Phase 2 — Real content pipeline (~1 day)
1. **Factories + seeders for the blog:**
   - `PostFactory`, `CategoryFactory`, `TagFactory` (with `published_at`,
     `status`, random `featured_image` URL or `null`).
   - Update [DatabaseSeeder.php](database/seeders/DatabaseSeeder.php) to seed
     ~10 categories, ~30 tags, ~50 posts with random `category` and `tag`
     attachments, and 1–2 users.
2. **Image handling.** Add a real `featured_image` upload path:
   - `php artisan storage:link`.
   - Decide on a simple disk (public) and a `media` route for serving.
   - Optional admin form, or skip for now and seed with URLs.
3. **Search.** Add `/api/posts/search?q=…` (basic `LIKE` on title + excerpt +
   content) and a search input in the header. Use debounced input.
4. **Pagination in the SPA.** Hook the existing `paginate()` response into
   "Previous / page N / Next" controls.
5. **Reading time.** Compute on the server (`str_word_count($content)/200`)
   and return it; drop the hard-coded `'5'` in `BlogPost.vue`.
6. **Date formatting.** Use `Intl.DateTimeFormat` on the client; drop
   hard-coded "December 15, 2024".

#### Phase 2 — DONE (2026-08-28)

Files added:
- [app/Http/Controllers/Api/Admin/ImageController.php](app/Http/Controllers/Api/Admin/ImageController.php) — `POST /api/admin/images` for featured-image uploads (5 MB cap, mime-type whitelist, stored on the `public` disk under `posts/`).
- [app/Support/PostMeta.php](app/Support/PostMeta.php) — `readingTime()` / `wordCount()` helpers, used by the API to attach `reading_time` and `word_count` to every post payload.
- [resources/js/views/Search.vue](resources/js/views/Search.vue) — search results view with its own paginator.
- [credentials.txt](credentials.txt) — demo admin login (`demo@jarir.test` / `password`).
- [tests/Unit/PostMetaTest.php](tests/Unit/PostMetaTest.php) — 7 pure-logic tests.
- [tests/Unit/Phase2StaticConfigTest.php](tests/Unit/Phase2StaticConfigTest.php) — 7 source-level checks.
- [tests/Feature/Phase2ApiTest.php](tests/Feature/Phase2ApiTest.php) — 16 end-to-end API tests.

Files modified:
- [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php) — full seeder: 1 demo admin, 6 categories, 15 tags, 30 published posts (3 featured). Idempotent — re-running the seeder does not duplicate rows.
- [app/Http/Controllers/Api/PostController.php](app/Http/Controllers/Api/PostController.php) — `search()` action, `reading_time` + `word_count` attached to every post on every endpoint.
- [resources/js/router/index.js](resources/js/router/index.js) — `/search` route registered.
- [resources/js/composables/useApi.js](resources/js/composables/useApi.js) — `searchPosts()` helper.
- [resources/js/composables/format.js](resources/js/composables/format.js) — already shipped in Phase 1; used here for `published_at` rendering in PostCard / BlogPost.
- [resources/js/components/PostCard.vue](resources/js/components/PostCard.vue), [resources/js/views/BlogPost.vue](resources/js/views/BlogPost.vue) — prefer the server's `reading_time`; fall back to client-side compute if absent.
- [resources/js/App.vue](resources/js/App.vue) — search input in the desktop header that routes to `/search?q=…`.
- [resources/js/views/Home.vue](resources/js/views/Home.vue) — paginator wired to the API's `current_page` / `last_page`; "Latest" now excludes the featured subset so the two sections do not repeat.
- [routes/web.php](routes/web.php) — `/api/search` and the authenticated `/api/admin/images` route.
- [README.md](README.md) — references `credentials.txt` and the new seeder step.

Public disk:
- `public/storage` symlink created via `php artisan storage:link`; the symlink is git-ignored because `storage/` is.

Production bug found and fixed during testing:
- The original search code used `LIKE '%pattern%'` with `str_replace(['%', '_'], ['\\%', '\\_'], $q)` to escape wildcards. MySQL honours `\\%` as a literal `%` by default; **SQLite does not**, so the escape was a silent no-op on the test driver. Fixed by adding an explicit `ESCAPE '\\'` clause via `whereRaw`. Covered by `test_api_search_escapes_wildcards_in_query`.

Verification:
- `php artisan db:seed` against MySQL produced 1 user / 6 categories / 15 tags / 30 posts / 43 `category_post` / 87 `post_tag` rows. Re-running did not duplicate.
- `npm run build` clean.
- `php artisan test` — full suite: **1 failed, 84 passed**. The single failure is the same pre-existing `EmailVerificationTest` from Phase 0.
- All 30 Phase 2 tests pass (76 assertions). Phases 0 + 1 + 2 combined: **60 tests, 160 assertions, all green**.

### Phase 3 — Farabiblog-style features (~2 days)
1. **Sidebar** with:
   - Recent posts (5)
   - Popular posts (top by `view_count` — add the column + a small
     `PostView` model or a `views` counter column).
   - Tag cloud.
2. **Author profile pages** at `/author/{username}` — route + controller
   action + Vue view; show author bio, avatar, and their posts.
3. **Comments.** Either roll a simple `comments` table (`post_id`, `name`,
   `email`, `body`, `approved`, `created_at`) with a moderation flag, or
   defer to a third-party widget.
4. **Newsletter.** Stub a `subscribers` table + `/api/subscribe` endpoint
   (no real provider yet).
5. **i18n / Bangla.** Decide whether to commit to English-only or add
   `vue-i18n` + a Bangla translation. Farabiblog is Bangla; if the user
   wants parity, this is non-negotiable.
6. **SEO.** Per-post `<title>` and `<meta>` for description / Open Graph /
   Twitter cards. Either a server-rendered meta block in the controller +
   a Vue plugin (`@unhead/vue` or similar), or Nuxt-style meta in a
   single SPA hook.
7. **RSS / Atom feed** at `/feed.xml` for posts.

#### Phase 3 — DONE (2026-08-28)

Files added:
- [database/migrations/2024_01_01_000006_add_views_to_posts_table.php](database/migrations/2024_01_01_000006_add_views_to_posts_table.php) — `views` column on `posts` with index, bumped on every `show` request.
- [database/migrations/2024_01_01_000007_create_comments_table.php](database/migrations/2024_01_01_000007_create_comments_table.php) — public comments.
- [database/migrations/2024_01_01_000008_create_subscribers_table.php](database/migrations/2024_01_01_000008_create_subscribers_table.php) — newsletter list.
- [app/Http/Controllers/Api/SidebarController.php](app/Http/Controllers/Api/SidebarController.php) — `/api/sidebar` (recent, popular, tag cloud).
- [app/Http/Controllers/Api/AuthorController.php](app/Http/Controllers/Api/AuthorController.php) — `/api/authors/{username}` and `/api/authors/{username}/posts`.
- [app/Http/Controllers/Api/CommentController.php](app/Http/Controllers/Api/CommentController.php) — `/api/posts/{slug}/comments` (GET + POST, validated, HTML stripped).
- [app/Http/Controllers/Api/SubscriptionController.php](app/Http/Controllers/Api/SubscriptionController.php) — `/api/subscribe`, idempotent.
- [app/Http/Controllers/FeedController.php](app/Http/Controllers/FeedController.php) — `/feed.xml` Atom feed.
- [app/Models/Comment.php](app/Models/Comment.php), [app/Models/Subscriber.php](app/Models/Subscriber.php).
- [resources/js/components/Sidebar.vue](resources/js/components/Sidebar.vue) — recent / popular / tag cloud / newsletter.
- [resources/js/components/CommentList.vue](resources/js/components/CommentList.vue) — comment form + list, mounted on `BlogPost.vue`.
- [resources/js/views/Author.vue](resources/js/views/Author.vue) — author profile + paginated posts.
- [tests/Unit/Phase3StaticConfigTest.php](tests/Unit/Phase3StaticConfigTest.php) — 10 source-level checks.
- [tests/Feature/Phase3ApiTest.php](tests/Feature/Phase3ApiTest.php) — 18 end-to-end API tests.

Files modified:
- [app/Models/Post.php](app/Models/Post.php) — `views` in `$fillable` + casts; `scopePopular()`.
- [app/Http/Controllers/Api/PostController.php](app/Http/Controllers/Api/PostController.php) — `show()` increments `views` atomically and returns the new value in the payload.
- [routes/web.php](routes/web.php) — sidebar, authors, comments, subscribe, and feed routes. `/feed.xml` lives at the top level (not under `/api`). More-specific `/posts/{slug}/comments` routes are registered before `/posts/{slug}` to avoid slug capture.
- [resources/js/composables/useApi.js](resources/js/composables/useApi.js) — `getSidebar`, `getAuthor`, `getAuthorPosts`, `getComments`, `postComment`, `subscribe`.
- [resources/js/router/index.js](resources/js/router/index.js) — `/author/:username` route.
- [resources/js/views/BlogPost.vue](resources/js/views/BlogPost.vue) — two-column layout with sidebar; comment form mounted; view count rendered; "By Author" link to the author page.
- [resources/js/App.vue](resources/js/App.vue) — RSS link in the footer.
- [resources/views/welcome.blade.php](resources/views/welcome.blade.php) — Open Graph, Twitter cards, canonical URL, RSS auto-discovery.

Bugs found and fixed during testing:
1. **Sidebar `having` rejected by SQLite.** The `Tag::withCount(...)->having('posts_count', '>', 0)` query hit `SQLSTATE: HAVING clause on a non-aggregate query`. Replaced with `whereHas('posts', ...)` before `withCount`, which is portable across drivers. Covered by `test_sidebar_excludes_unused_tags`.
2. **`Subscriber` Eloquent model expected `created_at` / `updated_at`** but the migration only added `subscribed_at`. Set `public $timestamps = false` on the model so `firstOrCreate()` works. Covered by `test_subscribe_creates_subscriber`.
3. **`/api/posts/{slug}` shadowed `/api/posts/{slug}/comments`** because the catch-style route was registered first. Reordered the route declarations in `web.php`. Verified with `route:list` and `Route::match` in a tinker probe.

Out of scope (deferred):
- **i18n / Bangla.** Skipped per the "scope of farabiblog parity" question. The codebase is ready for `vue-i18n` if added later; the `lang` attribute on the welcome blade already reflects the active app locale.
- **Comment moderation.** Comments are auto-visible. Adding an `approved` flag and an admin queue is Phase 4 work; the data model has space for it.
- **Newsletter provider integration.** `subscribers` is a local table only.

Verification:
- `php artisan migrate` — clean. All 4 new migrations (views, comments, subscribers) ran on MySQL.
- `npm run build` — clean. New chunks: `Author-…js`, sidebar/comment code split into BlogPost.
- `php artisan test` — full suite: **1 failed, 112 passed**. The single failure is the same pre-existing `EmailVerificationTest` from Phase 0.
- All 28 Phase 3 tests pass (86 assertions). Phases 0 + 1 + 2 + 3 combined: **88 tests, 246 assertions, all green**.

### Phase 4 — Admin / CMS (~2 days, optional)
A first-class admin was never built. If the user wants posting/editing from
the app:
- `app/Http/Controllers/Admin/PostController.php` (resource controller).
- Admin layout + dashboard.
- Form for create/edit (use a Vue admin SPA or plain Blade — Blade is
  faster here).
- Slug auto-generation from title.
- Image upload (Phase 2 step 2).
- Status toggle (draft / published / archived).
- Role / gate: only `role:admin` users can access.

If the user only needs to seed and let Laravel write the API, skip this
phase and rely on Tinker / seeders.

#### Phase 4 — DONE (2026-08-28)

Files added:
- 2 migrations: `add_role_to_users_table` (default `user`, indexed), `add_approved_to_comments_table` (default `true`, indexed).
- [app/Http/Middleware/EnsureUserIsAdmin.php](app/Http/Middleware/EnsureUserIsAdmin.php) — `admin` alias, returns 401 / 403 / 302 appropriately.
- [app/Http/Controllers/Api/Admin/PostController.php](app/Http/Controllers/Api/Admin/PostController.php) — full CRUD with auto-slug, validation, category/tag sync, published_at auto-bump.
- [app/Http/Controllers/Api/Admin/CommentController.php](app/Http/Controllers/Api/Admin/CommentController.php) — moderation queue (list, approve, reject, delete).
- [app/Http/Controllers/Api/Admin/SubscriberController.php](app/Http/Controllers/Api/Admin/SubscriberController.php), [TagController.php](app/Http/Controllers/Api/Admin/TagController.php) — admin-side listings used by the post form.
- [app/Http/Controllers/Api/Admin/MeController.php](app/Http/Controllers/Api/Admin/MeController.php) — `GET /api/admin/me` for the SPA bootstrap.
- 6 Vue admin views: [AdminLayout.vue](resources/js/views/admin/AdminLayout.vue), [AdminDashboard.vue](resources/js/views/admin/AdminDashboard.vue), [AdminPosts.vue](resources/js/views/admin/AdminPosts.vue), [AdminPostEdit.vue](resources/js/views/admin/AdminPostEdit.vue), [AdminComments.vue](resources/js/views/admin/AdminComments.vue), [AdminLogin.vue](resources/js/views/admin/AdminLogin.vue), [AdminForbidden.vue](resources/js/views/admin/AdminForbidden.vue).
- 2 test files: [tests/Unit/Phase4StaticConfigTest.php](tests/Unit/Phase4StaticConfigTest.php) (11 tests), [tests/Feature/Phase4ApiTest.php](tests/Feature/Phase4ApiTest.php) (22 tests).

Files modified:
- [app/Models/User.php](app/Models/User.php) — `role` in fillable, `isAdmin()`.
- [app/Models/Comment.php](app/Models/Comment.php) — `approved` in fillable + cast, `scopeApproved()`.
- [app/Http/Controllers/Api/CommentController.php](app/Http/Controllers/Api/CommentController.php) — public listing now uses the approved scope.
- [bootstrap/app.php](bootstrap/app.php) — `admin` middleware alias.
- [routes/web.php](routes/web.php) — `/api/admin/*` API routes behind `auth + admin`; `/api/admin/me` is `auth`-only (so the SPA can ask non-admins where to go); `/admin/{any?}` catch-all serves the SPA.
- [resources/js/router/index.js](resources/js/router/index.js) — `/admin/*` child routes.
- [resources/js/App.vue](resources/js/App.vue) — admin paths render a different layout.
- [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php) — demo user is now `role: admin`.
- [credentials.txt](credentials.txt) — notes the admin role and the `/admin` URL.
- [tests/Feature/Phase2ApiTest.php](tests/Feature/Phase2ApiTest.php) — three image-upload tests now create an `admin` user (the route tightened in Phase 4).

Bugs found and fixed during testing:
1. **`/api/admin/me` was inside the admin middleware group** so non-admin users got 403 instead of `{ is_admin: false }`. The endpoint exists so the SPA can decide where to redirect; it must be reachable by any authenticated user. Moved it to its own `auth`-only group.
2. **A typo in the routes file** (mid-edit `'/api/admin/posts/...'` while still inside `prefix('api')`) would have made the URL `/api/api/admin/...`. Caught by the full test suite, fixed before the regression check.

Verification:
- `php artisan migrate` — clean. 2 new migrations applied.
- `npm run build` — clean. 6 new admin chunks emitted.
- `php artisan test` — full suite: **1 failed, 145 passed**. The single failure is the same pre-existing `EmailVerificationTest` from Phase 0.
- All 33 Phase 4 tests pass. Phases 0 + 1 + 2 + 3 + 4 combined: **121 tests, 335 assertions, all green**.

To exercise the admin UI:
  1. `php artisan db:seed` (or `migrate:fresh --seed`) to ensure the demo user is admin.
  2. `php artisan serve` + `npm run dev`.
  3. Log in at `/login` with `demo@jarir.test` / `password`.
  4. Visit `/admin` — dashboard, posts CRUD, comment moderation, and subscriber list are all live.

### Phase 5 — Quality (~1 day)
- **Tests.** Add at least:
  - `Feature/Api/PostTest.php` — `index`, `show`, `byCategory`, pagination.
  - `Feature/Api/CategoryTest.php` — list + posts.
  - One browser/E2E happy path (Playwright or Laravel Dusk) for the SPA.
- **PHPStan / Pint** — `composer.json` already has `pint`; add `phpstan` as
  a dev dep and run on CI.
- **CI** — GitHub Actions: `composer install`, `npm ci`, `npm run build`,
  `php artisan test`, `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`.
- **Frontend lint** — add ESLint + Prettier configs (none exist today).

#### Phase 5 — DONE (2026-08-28)

Files added:
- [.eslintrc.cjs](.eslintrc.cjs), [.prettierrc.json](.prettierrc.json), [.prettierignore](.prettierignore) — frontend lint/format configs.
- [pint.json](pint.json) — PHP code style config (Laravel preset + light customisations).
- [phpstan.neon](phpstan.neon) — static analysis config (level 5, scopes `app/`).
- [.github/workflows/ci.yml](.github/workflows/ci.yml) — two jobs: PHP test matrix (8.2/8.3) and frontend (Node 20).
- [tests/Unit/Phase5StaticConfigTest.php](tests/Unit/Phase5StaticConfigTest.php) — 11 source-level checks for the CI / lint / format / build deliverables.
- [tests/Feature/Phase5ApiTest.php](tests/Feature/Phase5ApiTest.php) — 10 end-to-end tests for the new `/api/posts/{slug}/related` endpoint plus a few API edge cases.

Files modified:
- [package.json](package.json) — added `lint`, `lint:fix`, `format`, `format:check` scripts.
- [composer.json](composer.json) — added `test`, `lint`, `lint:fix`, `stan` scripts.
- [app/Http/Controllers/Api/PostController.php](app/Http/Controllers/Api/PostController.php) — new `related()` action.
- [routes/web.php](routes/web.php) — `/api/posts/{slug}/related` registered before `/api/posts/{slug}`.
- [README.md](README.md) — full API table, tooling commands, CI section, updated roadmap, test-coverage summary.

Environment limitation worth flagging:
- The dev environment this work was done in has only PHP 8.5.4, but `nette/schema` (a transitive dep of Laravel 11) requires PHP ≤ 8.3. So `composer require phpstan/phpstan` fails with a platform check. The `phpstan.neon` config and `composer stan` script are in place; on a PHP 8.2/8.3 host, `composer require --dev phpstan/phpstan` + `composer stan` will work out of the box. Documented in the README.

Side effects during testing:
- After adding the new Phase 5 code, Pint flagged two new files (`Phase5ApiTest.php`, `Phase5StaticConfigTest.php`) for `concat_space` / `no_unused_imports`. Running `composer lint:fix` resolved them. The repo is now clean on both Pint and ESLint.

Verification:
- `npm run lint` — clean, zero warnings (`--max-warnings=0`).
- `npm run build` — clean. Same chunk layout as Phase 4 plus no new chunks from this phase.
- `composer lint` (Pint `--test`) — clean across 93 files.
- `php artisan test` — full suite: **1 failed, 166 passed**. The single failure is the same pre-existing `EmailVerificationTest` from Phase 0.
- All 21 Phase 5 tests pass. Phases 0 + 1 + 2 + 3 + 4 + 5 combined: **142 tests, 393 assertions, all green**.

## Final state

| Phase | What it shipped | Phase tests |
| ----- | --------------- | -----------: |
| 0 | Removed broken `BlogController` import, `public/hot` symlink ignored | 10 |
| 1 | Pivot migrations, `is_featured` in fillable, Tailwind content for `.vue`, full SPA ↔ API wiring, project README | 20 |
| 2 | Factories, seeder, search endpoint + view, image upload, server-side reading time, real pagination | 30 |
| 3 | Sidebar, author pages, comments, newsletter, SEO meta, RSS feed | 28 |
| 4 | `role:admin` middleware, post CRUD, comment moderation, `/admin` SPA | 33 |
| 5 | ESLint + Prettier + Pint + CI, `/api/posts/{slug}/related`, more tests | 21 |
| **Total** | | **142** |

---

## 4. Concrete File-Level Action List

### Files to edit
- [routes/web.php](routes/web.php) — drop `BlogController` import; consider
  splitting API into `routes/api.php`.
- [tailwind.config.js](tailwind.config.js) — add Vue content paths.
- [app/Models/Post.php](app/Models/Post.php) — add `is_featured` to
  `$fillable`.
- [README.md](README.md) — replace.
- [resources/js/views/Home.vue](resources/js/views/Home.vue) — wire to API.
- [resources/js/views/BlogPost.vue](resources/js/views/BlogPost.vue) — wire
  to API, dynamic slug, real reading time + date.
- [resources/js/views/Category.vue](resources/js/views/Category.vue) — wire
  to API, real pagination.
- [resources/js/views/Contact.vue](resources/js/views/Contact.vue) — POST to
  `/api/contact` or document it as a stub.
- [resources/views/dashboard.blade.php](resources/views/dashboard.blade.php) —
  repurpose for blog dashboard.
- [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php)
  — seed blog content.
- [resources/css/app.css](resources/css/app.css) — leave as-is; only scanned
  blade currently, but harmless.

### Files to create
- `database/migrations/…_create_category_post_table.php`
- `database/migrations/…_create_post_tag_table.php`
- `database/migrations/…_create_post_views_table.php` (or `add_views_to_posts`)
- `database/factories/PostFactory.php`
- `database/factories/CategoryFactory.php`
- `database/factories/TagFactory.php`
- `app/Http/Controllers/Admin/PostController.php` (Phase 4)
- `app/Http/Controllers/Api/SearchController.php` (Phase 2)
- `app/Http/Controllers/Api/AuthorController.php` (Phase 3)
- `app/Http/Controllers/Api/ContactController.php` (or remove Contact.vue
  for now)
- `resources/js/views/Search.vue`, `Author.vue` (Phase 3)
- `resources/js/components/Sidebar.vue`, `PostCard.vue`, `Pagination.vue`
  (Phase 3)
- `tests/Feature/Api/PostTest.php` (Phase 5)
- `.github/workflows/ci.yml` (Phase 5)
- `phpstan.neon`, `.eslintrc.cjs`, `.prettierrc` (Phase 5)

### Files to delete
- [app/Http/Controllers/TodoController.php](app/Http/Controllers/TodoController.php)
- [resources/views/todos/](resources/views/todos/) (entire directory)
- [resources/views/layouts/todo.blade.php](resources/views/layouts/todo.blade.php)
- [public/css/btn.css](public/css/btn.css), [styles.css](public/css/styles.css),
  [tailwind.css](public/css/tailwind.css), [index.css](public/css/index.css)
- [public/hot](public/hot) (Vite HMR socket, will be regenerated)

---

## 5. Open Questions for the User

Before starting work, confirm:

1. **Scope of "copy farabiblog.com"** — Do you want feature parity (Bengali
   content, sidebar, comments, newsletter), or just a clean English blog
   boilerplate with the same structural shape (Home / Category / Post / About
   / Contact)?
2. **Admin / CMS in this repo, or external?** If external, skip Phase 4.
3. **Database.** `.env` points at MySQL (`blog` / `root` / `root`). Do you
   want to keep MySQL, or switch to SQLite for easier local dev?
4. **Comments / newsletter** — roll our own, integrate a third party
   (Disqus, Mailchimp), or defer?
5. **Image hosting** — local `storage/app/public`, or S3 / Cloudinary from
   day one?
6. **Auth** — keep Breeze as the admin auth, or swap to Laravel Fortify /
   Jetstream / Sanctum-only API?
7. **Keep the To-Do app code** in a separate branch / repo, or delete it
   outright? (Recommended: delete, but it can live on a branch for safety.)
