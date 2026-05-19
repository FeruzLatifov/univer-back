---
name: add-shared-table-migration
description: Write a Laravel migration that touches a Yii2-owned (e_*) table or adds a new shared table. Use when the user asks to change schema for any table starting with e_ or to add a table that both Yii2 and Laravel will read.
---

# Migration on Yii2-shared schema

This Laravel app shares its database with the Yii2 legacy app (`../univer/`). Every table prefixed `e_*` (and `oauth_*`) has at least one Active Record model on the Yii2 side under `../univer/common/models/`. **The Yii2 model is the schema contract.** Migrations here that drift from that contract will corrupt production.

## Decision flow

1. **Is the table you're touching already known to Yii2?** Grep `../univer/common/models/` for the table name (search for `return '<table>'`). If yes → this is a *shared-table* migration; follow the full checklist below. If no → it's a Laravel-only table (e.g. `e_password_reset_tokens`, `e_auth_refresh_tokens`, `e_system_login`); standard Laravel migration suffices, but still avoid renaming if possible.
2. **What are you doing?**
   - **Adding a nullable column** → low risk; both stacks tolerate it, Yii2 ignores unknown attributes by default. **Acceptable.**
   - **Adding a NOT NULL column with default** → acceptable, but Yii2 inserts/updates that don't set this column will start writing the default; check if that's desired.
   - **Renaming or dropping a column** → **stop**. Coordinate with the Yii2 side; the rename must land there too in the same deploy, or one stack throws on every read.
   - **Changing a column type** → only safe if the Yii2 model casts the value the same way. Check `rules()` and `attributeLabels()` in the corresponding model.
   - **Adding an index** → safe, but if it's a unique constraint, Yii2 unique validators (defined in the model's `rules()`) need updating too.
3. **Is there a foreign key relationship the Yii2 side enforces in `relations()` rather than at the DB level?** Many `e_*` tables use application-level joins, not FK constraints. Don't add a FK constraint that contradicts existing Yii2 data.

## File checklist

1. **Find the Yii2 model first.** Open `../univer/common/models/<domain>/<Model>.php`. Note:
   - The exact column names from `attributeLabels()` / `rules()`.
   - Any `behaviors()` like `TimestampBehavior` (which auto-fills `created_at` / `updated_at` columns from the Yii2 side — don't duplicate this behavior on the Laravel side or you'll get conflicting writes).
   - Any `getXxx()` methods that define a relation — these encode the schema graph implicitly.
2. **Create the Laravel migration** in `database/migrations/`:
   - Naming: `YYYY_MM_DD_HHMMSS_<verb>_<column_or_change>_to_e_<table>.php`.
   - Use `Schema::table('e_<table>', function (Blueprint $table) { ... })`. **Don't** generate via `php artisan make:migration --create=e_admin --table=e_admin` if the table already exists — only the column change.
   - For new shared tables (rare), declare every column the Yii2 side will need, including `created_at`/`updated_at`/`created_by`/`updated_by` if `TimestampBehavior` is in scope.
3. **Update the Laravel model** in `app/Models/E<Entity>.php`:
   - Add the column to `$fillable` (or `$guarded = []` if that's the convention used).
   - Add a cast in `$casts` matching how the Yii2 side treats the column.
4. **Update the Yii2 model** if needed:
   - The user may or may not want you to touch `../univer/`. Default: **ask first** before editing files in `../univer/`. If they confirm:
     - Add the new property docblock at the top of the class.
     - Add validation in `rules()`.
     - Add the label in `attributeLabels()` (localized labels live in `../univer/common/messages/`).

## Production safeguards

`app/Providers/AppServiceProvider.php::blockDangerousQueries()` throws in production if any query contains `DROP TABLE`, `DROP DATABASE`, `TRUNCATE TABLE`, `ALTER TABLE`, `DROP COLUMN`, or `DROP INDEX`. This means:

- Migrations bypass it (they run under the `migrate` command, not the runtime query listener) — so the safeguard does not protect you when running `php artisan migrate` in prod. You are the safeguard.
- **Always** run `php artisan migrate --pretend` first to see the exact SQL.
- **Always** run on a test database (`USE_TEST_DATABASE=true`, `DB_DATABASE_TEST=test_401`) end-to-end before prod.

## Verification

```bash
# Inspect SQL without executing
php artisan migrate --pretend

# Run against test database first
USE_TEST_DATABASE=true DB_DATABASE_TEST=test_401 php artisan migrate

# Verify both stacks still see the schema correctly
php artisan tinker
# >>> App\Models\E<Entity>::first()->toArray();   # Laravel side

# On the Yii2 side (cd ../univer):
# php yii migrate/history       # confirm Yii2 migration state untouched
# ./yii console                 # then: \common\models\<domain>\<Model>::find()->one()
```

## What NOT to do

- **Never** run a migration that renames or drops an `e_*` column without coordinating with the Yii2 model. The grep `../univer/common/models/ -r "<column>"` is the minimum diligence.
- **Never** add `down()` logic that drops data. Migrations on shared tables should be effectively irreversible in prod — use a *separate forward migration* if you need to undo.
- **Don't** put data backfill in the migration's `up()` for large `e_*` tables (`e_admin`, `e_student` ~> hundreds of thousands of rows). Use a separate seeder or queued job; migrations should be schema-only.
- **Don't** touch tables prefixed `oauth_*` casually — they back the OAuth2 server compatible with `../univer/common/modules/oauth/`. External integrators rely on the wire format.
- **Don't** add `created_at`/`updated_at`/`created_by`/`updated_by` columns blindly. Check whether the Yii2 model uses `TimestampBehavior` first — if it does, Yii2 will silently overwrite Laravel's `updated_at` on every Yii2 write.
