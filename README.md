# FixFlow

AI-assisted maintenance triage and dispatch for property managers, built with Laravel.

A property manager with 200 units receives tenant requests all day: "water leaking under the sink", "smoke coming from the outlet", "AC stopped working". Triage is manual, slow and inconsistent. FixFlow classifies each request (category, severity, confidence), routes low-confidence cases to human review, and prepares contractor dispatches that a human approves before anything executes.

**The AI never sends anyone or spends money on its own.**

## Problem statement

Small and mid-size property managers handle maintenance through phone calls, texts and spreadsheets:

- Urgent issues sit in an inbox next to cosmetic ones
- Contractors get dispatched without cost context
- Nobody can answer "why was this decision made?" six weeks later
- Tenants re-explain the same problem three times to three people

FixFlow turns that into a single auditable pipeline:

```
Tenant request ─▶ Queued triage agent ─▶ Structured result + confidence
                                              │
                    confidence ≥ auto-route min ─▶ Triaged (ready for dispatch prep)
                    confidence < auto-route min ─▶ AwaitingApproval (human review queue)
```

Every run persists input, output, latency and cost (`ai_runs`); every consequential action passes a deterministic permission gate (`PermissionGate`) and lands in an immutable audit trail (`audit_logs`).

## Bounded agent authority (the core design idea)

LLM output is treated as **untrusted input**. Authority levels are enforced in PHP — never in the prompt:

| Level | Name | Agent may | Enforced by |
|---|---|---|---|
| 0 | Read | read requests, search knowledge base | Policies |
| 1 | Recommend | classify, estimate cost, draft messages | `TriageAgent` contract returns DTOs only |
| 2 | Prepare | create draft work orders / dispatch proposals | `PermissionGate::evaluate()` |
| 3 | Execute | schedule, send, spend money | auto-approval rules, else human approval |

Auto-execution requires **all** of: confidence ≥ `auto_route_min`, estimated cost ≤ `auto_execute_cost_limit_cents`, contractor verified, severity < critical. Any failure → `NeedsApproval` with explicit reasons attached to the decision.

## Architecture

```
app/
├── AI/
│   ├── Agents/FakeTriageAgent.php    deterministic driver, zero API keys needed
│   ├── Contracts/TriageAgent.php     swap in a real LLM driver behind one interface
│   ├── Dto/                          TriageResult, DispatchProposal (readonly)
│   ├── Permissions/                  PermissionGate + PermissionDecision
│   └── RAG/                          TextSplitter, EmbeddingService, VectorSearch,
│                                     KnowledgeIngestion (fake + Prism LLM drivers)
├── Enums/                            RequestStatus, Severity, IssueCategory,
│                                     ActionStatus, ApprovalStatus, AuthorityLevel
├── Jobs/ProcessMaintenanceRequest.php queued triage with retries + idempotency
└── Models/                           Property, Unit, Tenant, MaintenanceRequest,
                                      WorkOrder, Contractor*, Knowledge*,
                                      AiRun, AiAction, Approval, AuditLog
config/fixflow.php                     thresholds live here, not in prompts
```

Key decisions baked into the skeleton:

- **Idempotent jobs** — `ShouldBeUnique` per request + status guard, so a retried job can never double-triage
- **Failure visibility** — exhausted retries flip the run to failed *and* write `ai.triage.exhausted_retries` to the audit log; the request stays pending for reprocessing
- **Cost telemetry from day one** — every `ai_run` stores tokens, latency and cost so the eval dashboard later has real data

## Testing

The pipeline runs entirely offline via the fake driver:

```bash
php artisan test
```

Covers: automatic routing of confident triages, human-review routing of vague ones, retry idempotency, all four permission-gate branches, knowledge chunking/embedding/search, vector-search top-k + min-score filtering, review action audit trails, and end-to-end RAG citation retrieval.

## Evaluation

A labeled eval set + metrics harness proves how well the triage agent actually performs — the key portfolio metric.

```bash
php artisan eval:run --split=all
```

Every case is prefixed with a human-labeled ground truth (category, severity, emergency). The runner drives the active `TriageAgent` over the set, records each prediction against an `ai_run`, and reports:

- **Category accuracy** — exact trade-category match rate
- **Severity accuracy** — exact urgency match rate
- **Critical recall** — % of true emergencies the agent correctly flags (the safety-critical number)
- **Average confidence** — calibration signal

Baseline with the deterministic fake driver over the curated 20-case set: ~75% category, ~65% severity, 100% critical recall. Swap to the LLM driver (`FIXFLOW_TRIAGE_DRIVER=llm`) to measure the real model; results land in `eval_runs` and show on the dashboard's `EvalMetricsWidget`.

## Security

The core posture: **LLM output and tenant input are both untrusted.**

- **Prompt injection** — tenant title/description are wrapped in `<<<UNTRUSTED_TENANT_INPUT>>>` delimiters by `PromptSafety::encloseUntrusted()` and marked as data, never instructions. Even if the model is coaxed into misclassifying, the `PermissionGate` still enforces authority in deterministic PHP, so no injected instruction can grant permission.
- **Authorization** — a `MaintenanceRequestPolicy` gates review/dispatch to admins (`is_admin`). Strict Eloquent mode is enabled in dev/test so accidental mass assignment throws instead of silently dropping.
- **Untrusted persistence** — `PromptSafety::sanitizeForStorage()` strips null bytes and control characters before text is stored/displayed.

## Failure modes

| Failure | Behavior | Recovers by |
|---|---|---|
| LLM call times out / errors | `AiRun` marked `failed`, audit `ai.triage.failed` written | `ProcessMaintenanceRequest` retries (3, backoff 10/30s); exhausted → audit `ai.triage.exhausted_retries`, request stays `PendingTriage` |
| Duplicate processing / retry | job is `ShouldBeUnique` + status guard → safe to retry | no double-triage; idempotent |
| LLM returns invalid enum | `tryFrom` throws; run fails and retries | same retry path |
| LLM overclaims confidence | clamped to ≤0.97 in code | safer thresholds |
| Injection phrase in tenant text | delimited + gate still enforced | blocked regardless of model behavior |
| Unknown triage driver | `AppServiceProvider` throws clear exception at boot | misconfig fails fast, no silent fallback |
| No matching contractor | `dispatch.match_failed` audited, request stays `Triaged` | manager can add/verify a contractor and retry |
| Non-admin action | `MaintenanceRequestPolicy` denies | 403 |

## Roadmap

- [x] Week 1–2 — domain schema, models, enums, queued triage pipeline, permission gate
- [x] Week 3 — real LLM driver behind the `TriageAgent` contract (Prism PHP, structured output + confidence clamping)
- [x] Week 4 — manager dashboard (Filament): review queue, AI triage card, approve/reject with audit trail
- [x] Week 5 — knowledge base + embeddings + RAG answers with citations
- [x] Week 6–7 — contractor matching tools + work order dispatch flow
- [x] Week 8 — evaluation suite: labeled dataset, accuracy/severity/critical-recall metrics
- [x] Week 9 — security pass, failure-mode documentation, cost dashboard
- [ ] Week 10 — deployment, demo video, MCP server exposure

## Decision log

| # | Decision | Why |
|---|---|---|
| 1 | Deterministic fake triage driver first, LLM second | The whole pipeline is testable in CI without keys; the LLM becomes a swappable implementation detail |
| 2 | Permission levels enforced in PHP, not prompts | Prompt-based guardrails are suggestions; code-based ones are guarantees |
| 3 | Confidence threshold routing instead of binary classify | Mirrors how a real team works: sure things flow, unsure things wait for a human |
| 4 | SQLite for dev, schema kept portable | Zero-setup local dev; no vendor-specific column types yet |
| 5 | Audit log as append-only table without updated_at | "Why did the system do this?" must have one truthful answer |
| 6 | Laravel over Python for the app layer | The product is a transactional SaaS (auth, workflows, queues, consistency) where Laravel is strong; AI is one component of it |
| 7 | Prism PHP as the LLM layer | Official `laravel/ai` SDK requires newer PHP than this environment; Prism is battle-tested, provider-swappable, and its structured-output API maps cleanly onto our `TriageAgent` DTO contract |
| 8 | LLM output defensively parsed (enum `tryFrom`, confidence clamped to ≤0.97) | The model can hallucinate enum values or overclaim certainty; the driver treats model output like untrusted input |
| 9 | Token usage flows through `TriageResult->meta`, cost estimated per million-token rates in config | Cost telemetry belongs in `ai_runs` from day one, not retrofitted later |
| 10 | Filament pinned to ^3.3 | v3 APIs are stable and fully documented; review actions delegate to framework-agnostic `ApproveReview`/`RejectReview` classes so business rules stay testable without Filament |
| 11 | RAG: brute-force cosine similarity over PHP arrays, embeddings swapped per driver (fake/LLM) | Fast enough for <10k chunks; pgvector when scaling |
| 12 | Fake embeddings produce deterministic vectors (md5-based) so tests are reproducible without API keys | Only the LLM embedding driver produces semantically meaningful results |
| 13 | MatchContractor returns a MatchResult DTO (not a raw model) so downstream actions get service, slot and score in one query | Avoids N+1 lookups in the dispatch flow |
| 14 | WorkOrder approval is always human (Filament action) — the AI creates a draft, a person dispatches | No contractor is ever contacted without explicit approval |
| 15 | Eval cases are a separate labeled table (human ground truth), evaluated against `ai_runs` predictions | Keeps eval data independent of production requests and lets the split (train/eval/holdout) be managed in SQL |
| 16 | Strict Eloquent mode enabled in dev/test | Forces mass-assignment violations to throw during development rather than silently stripping fields |
| 17 | Prompt injection handled at two layers — delimiting untrusted input AND an independent PHP permission gate | Defense in depth: the model is only as smart as the prompt, but authority is guaranteed by code |

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed     # seeds admin, knowledge docs (embedded via fake driver), and triaged demo requests
php artisan serve
```

Log in at `/admin` with `admin@fixflow.test` / `password`. Requests are triaged by the deterministic fake driver by default; set `FIXFLOW_TRIAGE_DRIVER=llm` plus an `ANTHROPIC_API_KEY` to switch to Claude via Prism. Run the worker with `php artisan queue:work` when not using the sync driver.

To re-embed the knowledge base with a real embedding provider: `FIXFLOW_EMBEDDING_DRIVER=llm php artisan knowledge:ingest --fresh`.

## License

MIT
