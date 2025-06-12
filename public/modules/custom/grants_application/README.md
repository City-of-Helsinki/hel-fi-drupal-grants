## Grants application

The new react-form implementation.


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

