# ForkLore v2

ForkLore v2 is a Symfony-based user-generated content (UGC) platform for sharing and exploring culinary heritage — including recipes, stories, community discussions, and support interactions.

## Features

- **Authentication & roles:** User registration/login/logout, hashed passwords, role-based access (`ROLE_USER`, `ROLE_STAFF`, `ROLE_ADMIN`), and protected routes.
- **Recipes & stories:** Create, view, edit, and delete recipe content (with ownership/admin controls), share stories, save/favorite content, and manage recipe visibility.
- **Community:** Discussion posts and replies, with owner/admin post management and user profile links.
- **Profiles & account:** Public/user profiles, bio updates, saved content views, and password change for authenticated users.
- **Support:** User support tickets/messages and staff/admin support workflows.
- **Staff area (`/staff`):** Moderation/support tools (e.g. bans, warnings, support handling) with access separated from admin.
- **Admin area (`/admin`):** Dashboard metrics, user management (create/edit/reset/delete/status/role), records management (view/search/filter and manage supported records), and support/transaction views.
- **Security/UX:** CSRF protection, validation/flash feedback, friendly access handling for unauthorized deep links, and confirmation prompts for destructive actions.

## Tech Stack

- **PHP:** >= 8.4
- **Framework:** Symfony 8.1
- **Database/ORM:** Doctrine ORM & Migrations
- **Templating/UI:** Twig, Symfony Asset Mapper / Stimulus / Turbo
- **Testing:** PHPUnit

## Getting Started

### Prerequisites
- PHP 8.4+
- Composer
- A supported database (configured in your `.env.local`)
- Symfony CLI (recommended) or PHP built-in server

### Setup
1. Install dependencies:
   ```bash
   composer install
   ```
2. Configure environment:
   - Copy/update `.env` settings in `.env.local` (database, app env, etc.).
3. Create/update the database schema:
   ```bash
   php bin/console doctrine:database:create --if-not-exists
   php bin/console doctrine:migrations:migrate
   ```
4. Run the app:
   ```bash
   symfony serve -d
   ```
   Or use your preferred local PHP server setup.

## Roles & Access

- **User (`ROLE_USER`)**: Can use core UGC features (recipes/stories/community/profile/support) based on permissions and ownership.
- **Staff (`ROLE_STAFF`)**: Access to `/staff` for moderation/support workflows; no access to `/admin`.
- **Admin (`ROLE_ADMIN`)**: Access to `/admin` for administration, user management, records management, and support oversight.

> Note: Unauthorized access to protected areas is handled with login redirection (when unauthenticated) or a friendly access-restricted response/page (when authenticated without permission).

## Tests

Run the test suite:

```bash
php bin/phpunit
```

## Project Structure (high level)

- `src/` — Controllers, entities, repositories, forms, services, security/event subscribers
- `templates/` — Twig templates (including `admin/`, `staff/`, and feature views)
- `migrations/` — Doctrine migrations
- `assets/` / `public/` — Front-end assets and public files
- `tests/` — Automated tests

## Notes

- This is a UGC/community project (not an e-commerce platform).
- Real secrets and local overrides should go in `.env.local` (not committed).
- If you add features, prefer Symfony conventions/components and update migrations/tests accordingly.
