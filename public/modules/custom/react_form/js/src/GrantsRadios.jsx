import React from "react";
import { SelectionGroup, RadioButton } from 'hds-react';
import {useState} from "react";
import parse from "html-react-parser";

const GrantsRadios = (props) => {
  const [selectedItem, setSelectedItem] = useState("0");
  const onChange = (event) => {
    setSelectedItem(event.target.value);
  };
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
          onChange={onChange}
        />
      )
      )}
    </SelectionGroup>
  );
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
