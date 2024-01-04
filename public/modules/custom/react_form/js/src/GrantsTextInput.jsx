import React, {useState} from "react";
import {TextArea, TextInput, Tooltip} from "hds-react";
import parse from 'html-react-parser';

function GrantsTextInput(props) {
  const [inputText, setInputText] = useState("");
  const [errorText, setErrorText] = useState('');
  const [characterLimit] = useState(props.inputArray['#counter_maximum'] ?? props.inputArray['#maxlength'] ?? null);
  // event handler
  const handleChange = event => {
    setInputText(event.target.value);
    props.updatedValueCallback(props.callbackKey, event.target.value)
    if (props.inputArray['#required'] || props.inputArray['#required'] === 'required') {
      if (event.target.value.length < 1) {
        setErrorText(Drupal.t('@name field is required.', {'@name': props.inputArray['#title'] ?? ''}));
      }
    }
  };
  return (
    <TextInput
      id={props.id}
      label={props.inputArray['#title']}
      required={props.inputArray['#required']}
      onChange={handleChange}
      errorText={errorText}
      maxLength={characterLimit ?? undefined}
      tooltipText={props.inputArray['#help'] ? parse(props.inputArray['#help']) : null}
      helperText={props.inputArray['#description'] ?
        parse(props.inputArray['#description']) :
        (props.inputArray['#counter_type'] ? inputText.length + '/' + characterLimit : null)}    />
  );
}
export default GrantsTextInput
