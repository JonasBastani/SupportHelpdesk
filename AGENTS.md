# AGENTS.md

## Project Overview

This project is an IT support ticketing system. It is split into two main applications:

- `api/`: REST API built with Laravel 13.
- `app/`: Web frontend built with Angular 22.

## Repository Structure

Expected high-level structure:

```text
/
├── api/      # Laravel 13 API
├── app/      # Angular 22 web application
├── README.md # Project description and setup instructions
└── AGENTS.md # Agent instructions and project conventions
```

## API Rules

The API must be built in Laravel 13 and located in `api/`.

### Standards

- Follow PSR-4 autoloading standards.
- Follow PSR-12 coding style.
- Use MySQL as the database.
- Follow REST principles and return correct HTTP status codes.
- Use environment variables properly through `.env`.
- Never hardcode sensitive data in the source code.
- Maintain a good `.env.example` to make initial setup easy.

### Database

- Use Laravel migrations for all database schema changes.
- Use seeders for required initial data.
- Be careful with N+1 query problems.
- Use eager loading whenever relationships are needed to avoid unnecessary database queries.

### Authentication

- Use Laravel Sanctum for authentication.
- At this stage, every authenticated user in the API represents a responsible attendant for support calls unless a future requirement introduces explicit roles.

### Architecture

- Use a Service and Repository architecture.
- Repositories and Services must have interfaces.
- Bind interfaces to concrete implementations through Laravel's standard dependency injection container, preferably in a service provider.
- Keep business rules in services.
- Controllers should stay thin and delegate business logic to services.

### Testing

- Use TDD for every feature.
- Use PHPUnit.
- Test business rules at the service layer.
- When needed, inject mocked repository dependencies into services during tests.
- Add or update tests whenever business behavior changes.

## Frontend App Rules

The frontend must be built in Angular 22 and located in `app/`.

### State Management

- Use Angular Signals plus Local Storage for state management.
- Do not introduce another state management library unless explicitly approved.

### Styling and UI

- Never add CSS styles directly inside HTML templates.
- The interface must be responsive.
- The interface must be accessible.
- The interface must support both dark mode and light mode.
- User-facing software text, messages, and alerts must be in Brazilian Portuguese.

### HTTP and Data Types

- Use Angular's built-in HttpClient for HTTP requests.
- Type every data structure returned by the API.
- Example: a support ticket returned by the API must have a frontend type such as `SupportCall`.

### Business Logic

- Do not place business rules in the frontend.
- The frontend should present data, collect user input, and call the API.
- Business decisions and validations that belong to the domain must live in the API.

### Environment

- Environment-specific data must be placed in the Angular development environment configuration.
- The development environment must be the default environment used while running the app locally.

## General Rules For API And App

- Follow SOLID principles.
- Follow object-oriented programming principles where applicable.
- Never create more than one class in the same file.
- Variables must always be written in English.
- Code comments must always be written in English.
- User-facing software text and alerts must be written in Brazilian Portuguese.
- Keep code readable, explicit, and consistent with the local project patterns.
- Avoid adding abstractions unless they reduce real complexity or match an established project pattern.

## Docker And Local Environment

- The project must use Dockerfiles and Docker Compose.
- The API and frontend must be runnable quickly and easily with a single Docker Compose command.
- The root `README.md` must describe the project and include all required setup and run steps.

## Documentation Expectations

- Add relevant information to this file during development whenever new architectural decisions, standards, setup requirements, or recurring project rules appear.
- Keep this file current as the project evolves.
- If an implementation decision is not covered by this file and is not obvious from the existing codebase, ask the user before proceeding.

## Agent Behavior

- Read this file before making project changes.
- Respect the split between `api/` and `app/`.
- Do not create new functionality, dependencies, tooling, or project scaffolding unless the user explicitly asks for it.
- Prefer small, focused changes that follow the existing architecture.
- Preserve user changes and never revert unrelated work.
- When unsure about a requirement, ask for clarification instead of guessing.
