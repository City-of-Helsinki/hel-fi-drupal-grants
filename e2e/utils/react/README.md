# React form tests

Shared engine that runs end-to-end tests for the React grant application forms.
One `executeFormFlow()` call drives the whole flow for a form.

## What the flow does

For a form, `executeFormFlow()` runs these steps in order:

1. **Translations** — open the form in fi/en/sv and check every label, description, and tooltip.
2. **Fill** — fill every field with a valid value in Finnish, then save as a draft.
3. **Preview** — open the summary in fi/en/sv and check that the filled values are shown.
4. **Draft list** — confirm that the draft appears in `oma-asiointi`.
5. **Submit** — accept the terms, submit, and wait for the completion page.
6. **Sent list** — confirm that the application appears in the sent list.
7. **Sent values** — open the sent application and confirm that the stored values match.
8. **Modify** — if the application reached the received state, edit a field and confirm that it persists.
9. **Secondary roles** — for every other `applicant_types` value the form supports, confirm that the role can open and draft the form.

The primary role is `REGISTERED_COMMUNITY` by default.

## Add a test for a form

1. Create a folder: `e2e/tests/forms/{application_type_id}_{form_identifier}/`.
   `FORM_ID` is the folder name with the leading `{id}_` stripped.
2. Copy `form.spec.ts` from an existing form folder. It needs no changes.
3. Add the form test to `playwright.config.ts`. See, for example: `name: 'forms-react'` and `name: 'forms-29-yleisavustushakemus'`.

## Per-form customization

Two optional files live next to `form.spec.ts` and are ignored as test files:

* **`formLogic.ts`** — custom handling for fields the generic engine cannot drive on its own.
  Keyed by field path `step → section → field`, the handler returns `true` when it has handled
  the field. Use it for true one-offs, for example, selecting a specific radio option that opens
  more fields. Pass it as the third argument: `executeFormFlow(page, FORM_ID, formLogic)`.
* **`formInputs.ts`** — replace the auto-generated value for specific fields. Uses the same nesting, with a string or a function as the field value.

Recurring patterns belong in the engine, not in `formLogic`.

## How fields are detected and filled

The engine reads the field type from the rendered DOM, not from the schema name. Each field
wrapper carries a `hdbt-form--field--<token>` class, for example `date`, `decimal-number`,
`select`, or `atv-file`. `handleField` picks the fill strategy from that token, so the React form
app is the source of truth. A field with no recognized token will produce an error.

Filled values are stored in a `filledFields` map and re-checked in the preview, the other
languages, and the "sent" application view.

## Running and debugging

* Run one form: `npx playwright test --project forms-70-iakkaiden`.
* `APP_DEBUG=TRUE` prints progress lines per language, step, and field.
* The local reporter is `list`, so the running test and its timer are shown live.
* `SKIP_SUBMIT=true` fills and drafts the form but skips submit and the post-submit steps, and
  deletes the draft afterwards. Use it to iterate quickly on the fill and translation steps.

To run a test with a non-registered primary role, pass it last:
`executeFormFlow(page, FORM_ID, undefined, undefined, 'PRIVATE_PERSON')`.

When a form fails, the message usually points to one field. Common causes:

* **Field not filled or wrong value** — the field needs a specific value or option. Add a  `formInputs.ts` value, or a `formLogic.ts` handler if it needs interaction.
* **Field not found / not visible** — it is conditional and its reveal was missed, or it is a new widget without a `hdbt-form--field--` class. Check the class in the running browser.
* **A field is filled with the wrong type** — the type token is missing or wrong on that widget.
* **Preview or sent value mismatch** — the value was truncated by a `misc:max-length` limit, or a select shows a translated label instead of the entered value.
* **Submit never reaches "Vastaanotettu"** — this is a backend or network issue, not a test issue. A single form is tolerated, but the teardown fails the run if every submitted form fails to be received. See: [./receivedStatus.ts](receivedStatus.ts)

## File reference

* **`formFlow.ts`** — entry point. `executeFormFlow()` orchestrates the whole flow.
* **`formFieldVerifier.ts`** — the engine: translation, fill, preview, submit, sent checks, modify and the `handleField` per-field logic.
* **`stepInspector.ts`** — turns the form schema into a `step → section → field` tree, including conditional and nested fields.
* **`schemaFetcher.ts`** — fetches the form schema, translations, and settings from the API and injects the applicant info and submit steps.
* **`applicantInfoStep.ts`** — fill and verify logic for the applicant info step.
* **`receivedStatus.ts`** — records the received state per form and reports it in teardown.
* **`utils.ts`** — navigation, waits, draft and `oma-asiointi` helpers and the translator.
* **`fieldFillers.ts`** — small helpers for dates and HDS dropdowns.
