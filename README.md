# Acid Reflux Health Tracker API

Laravel 13 REST API for patient food, symptom, sleep/stress, insight, and recommendation tracking. Authentication uses Laravel Sanctum personal access tokens and all patient data is scoped through the authenticated user relationship.

## Setup

1. Copy `.env.example` to `.env` and set the MySQL connection values.
2. Set a strong `ADMIN_PASSWORD` before seeding outside local development.
3. Run `php artisan key:generate`, `php artisan migrate --seed`, then start the app with `php artisan serve`.

The seeded admin email defaults to `admin@example.com`. New registrations always receive the `patient` role.

## API

Authentication: `POST /api/register`, `POST /api/login`, and authenticated `POST /api/logout`.

Patients use the food, symptom, and sleep log endpoints, then read `/api/insights`, `/api/recommendations`, and `/api/history`. History and the paginated log endpoints accept optional `from` and `to` date parameters.

Admins use `/api/admin/users`, `/api/admin/analytics`, `/api/admin/logs`, and the food-category management endpoints. Send the token returned by login as `Authorization: Bearer <token>`.

## Web portal

The Blade frontend is available at `/`. It includes the patient login/register gateway, a Google sign-in button, responsive patient dashboard cards, quick food/symptom/sleep logging, timeline, trigger list, and insight summary. The dashboard uses the same API and insight service as the REST clients.

To enable Google OAuth, create a Google OAuth web client and register this callback URL exactly:

```text
http://localhost:8000/auth/google/callback
```

Then set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` in `.env`, run `php artisan config:clear`, and restart `php artisan serve`. Without these values, the email/password portal still works and the Google button shows a configuration message.

## Insight scoring

The weekly insight service considers symptom frequency and intensity, food-to-symptom associations within six hours, sleep duration/quality, and stress. It returns a low, medium, or high risk estimate and explicitly labels it as a tracking aid rather than a medical diagnosis.
