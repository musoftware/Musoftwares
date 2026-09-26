# Local Development Server Rules (`--env=local`)

## Core Rule
Whenever starting or running the local Laravel development server (`artisan serve`), you MUST explicitly specify `--env=local` and use PHP 8.3:

```bash
C:\tools\php83\php.exe artisan serve --host=127.0.0.1 --port=8000 --env=local
```

## Guidelines
1. **Never omit `--env=local`**: The local development database (`musoftwarescom`, `root`, `127.0.0.1`) is configured exclusively in `.env.local`. Running `artisan serve` without `--env=local` causes Laravel to read `.env` (remote production database host and credentials), resulting in `SQLSTATE[HY000] [2002] Connection refused`.
2. **Authoritative Launcher**: `serve.bat` in the repository root is the designated batch script that encapsulates these exact parameters.
3. **Database Migrations & Commands**: Any artisan commands interacting with the local database (`migrate`, `db:seed`, `tinker`, `test`) must similarly include `--env=local` or verify the active environment to avoid touching production or hitting connection errors.
