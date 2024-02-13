import React, {useState} from "react";
import { TextArea, Tooltip } from "hds-react";
import parse from 'html-react-parser';

function GrantsTextArea(props) {
  const [inputText, setInputText] = useState("");
  const [errorText, setErrorText] = useState('');
  const [characterLimit] = useState(props.inputArray['#counter_maximum'] ?? props.inputArray['#maxlength'] ?? null);
  // event handler
  const handleChange = event => {
    console.log(event)
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
        <dd>{props.inputArray['#value']}</dd>
      </dl>
    );
  } else {
    return (
      <TextArea
        id={props.id}
        label={props.inputArray['#title']}
        required={props.inputArray['#required']}
        onChange={handleChange}
        errorText={errorText}
        maxLength={characterLimit ?? undefined}
        tooltipText={props.inputArray['#help'] ? parse(props.inputArray['#help']) : null}
        helperText={props.inputArray['#description'] ?
          parse(props.inputArray['#description']) :
          (props.inputArray['#counter_type'] ? inputText.length + '/' + characterLimit : null)}
      />
    );
  }

}
export default GrantsTextArea
