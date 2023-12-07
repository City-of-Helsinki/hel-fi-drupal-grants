import React from "react";
import { TextArea, Tooltip } from "hds-react";
import parse from 'html-react-parser';

const GrantsTextArea = (props) => (
  <TextArea
    id={props.id}
    label={props.inputArray['#title']}
    required={props.inputArray['#required']}
    tooltipText={props.inputArray['#help'] ? parse(props.inputArray['#help']) : null}
    helperText={props.inputArray['#description'] ? parse(props.inputArray['#description']) : null}
  />
);
export default GrantsTextArea
