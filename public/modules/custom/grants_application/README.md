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

#### Service page settings entity and file
TBD

#### Translations
TBD
