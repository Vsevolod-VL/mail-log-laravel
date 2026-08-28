# Workbench

Local development environment for the Mail Log Laravel package.

## Quick Start

Run all commands from `httdocs/` inside the Docker container.

### Build and serve

```bash
vendor/bin/testbench workbench:build
vendor/bin/testbench serve --host=0.0.0.0 --port=8080
```

Open browser: `http://localhost:8080/mail-log`

## Database

### Fresh build with migrations and sample data

```bash
vendor/bin/testbench migrate:fresh --seed
```

### Manual migrations and seeding

```bash
vendor/bin/testbench migrate
vendor/bin/testbench db:seed --class="Database\Seeders\DatabaseSeeder"
```

## Testing and Quality

### Run test suite

```bash
composer test
```

### Lint code

```bash
composer lint
```

### Dry-run refactoring (Rector)

```bash
composer refactor -- --dry-run
```

### Apply refactoring

```bash
composer refactor
```

## Utilities

### List all routes

```bash
vendor/bin/testbench route:list
```

### View diagnostics

Open: `http://localhost:8080/_debug` (when serving)

Shows registered view hints, config, and loaded providers.

### Fresh database without seeding

```bash
vendor/bin/testbench migrate:fresh
```

## Notes

- Sample data in `database/seeders/DatabaseSeeder.php` simulates 6 mail scenarios with varying statuses and recipient patterns.
- `.env` file is auto-copied from `.env.dist` on first container start.
- MySQL connection is `mail_log` user to `mail_log` database on host `mysql`.
