# MAPPING

## How to map an existing form to Avus2-format

Optimally at this point, the react-form should already been created and you should be able to fill it.

### Start with the old form

You have two ways to start:
- Either check out the correct example-json -files under conf/examples
- Or fill and save the correct webform-application and take out the mapped json out of the ATV-document

The second option is usually better since filling the actual form can reveal some unexpected behaviours that must
be taken into account.

### Next, fill the new react-form and take the form_data out of it

Once you have both target data and source data, you can create new mapping

- Add a new file to the mappings folder, name it ID{form_type_id}.json
- Start creating new field mappings based on the old webform-submission (or example-json)
- Add all the field in the map
- Run the mapper
  - TODO a good way test mapping (maybe a unit tests)
- Check that the newly mapped data is similar to the example-json (or old webform submission)


### Mappings explained

#### Example mapping for the most common mapping case

The 'default' -mapping_type is the most usual case:
- Take one value from the react-form (source) and add it under 'value'-key in a predefined data-object format.
- Then write the data-object into the Avus2-format (target-json).

```
{
  "json-path.to.target_data.location": {      // Json-path to the target data.
    "#tip": "developer note"       // Not used by mapper.
    "skip": true                   // Allow the mapper to skip, dev purposes.
    "data_source": "form_data"     // Where the data is read from, the actual react-form or elsewhere.
    "source": "source.path"        // Json-path to the source data.
    "mapping_type": "default"      // How to add the data to target json.
    "data": {                      // Decided by Avus2: The 'data' can be found from the example-json.
      "ID": "avus2_id",            // Decided by Avus2.
      "label": "label for the ID", // Sometimes relevant data, usually not. Impossible to know when it is actually required.
      "value": "abc123"            // This is read from datasource.
      "valueType": "string"        // Decided by avus2.
    }
  }
}
```

#### The target -json-path is the key for a single field's mapping.
TIP: If you use PhpStorm, you can get the path from the target-json by using the `copy JSON pointer` -feature

You create the target and source -json-paths in same way. Combine the array keys into a comma-separated string.

Check out the target-json and create the json-path to the target data location.

For example, Avus2 requires that user gives an address and the email address must be set in certain place on the avus2-document:

`compensation.applicantInfoArray.8`

in PHP, the target path^ would look like this:

`$array['compensation]['applicantInfoArray'][8]`


#### Data sources
One application submission usually contains data from multiple data sources.
A datasource is nothing more than an array of data.

```
form_data             : The react-form
company               : The company user is mandated to act for.
user_profile          : The user profile
grants_profile_array  : Granst profile aka. hakuprofiili
form_settings         : The actual form settings
custom                : You can add anything to the custom datasource
```


#### Source

`source` is the json-path to the source-data (similar to the target -json-path).

Special case:
When mapping_type is empty or hardcoded, the source can just be "empty" or "hardcoded" since the data is not used anywhere.

TODO refactor the code that we can get rid of this.

#### Mapping type
Mapping type decides how to process the given value before adding it to the source data

```
default          : The normal mapping for most of the form fields
multiple_values  : The "add new item" -field, where user may add new item n-times.
hardcoded        : Read the first key from the data-section and add it to target data as it is
simple           : Simple key-value added to the target data
custom           : Allows creating and using custom mapper function to process the data
empty            : Add an empty object to the data.
```

#### Custom_handler when custom mapping-type is selected
Which custom handler to use if selected custom mapping type

```
valueAndLabel  : Add first value as label, second as value
income         : Map income-field
```

#### Data

Data -object is predefined in example-json.
In most cases, (when mapping type is default) only the value is overridden by mapper

TODO write about the special cases (mapping_type = simple, empty, hardcoded, multiple_values)


