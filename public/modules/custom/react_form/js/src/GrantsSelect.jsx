import React, {useState} from "react";
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
  const GrantsSelect = (props) => {
    const [inputValue, setInputValue] = useState("");
    const [errorText, setErrorText] = useState('');
    const handleChange = event => {
      setInputValue(event.value);
      props.updatedValueCallback(props.callbackKey, event.value)
      if (props.inputArray['#required'] || props.inputArray['#required'] === 'required') {
        if (event.value.length < 1) {
          setErrorText(Drupal.t('@name field is required.', {'@name': props.inputArray['#title'] ?? ''}));
        }
      }
    };
    return (<Select
        id={props.id}
        label={props.inputArray['#title']}
        required={props.inputArray['#required']}
        onChange={handleChange}
        errorText={errorText}
        options={parsedObject(props)}
        tooltipText={props.inputArray['#help'] ? parse(props.inputArray['#help']) : null}
        helperText={props.inputArray['#description'] ? parse(props.inputArray['#description']) : null}
      />
    );
  }
  export default GrantsSelect
