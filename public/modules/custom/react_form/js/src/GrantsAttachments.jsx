import React, {useState} from "react";
import {Checkbox, FileInput} from "hds-react";
import parse from 'html-react-parser';

function GrantsTextArea(props) {
  const [inputText, setInputText] = useState("");
  const [errorText, setErrorText] = useState('');
  const [characterLimit] = useState(props.inputArray['#counter_maximum'] ?? props.inputArray['#maxlength'] ?? null);
  // event handler
  const handleChange = event => {
    setInputText(event.target.value);
    if (props.inputArray['#required'] || props.inputArray['#required'] === 'required') {
      if (event.target.value.length < 1) {
        setErrorText(Drupal.t('@name field is required.', {'@name': props.inputArray['#title']}, {'langcode': drupalSettings.langcode}));
      }
    }
  };
  return (
    <>
      <FileInput
        id={props.id}
        label={props.inputArray['#title']}
        required={props.inputArray['#required']}
        onChange={handleChange}
        errorText={errorText}
        accept=".doc,.docx,.gif,.jpg,.jpeg,.pdf,.png,.ppt,.pptx,.rtf,.txt,.xls,.xlsx,.zip"
        maxSize={20 * 1024 * 1024}
        tooltipText={props.inputArray['#help'] ? parse(props.inputArray['#help']) : null}
        helperText={props.inputArray['#description'] ?
          parse(props.inputArray['#description']) :
          (props.inputArray['#counter_type'] ? inputText.length + '/' + characterLimit : null)}
      />
      <Checkbox
        id={props.id + '_later'}
        label={Drupal.t("Attachment will be delivered at later time", {}, {context: 'grants_attachments'})}
      />
      <Checkbox
        id={props.id + '_already'}
        label={Drupal.t("Attachment already delivered", {}, {context: 'grants_attachments'})}
      />
      <br/>
      <br/>
    </>
  );
}
export default GrantsTextArea
