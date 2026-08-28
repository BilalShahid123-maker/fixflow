# Demo runbook

A 6-minute walkthrough that tells the FixFlow story: tenant request → AI triage → human approval → dispatch, with the AI constraints on display.

## Before starting

```bash
php artisan migrate:fresh --seed   # clean state: admin, 3 requests, 3 contractors, 3 knowledge docs, 20 eval cases
php artisan serve                  # http://127.0.0.1:8000
# keep a worker running for real-queue behavior:
php artisan queue:work
```

Log in at `/admin` → `admin@fixflow.test` / `password` (seeded admin).

## Scene 1 — Public submission (45s)

1. Open the tenant portal: **`/`** — landing page explains the 4-step pipeline.
2. Click **"Report an issue"** (`/submit`). Pick Maple Court → unit 302, enter name/email, and paste a clear issue:
   > "There is constant water leaking under the kitchen sink. Cabinet is completely soaked, L-shaped crack in the drain pipe. Smells like mildew."
3. Submit. You're redirected to the **status page** showing a reference (e.g. `XRRXRURR`) and status *Pending triage*.

**Say:** *This is the tenant-facing door. The request is now on the queue — no human touched it yet.*

## Scene 2 — AI triage (60s)

1. Open `/admin` → **Maintenance Requests**.
2. Pick the newly submitted request. The **AI triage card** shows category `plumbing`, severity `high`, confidence ≈0.9x and an explanatory reasoning line, plus **RAG knowledge base citations** (e.g. *"water shutoff location"* from a knowledge doc).
3. Note the request auto-routed to **Triaged** (confidence above the `0.90` auto-route floor).

**Say:** *Category, severity, confidence and citation — all persisted to `ai_runs`. Nothing was allowed to happen yet.*

## Scene 3 — Human approval is unavoidable (90s)

1. Open the same request and run **Approve**.
2. Watch the status go to **Dispatching**, a **Work Order** is created with the matched `PlumbCo` contractor, estimated cost, and a dispatch audit entry.
3. Open **Work Orders** → the work order is in `draft`, cost and contractor visible; **Approve & Dispatch** (the human click) schedules PlumbCo.

**Say:** *The AI matched a verified contractor and estimated the job. But no contractor is contacted until a person clicks Approve & Dispatch. `PermissionGate` enforces this in code — the LLM can't override it.*

## Scene 4 — The confidence trap (60s)

Show how low-confidence requests are held back:

1. Open the request titled something vague (seeded smoke-detector fallback) — its confidence is below `auto_route_min`, so it sits in **Awaiting approval**.
2. Its triage card shows severity/category with lower confidence and the human-review badge.

**Say:** *When the model is unsure, the request waits for a person — never auto-flows.*

## Scene 5 — Audit trail (60s)

1. On any request open **Audit Log** relation manager (or view DB `audit_logs`).
2. Show the immutable rows: `triage.succeeded`, `approval.approved`, `dispatch.approved`, each with actor, event and JSON properties.

**Say:** *Every consequential action is append-only. Six weeks later you can answer "why was this done?" — the decision, the model's confidence, and who approved it are all here.*

## Scene 6 — Evaluation + MCP (optional, 90s)

1. On the dashboard, the **Eval Metrics** widget shows category accuracy, severity accuracy and critical recall for the last `eval:run`.
2. Run `php artisan eval:run --split=all` in a terminal for a fresh table.
3. Show the MCP server integration:
   ```bash
   echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-03-26"}}' \
     | php bin/fixflow-mcp
   ```
   Then open the client of your choice (Claude Desktop / Cursor) wired to `fixflow` and have an assistant submit and check a request.

## Security one-liner for the interview

> "The LLM classifies and recommends; it never sends anyone or spends money. All authority is enforced in PHP — the prompt output is treated as untrusted input, and every action is behind a deterministic gate plus an audit trail."