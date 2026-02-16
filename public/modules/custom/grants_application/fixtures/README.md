# Fixtures folder

The fixtures folder contains everything that is needed for react forms.

## Folder naming
- The folder name must match the `form_identifier`
  - Form identifiers for existing forms can be found from `form_configuration/form_types.json`
  - New forms must always be added to `form_configuration/form_types.json` before you can create the definitions
  - Form identifiers must be unique, since the application type ids (the numeric ids) are not

### schema.json
- Contains react rjsf-library schema definition. [Rjsf-documentation](https://rjsf-team.github.io/react-jsonschema-form/docs/)

### uischema.json
- Contains react rjsf-library UI-schema definition

### translation.json
- Contains form-specific translations

### settings.json
- Contains form settings that are used on local/dev/test environments
- On local/dev/test, the settings.json can be overridden by creating an Application metadata-entity
- Settings.json won't be used on production

### Default translations
- Contains translations used in all forms

