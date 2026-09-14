# iss_api.php — REST API for the mobile app

Drop beside `dashboard.php`. Requires `dash_data.php`; adds no queries of its own.

## Resources

| Method | Path | Returns |
|---|---|---|
| GET | `/health` | liveness, which tables resolved, coverage |
| GET | `/days/{date}` | the dashboard payload in one call |
| GET | `/days/{date}/trains` | fleet, one entry per index |
| GET | `/days/{date}/incidents` | the day's register |
| GET | `/days/{date}/trends` | breakdowns, monthly series, sparklines |
| GET | `/incidents/{id}` | one incident, full record + annotations |
| GET | `/incidents/{id}/annotations` | notes and acknowledgements |
| POST | `/incidents/{id}/annotations` | add one — **the only write** |

`{date}` is `YYYY-MM-DD` or the literal `today`.

## Why not full CRUD

No PUT, no DELETE, and no POST on incidents or trains. Those records are encoded on
the console by the controller who witnessed the event. A CCDR entry that can be
rewritten from a handset hours later is not evidence of anything — so the app appends
annotations and touches nothing else.

That also keeps a question from arising: what happens when the phone and the console
disagree about the same field. Today it cannot.

Annotations live in their own `iss_annotation` table, created on first use. Nothing
the console owns is written.

## Calling it

    BASE=https://your-host/iss_api.php

    curl "$BASE/health"
    curl "$BASE/days/today"
    curl "$BASE/days/2026-08-13/incidents"
    curl "$BASE/incidents/1482"

    curl -X POST "$BASE/incidents/1482/annotations" \
         -H 'Content-Type: application/json' \
         -H 'X-ISS-Token: your-token' \
         -d '{"kind":"note","author":"R. Villamor","body":"Relief compo requested."}'

If your server does not pass `PATH_INFO`, use `?p=` instead:

    curl "$BASE?p=days/today/incidents"

With mod_rewrite you can lose the filename:

    RewriteRule ^api/(.*)$ iss_api.php/$1 [QSA,L]

## meta.ok — read this before trusting a zero

Every response carries `meta`. `meta.ok = false` means the read **failed**.

Without that flag the app cannot tell "no incidents today" from "the database is
unreachable", and would render a calm empty dashboard during an outage. When `ok` is
false the app shows its stale banner and keeps the last known values.

## Auth

`ISS_API_TOKEN` is empty by default — **no auth**. Do not leave it that way on a
reachable host. Set it at the top of the file, or in `db_config.php` before the
require. The app sends `X-ISS-Token`; `Authorization: Bearer` also works.

To reuse the console login instead, delete the `iss_authenticate()` call —
`session_start()` already runs inside `dash_data.php`.

Set `ISS_API_WRITES` to `false` for a strictly read-only deployment.

## Three things I could not settle from the source

1. **`station` / `direction` / `reported_by`.** `dash_incident_where()` probes for
   `station`/`location`/`area` and `direction`, so they may not exist. The API returns
   `where` as a possibly-empty string. Run `/days/today/incidents`: if `where` is empty
   everywhere, the app's incident rows need redesigning — that string is currently
   their primary line.

2. **`incident_report` vs `incident_union`.** `car_history.php` reads `incident_union`;
   this data layer reads `incident_report`. Same records, a view, or two registers? If
   the dashboard and the car history ever disagree about a day, this is why.

3. **Reachability.** The console is `localhost` to the web server. The phone needs a
   routable name or IP, and HTTPS if it leaves the building.

## Ops endpoints (new)

Two changes, both additive — an existing build keeps working against them untouched.

1. **Drop in `iss_api_ops.php`** beside `iss_api.php`. Nothing else to do: `iss_api.php`
   already includes it if present, and degrades to the old payload if absent.
2. **Deploy the patched `iss_api.php`.**

New routes:

    GET /days/{date}/trends[?g=week|month]   severity grid, AM/PM, loops, LRV, trend
    GET /days/{date}/insertions              the day's insertion log

New field on every train in `/trains`: `skipping` (bool).

### What is guarded

Every aggregate probes its tables AND its columns before running
(`timetable_day`, `train_compo`, `incident_report.level_condition`). A station whose
schema predates any of them gets `available: false` plus a reason string — never a
fatal, and never a zero. `/health` now reports all three so you can see which
station can answer which question.

### Reserve is boundary

`state` keeps the machine value `boundary` — it is the join key with
`dash_data.php`, and renaming it would silently break every client's switch.
`state_label` says **"Reserve"**, which is what operations calls those trains and
what the console dashboard shows. One state, two names, decided once in
`iss_state_label()`.

`counts.reserve` is that count, sent explicitly so no client derives it. The mobile
app previously inferred Reserve from an `index_type` field this payload has never
carried, so the tile read 0 every day — a confident number for a question that was
never asked. `counts.skipping` is sent for the same reason.

Both are derived from the same `dash_trains()` pass that builds `strip`, so the
tiles and the strip cannot disagree.

### Skipping is read, not derived

Operations encodes a skipped departure as a row in the `skipping` table, and
`dash_is_skipping()` in the console now returns simply whether such a row exists.
`dash_trains()` carries the count as `skip`. This API reports it and derives
nothing.

That replaced two earlier attempts to infer skipping from the insertion point. The
second looked correct and was not: insertion point answers *where a set entered the
loop*, which is a different question from *whether it skipped part of one*. A set
can enter at North and still skip; a planned short working from Quezon has skipped
nothing.

Three fields now travel:

- `skipping` — **trains** with at least one logged skip
- `skipping_events` — **departures** skipped (>= the above; one train can skip
  several times). The console's tile shows this figure under a label reading
  "Skipping Trains"; keeping them separate lets the app agree with that number
  without repeating the mislabel.
- `skipping_available` — false when the server predates the `skipping` table. The
  app renders an em dash, not a 0 — an absent source must read as unknown.

Per-train rows also carry `skip_count`, so the app can say "Skipped ×3" rather than
flattening one skip and six into the same badge.

### Superseded: skipping tested on the insertion

`inserted_to` holds the raw form code `north` / `quezon` — `dash_data.php` maps
those to display labels only inside `dash_recent_insertions()`; `dash_trains()`
returns the code untouched. The old test compared that code against the display
label `"North Ave."`, so **every** inserted set came back skipping. A
`state=='online'` gate on the counts cancelled most of that out — and dropped the
genuine cases: a set inserted at Quezon and later removed has state `removed`, so
it was never counted despite demonstrably having skipped.

Now: positive test for `quezon`, no state gate. Skipping is a fact about what
already happened, so the count no longer depends on when you look. An unrecognised
code reads as *not* skipping — inventing a skip is worse than missing one, since the
badge is an accusation about service that was not delivered.

The app matches: `Train.isSkipping()` is state-independent, and a removed set that
skipped reads "Removed · skipped" rather than losing one of the two facts.

### Two deliberate non-fixes

**The noon boundary.** AM ends `12:00:00`, PM starts `12:00:01` — `ccdr_summary.php`'s
verbatim windows, one-second gap included. A cancellation logged exactly at noon must
land in the same half-day here as on the printout.

**No per-car loop counts.** `train_compo` records which cars made up a set, not how
many loops each ran. The endpoint returns the count of cars utilised and the app says
the per-car split is not recorded, rather than dividing the fleet total and inventing
a figure.
