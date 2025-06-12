
# Forms App

This is an embedded React app for grants applications.

App is based heavily on https://rjsf-team.github.io/react-jsonschema-form/docs.

## Templates & widgets

## Custom additions

We have introduced some custom ways to configure the forms.

### Field types

### UiSchema

* **misc:file-type** `number` This is a value necessary for Avus2. It's passed during form submit and does nothing else. See `FileInput.tsx`.
* **misc:variant** `string` Determines a variant of a component to be rendered. See `TextParagraph.tsx`.

### ui:options additions

We've added some custom keys to ui:options.

* **addText** `string` Renders a custom text for 'add more' button (used in array fields). See `Templates.tsx`.
* **hideNameFromPint** `boolean` Hides field name when viewing a preview or submitted form. See `Templates.tsx`.
* **printableName** `string` Overrides the field name in preview or submitted form. See `Templates.tsx`.
* **removeText** `string` Renders a custom text for 'remove item' button (used in array fields). See `Templates.tsx`.
* **tooltipButtonLabel** `string` Button label for HDS tooltip feature. See `Input.tsx`.
* **tooltipLabel** `string` Label for HDS tooltip feature. See `Input.tsx`.
* **tooltipText** `string` Inner text for HDS tooltip feature. See `Input.tsx`.
