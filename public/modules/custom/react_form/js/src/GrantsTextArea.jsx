import React from "react";
import { TextInput, Tooltip } from "hds-react";

const GrantsTextInput = (props) => (
      <div>
        {props.inputArray['#help'] ?
          (<Tooltip>{props.inputArray['#help']}</Tooltip>) : ''}
          <TextInput
            id={props.inputArray['#id']}
            label={props.inputArray['#title']}
            helperText={props.inputArray['#description']}
          />
      </div>
    );
export default GrantsTextInput
