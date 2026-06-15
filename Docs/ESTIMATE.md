## Manual Estimate

I estimate this task will take 12 hours.

The implementation itself is not huge, but the workflow adds time because I need to create the project from scratch, configure Sanctum and SQLite, write planning documents, make schema decisions around slug and soft deletes, write feature tests, and capture terminal output for BEFORE-AFTER.md.

## Task Breakdown

| Task | Estimate |
|---|---:|
| Read brief and workflow | 15 min | I need to carefully read the project brief and intern-scope/WORKFLOW.md before starting, because the workflow controls the order of documents, commits, and implementation.

| UNDERSTANDING.md |  60 min | This needs a careful restatement of the task, inputs, outputs, assumptions, and ambiguities. The slug and delete behavior need special attention.

| ESTIMATE.md manual quote | 20 min | I need to create my own estimate before using AI, including a breakdown of setup, documentation, coding, testing, and evidence collection.

| AI quote and reconciliation | 15 min | After getting an AI estimate, I need to compare it against my manual estimate and explain any differences.

| APPROACH.md | 60 min | This needs schema decisions, route design, validation approach, search behavior, slug handling, soft delete decisions, and edge cases.

| Laravel setup, Sanctum, SQLite | 60 min | The project starts from nothing, so this includes creating the Laravel app, installing Sanctum, publishing Sanctum files, configuring SQLite, and checking the app runs.

| Migration, model, slug, soft deletes | 60 min | The exercise schema is mostly simple, but I need to decide and implement slug and likely soft deletes carefully, including database constraints.

| FormRequest validation | 30 min | Validation must be handled in a FormRequest, including unique name rules and ignoring the current record on update.

| Controller and routes | 75 min | I need to build the CRUD controller methods, return the right status codes, use route model binding, and protect all routes with auth:sanctum.

| Search and pagination | 35 min | The list endpoint must paginate 20 per page and support ?search= filtering across the decided fields.

| Feature tests | 120 min | Tests need to cover authentication, list pagination, search, create, update, delete, validation errors, duplicate names, and deleted exercise behavior.

| Debugging and test fixes | 75 min | Some test failures are likely while wiring Sanctum, validation, route model binding, soft deletes, and JSON assertions.

| BEFORE-AFTER.md terminal evidence | 40 min | I need to paste useful terminal output showing the project state before and after, including migrations/tests passing.

| Final acceptance checklist review | 30 min | I need to compare the finished work against every acceptance criterion before saying the task is complete.

## Total

Estimated total: 11 hours 30 mins.

## Notes

The highest-risk parts are:

1. Deciding whether to include the slug field even though it is missing from the formal schema table.
2. Deciding whether to use soft deletes because the acceptance criteria and explain section imply deleted records should be tracked.
3. Making sure Sanctum authentication is correctly tested.
4. Making sure validation uses a FormRequest instead of inline controller validation.
5. Writing enough feature tests to prove authentication, validation, CRUD, search, pagination, and deleted-record behavior.