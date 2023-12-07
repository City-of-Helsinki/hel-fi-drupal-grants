import React from "react";
import { Select, Tooltip } from "hds-react";
import parse from 'html-react-parser';

function parsedObject(props) {
  const optionsArray = Object.entries(props.inputArray['#options']).map(function(arrayKey) {
    return {
      label: arrayKey[1],
      value: arrayKey[0]
    }
  })
  return optionsArray
}
const GrantsSelect = (props) => (
  <Select
    id={props.id}
    label={props.inputArray['#title']}
    required={props.inputArray['#required']}
    options={parsedObject(props)}
    tooltipText={props.inputArray['#help'] ? parse(props.inputArray['#help']) : null}
    helperText={props.inputArray['#description'] ? parse(props.inputArray['#description']) : null}
  />
);
export default GrantsSelect
