# Helpdesk

IT support ticketing system composed of two applications:

- `api/`: Laravel 13 REST API.
- `app/`: Angular 22 web frontend.

## Requirements

Only Docker and Docker Compose are required to run the project locally. Project dependencies must be installed and executed inside containers.

## Running Locally

Start the full environment:

```bash
docker compose up --build
```

Available services:

- Web app: <http://localhost:4200>
- API: <http://localhost:8000>
- MySQL: `localhost:3306`

Default database settings used by Docker Compose:

```text
Database: helpdesk
User: helpdesk
Password: helpdesk
Root password: root
```

## Authentication API

The API now exposes Sanctum-based authentication for responsible attendants using `email` and `password`.

Endpoints:

- `POST /api/register`
- `POST /api/login`
- `GET /api/me`
- `POST /api/logout`

Start the environment and seed the default users:

```bash
docker compose up --build
docker compose exec api php artisan db:seed
```

Default responsible users:

- `ana.souza@example.com`
- `bruno.lima@example.com`
- `carla.mendes@example.com`
- `diego.santos@example.com`
- `fernanda.rocha@example.com`

Default development password for all seeded users:

```text
Password123!
```

## Project Structure

```text
/
├── api/                # Laravel 13 API
├── app/                # Angular 22 app
├── docker-compose.yml  # Local Docker environment
├── AGENTS.md           # Project standards for agents and LLMs
└── README.md
```

## Notes

- Do not install PHP, Composer, Node, or Angular dependencies directly on the host machine for project work.
- Keep environment-specific values in environment files or Docker Compose configuration.
- Update `AGENTS.md` whenever important development rules or architectural decisions are added.
