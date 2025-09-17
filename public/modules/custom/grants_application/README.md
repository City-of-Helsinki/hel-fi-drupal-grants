## Grants application

The new react-form implementation.



### Application configuration

Application configuration used to be located in module.settings.yml (grants_metadata.settings.yml or grants_application.settings.yml)
Now the module settings are located in application_configuration as json.

#### application_types.json

This is the list of all applications. When new application is created, add the new application data to the list.

#### application_configuration.json

The file contains data which is used in different places, for example in avus2 submission.



### REST - draft application

#### GET: /applications/{application_type_id}

Return an empty form data for new submission.

#### POST: /applications/{application_type_id}/{application_number}

Create the document and return application number and document id.

#### PATCH: /applications/{application_type_id}/{application_number}

Update existing document.



### REST - Sent application

#### GET: /applications/{application_type_id}/application/{application_number}

Get the application which has been saved as a draft or sent to Avus2.

#### POST: /applications/{application_type_id}/application/{application_number}

TBD (post and patch could be the same functionality I guess)

#### PATCH: /applications/{application_type_id}/application/{application_number}

TBD (post and patch could be the same functionality I guess)



### Controller

#### /application/{id}/render

Load the page which shows the react form. The first created form id is 58

#### UPLOAD FILE /application/{id}/upload

Upload a file to an existing application. Id is application number.

#### DELETE FILE /application/{id}/file/{file_id}/delete

Delete a file from an existing application. Id is application number.
The application must be a draft.



### Creating a new react-application form

#### Creating the react form
TBD

#### Mapping
TBD

#### Service page settings entity and fixtures

Service page settings entity (Application metadata) defines the configuration and settings for different types of grant applications. It stores essential information that controls how applications are processed and displayed.

##### Data Structure

Application types are defined in `form_configuration/form_types.json`.
Grants / Application industries, applicant types, application statuses and subvention types are defined in `form_configuration/form_configuration.json`.

The entity contains the following key fields:

- **Application Type**: The type of application (selected from a predefined list)
- **Application Name**: Human-readable name of the application (used in UI for admins)
- **Application Type Code**: Machine-readable code for the application type
- **Application Type ID**: Unique identifier for the application type
- **Grants Industry**: Industry/category the grant belongs to
- **Applicant Types**: Types of applicants eligible for this application (multiple selection)
- **Subvention Type**: Type of financial support provided (multiple selection)
- **Target Group**: Intended audience/beneficiaries of the grant
- **Application Period**: Open and close dates for applications
- **Continuous Application**: Whether applications are accepted on an ongoing basis
- **Disable Copying**: Option to prevent copying this application

##### Application Metadata Entity

All application metadata entities are listed in `/admin/tools/application-metadata`.

1. **Creating a New Application Metadata**
   - Navigate to `/admin/tools/application-metadata/add`
   - Select application type from the list
     - Application name, application type code and application type ID are autofilled based on the selected application type
   - Fill in the rest of the fields

##### Fixtures (Development/Testing Only)

Fixtures are automatically loaded in non-production environments, and they provide default values when no entity exists. If application metadata entity has been created for the application type, the fixture values are ignored. Fixtures are always ignored in production.

Place fixture files in `fixtures/{application_type}/`.
Required files:
 - `schema.json`: Form schema definition
 - `uiSchema.json`: UI configuration
 - `translation.json`: Translation strings
 - `settings.json`: Application settings

#### Translations
TBD
