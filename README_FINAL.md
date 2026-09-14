# CKPS Mapping — Final Release

Basis: project ZIP supplied by the user on 20 Aug 2026.

## Included
- Laravel 12 application
- ASN master data
- Unit kerja and ASN transfer history
- Training and competency mapping
- Manual training status: belum / diusulkan / sudah
- Audit trail
- Secure login/register/logout
- Excel preview + validation guard
- Row-level Excel validation report
- PhpSpreadsheet 2.2-compatible cell access (A1 coordinates)
- Transactional import
- Pagination and dashboard/report pages

## Important
The accidental nested `ckps-mapping/ckps-mapping` duplicate project has been removed from this release.
The local `.env` file is intentionally not packaged. Keep the existing production/local `.env` when replacing the source.

## After replacing the project files
```bash
composer install
php artisan optimize:clear
php artisan migrate
php artisan storage:link
php artisan serve
```

If `php artisan migrate` says `Nothing to migrate`, that is fine.
Do NOT run `migrate:fresh` on a database containing real ASN data.

## Import flow
Upload Excel -> Preview -> Row validation -> Confirm -> Transactional import.
A blocking validation error prevents the database write.
