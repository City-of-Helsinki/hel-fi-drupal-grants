import React from "react";
import { SelectionGroup, RadioButton } from 'hds-react';
import {useState} from "react";
import parse from "html-react-parser";

const GrantsRadios = (props) => {
  const [selectedItem, setSelectedItem] = useState("0");

  const [errorText, setErrorText] = useState('');
  const handleChange = event => {
    setSelectedItem(event.target.value);
    props.updatedValueCallback(props.callbackKey, event.target.value)
    if (props.inputArray['#required'] || props.inputArray['#required'] === 'required') {
      if (event.target.value.length < 1) {
        setErrorText(Drupal.t('@name field is required.', {'@name': props.inputArray['#title'] ?? ''}));
      } else {
        setErrorText('');
      }
    }
  };
  if (props.preview === true) {
    return (
      <dl key={props.id + "_group"}>
        <dt>{props.inputArray['#title']}</dt>
        <dd>{props.inputArray['#options'][props.inputArray['#value']]}</dd>
      </dl>
    )
  } else {
    return (
      <SelectionGroup
        key={props.id + "_group"}
        id={props.id + "_group"}
        label={props.inputArray['#title']}
        tooltipText={props.inputArray['#help'] ? parse(props.inputArray['#help']) : null}
      >

        {parsedObject(props).map((objectArray, index)=> (
            <RadioButton
              id={props.id+ (index + 1)}
              key={props.id+ (index + 1)}
              name={props.id}
              value={objectArray['value']}
              label={objectArray['label']}
              checked={parseInt(selectedItem) === parseInt(index)}
              onChange={handleChange}
            />
          )
        )}
      </SelectionGroup>
    );
  }

}
function parsedObject(props) {
  const optionsArray = Object.entries(props.inputArray['#options']).map(function(arrayKey) {
    return {
      label: arrayKey[1],
      value: arrayKey[0]
    }
  })
  return optionsArray
}
export default GrantsRadios
