# User API

A single REST endpoint `/v1/api/users` for a user entity, built with Symfony 6.4
and MySQL. It supports GET, POST, PUT and DELETE, Bearer authentication and two
roles (root and user).

## Running

```
docker compose up --build
```

The app is served on http://localhost:8000 and MySQL runs in the `db` container.
On startup it waits for the database, runs the migration and then serves.

The root Bearer token is configured via `ROOT_API_TOKEN` (default
`root-secret-token`). A regular user gets its own token in the response to the
create request.

## Endpoints

All requests go to `/v1/api/users` and require an `Authorization: Bearer <token>`
header.

| Method | Description | Request fields | Response |
|--------|-------------|----------------|----------|
| POST   | create user | login, phone, pass (JSON body)     | 201: id, login, phone, apiToken |
| GET    | read a user | id (query string)                  | 200: login, phone |
| PUT    | update user | id, login, phone, pass (JSON body) | 200: id |
| DELETE | delete user | id (query string)                  | 204: empty |

Roles:

- root can do anything with any user.
- user can read and update only its own record and cannot delete.

Errors are always returned as JSON (for example `{"error": "Access denied."}`)
without stack traces.

## Notes on the task

The task text has a couple of intentional mistakes around the password field:

- The response tables list `pass` for GET and POST. Returning a password, even a
  hashed one, is a security hole, so it is never returned. On creation the
  response returns the issued Bearer token instead.
- It asks for a unique composite index on `(login, pass)`. Once the password is
  hashed that index is pointless, because the salt makes every hash different, so
  uniqueness is kept on `login` only.

The Bearer token is stored in its own `api_token` table, so the `User` entity
keeps exactly the four attributes from the task (id, login, phone, pass).

## Sample data

`dump.sql` is a MySQL dump with five sample users. Load it into the running
database with:

```
docker compose exec -T db mysql -uroot -proot user_api < dump.sql
```

## Tests

```
docker compose run --rm php vendor/bin/codecept run
```
