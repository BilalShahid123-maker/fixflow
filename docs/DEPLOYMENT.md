# Deployment

Target: a single PHP application with a relational database and one worker process. Works on any modern host (Forge + VPS, Fly.io, Render, Railway) that can run PHP 8.2+ and Composer.

## Build the app

```bash
composer install --no-dev --prefer-dist
npm ci && npm run build   # only if you customize the Tailwind assets later
```

```
php artisan config:cache
php artisan route:cache
php artisan event:cache
php artisan view:cache
```

## Configure `.env` (production)

| Variable | Value | Notes |
|---|---|---|
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | |
| `APP_URL` | `https://fixflow.example.com` | must match the panel/redirects |
| `FIXFLOW_TRIAGE_DRIVER` | `llm` or `fake` | fake = deterministic, zero API cost |
| `ANTHROPIC_API_KEY` | `sk-ant-...` | needed only for `llm` driver |
| `FIXFLOW_LLM_MODEL` | `claude-sonnet-4-5` | tuned per provider |
| `QUEUE_CONNECTION` | `database` (default) | triage runs on the worker queue |
| `FIXFLOW_AUTO_ROUTE_MIN` | `0.90` | confidence threshold for auto-route |
| `FIXFLOW_AUTO_COST_LIMIT_CENTS` | `30000` | auto-execute cost ceiling per job |
| `FIXFLOW_EMBEDDING_DRIVER` | `fake` / `llm` | fake storage-compatible vectors, llm for semantic search |

The fake triage + fake embedding drivers are safe to run in production with **zero API keys**. The pipeline is deterministic, so behavior is fully predictable — useful for demos and load tests.

## Set up the database, users and RAG

```bash
php artisan migrate --force
php artisan db:seed --force          # creates admin@fixflow.test + demo data
php artisan knowledge:ingest --fresh # embed knowledge base (fake: instant, llm: API)
```

## Run the web + worker processes

Two long-running processes:

```bash
# Web:
php artisan serve --host=0.0.0.0 --port=80     # dev; use Nginx/FPM in production

# Queue worker (triage + retries):
php artisan queue:work --tries=3 --backoff=10
```

Expose the **queue worker** via a supervisor (`Queue::before` logs failures to `ai_runs`). If the worker is down, requests stay `pending_triage` in the DB — nothing is lost.

## Filesystem + scheduling

- `storage/` must be writable by both web and worker processes.
- No cron jobs are required in the base app (no schedulers defined).

## Maintenance

```bash
php artisan eval:run --split=eval     # refresh accuracy/severity/recall numbers anytime
php artisan tailwind:compile          # only after asset work
```

## Load / scale notes

- Current RAG is brute-force cosine over PHP arrays — fine to ~10k chunks. Move to `pgvector` before scaling the knowledge base.
- The queue is the only async-bound component; add workers horizontally for more throughput.