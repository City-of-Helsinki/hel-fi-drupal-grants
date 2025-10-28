
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

### UiSchema

* **misc:file-type** `number` This is a value necessary for Avus2. It's passed during form submit and does nothing else. See `FileInput.tsx`.
* **misc:max-length** `number` Determines max length for a textarea field. See `Input.tsx`.
* **misc:variant** `string` Determines a variant of a component to be rendered. See `TextParagraph.tsx`.

### Variants

Variants of components are determined with the custom `misc:variant` key in `UiSchema.json`. Our current variants are:

* TextParagraph
  * **infoBox** a highlighted version of text paragraph field.
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
* **tooltipButtonLabel** `string` Button label for HDS tooltip feature. See `Input.tsx`.
* **tooltipLabel** `string` Label for HDS tooltip feature. See `Input.tsx`.
* **tooltipText** `string` Inner text for HDS tooltip feature. See `Input.tsx`.

### Widgets

All custom widgets can be found in `Input.tsx`.
Basically all we're doing here is overriding default inputs with HDS components, with some minor adjustments for select fields that use data from Suomi.fi integration.
