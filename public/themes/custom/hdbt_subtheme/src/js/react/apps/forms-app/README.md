
# Forms App

This is an embedded React app for grants applications.

App is based on [react-jsonschema-form](https://rjsf-team.github.io/react-jsonschema-form/docs).

## Templates & widgets

## Custom additions

We have introduced some custom ways to configure the forms.

### Field types

* **atvFile** renders a fileinput. Has additional functionality for uploading files to ATV.
  * Fields using this need to be `object` type in `schema.json`.
  * Following structure is necessary for the fields (inside properties):
  ```
      "file_id": {
        "type": "integer"
      },
      "fileName": {
        "type": "string"
      },
      "fileType": {
        "type": "integer"
      },
      "integrationID": {
        "type": "string"
      },
      "isDeliveredLater": {
        "type": "boolean"
      },
      "isIncludedInOtherFile": {
        "type": "boolean"
      },
      "isNewAttachment": {
        "type": "boolean"
      },
      "size": {
        "type": "integer"
      }
  ```
* **textParagraph** used to render a simple text paragraph without input element. See `TextParagraph.tsx`.
* **computedSum** renders (or, with `ui:options.hidden`, just computes) a sum derived from other fields and writes it back into form data so it can drive conditional rendering. `ui:options`: `sourceFields` `string[]` (root-relative dot paths), `sourceShape` `'scalar' | 'subvention'`, `subventionType` `string` (with `sourceShape: 'subvention'`, sums only rows matching this subvention type code), `numericOutput` `boolean`, `hidden` `boolean`. Typically paired with `misc:exclude-from-submit`. Renders a read-only input by default, or the total as text with `misc:variant: summary` (see Variants). See `ComputedSum.tsx`.
* **sumMatchStatus** shows the result of the `misc:sum-match` rule declared on the same field: a success or error notification inside a `role="status"` live region. The rule's errors are attached to this field, and it receives the focus when moving to the next step fails. Use `"type": "null"` in `schema.json` and `misc:exclude-from-submit`. See `SumMatchStatus.tsx`.

### UiSchema

* **misc:file-type** `number` This is a value necessary for Avus2. It's passed during form submit and does nothing else. See `FileInput.tsx`.
* **misc:formats** `string[]` Narrows which file types an `atvFile` field accepts, e.g. `["pdf", "doc", "docx"]`. Values must come from `ALLOWED_FORMATS` in `FileInput.tsx`.
* **misc:max-length** `number` Determines max length for a textarea field. See `Input.tsx`.
* **misc:variant** `string` Determines a variant of a component to be rendered. See `TextParagraph.tsx`.
* **misc:sum-match** `object` Validates that the sum of `fields` equals the sum of `compareTo`, for example that the age groups and the gender distribution of participants add up to the same total. On mismatch the user can't move past the step or submit, but can still save a draft. The backend checks the rule on submit too, but only logs mismatches. Keys:
  * `compareTo` `string[]` root-relative dot paths, required.
  * `fields` `string[]` defaults to the field's computedSum `sourceFields`, or the field itself.
  * `message` `string` translation key for the error, defaults to `sum_match.mismatch`. `{{left}}` (sum of `fields`), `{{right}}` (sum of `compareTo`) and `{{difference}}` are interpolated.
  * `successMessage` `string` translation key for the success notification, defaults to `sum_match.match`.

  The rule is validated when moving to the next step, and when focus leaves the fieldset around `fields` or `compareTo` while both have a value. Incomplete input isn't flagged while the user is typing. Skipped while every field involved is empty. Declare the rule on a `sumMatchStatus` field placed after the compared fieldsets, and show the totals with `computedSum` summaries; they're colored by the result. Individual inputs aren't marked invalid, as it's unknown which number is wrong. Example:
  ```
  "participants_age": {
    "total": {
      "ui:field": "computedSum",
      "ui:options": { "sourceFields": ["step.participants.age.minors", "step.participants.age.adults"] },
      "misc:variant": "summary",
      "misc:exclude-from-submit": true
    }
  },
  "participants_gender": {
    "total": {
      "ui:field": "computedSum",
      "ui:options": { "sourceFields": ["step.participants.gender.women", "step.participants.gender.men"] },
      "misc:variant": "summary",
      "misc:exclude-from-submit": true
    }
  },
  "participants_status": {
    "ui:field": "sumMatchStatus",
    "misc:exclude-from-submit": true,
    "misc:sum-match": {
      "fields": ["step.participants.gender.women", "step.participants.gender.men"],
      "compareTo": ["step.participants.age.minors", "step.participants.age.adults"],
      "message": "participants.age_gender_mismatch",
      "successMessage": "participants.age_gender_match"
    }
  }
  ```
* **misc:exclude-from-submit** `boolean` Strips the field from the data sent to the backend on both submit and draft save. Used for synthetic helper fields (e.g. a `computedSum` that only exists to drive conditional rendering). See `FormWrapper.tsx`.

### Variants

Variants of components are determined with the custom `misc:variant` key in `UiSchema.json`. Our current variants are:

* TextParagraph
  * **infoBox** a highlighted version of text paragraph field.
* ComputedSum
  * **summary** the total as text instead of an input, with `@sum` in the field title replaced by the sum, e.g. `"Yhteensä @sum liikkujaa"`. The field description is shown below it. Colored green or red by the result of the sum match rule comparing its source fields.
* TextInput (default for string fields)
  * **width-s** max. 180px wide
  * **width-m** max. 282px wide
  * **width-l** max. 384px wide
  * **width-xl** max. 588px wide
  * **width-xxl** max. 792px wide

### ui:options additions

We've added some custom keys to ui:options.

* **addText** `string` Renders a custom text for 'add more' button (used in array fields). See `Templates.tsx`.
* **affirmativeExpands** `boolean` Prints an indication that affirmative answer opens another field. See `Templates.tsx`.
* **hideNameFromPint** `boolean` Hides field name when viewing a preview or submitted form. See `Templates.tsx`.
* **printableName** `string` Overrides the field name in preview or submitted form. See `Templates.tsx`.
* **removeText** `string` Renders a custom text for 'remove item' button (used in array fields). See `Templates.tsx`.

### Widgets

All custom widgets can be found in `Input.tsx`.
Basically all we're doing here is overriding default inputs with HDS components, with some minor adjustments for select fields that use data from Suomi.fi integration.

### Formats

Custom AJV formats enable declaring additional formats for data. See `RJSFormContainer.tsx` for examples.

One custom format has been defined for decimal numbers. It allows using decimal numbers with comma-separation. Use it by adding `format: decimal-number` to the appropriate `schema.json`
