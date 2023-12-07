import React from 'react';
import ReactDOM from 'react-dom';
import { TextInput } from "hds-react";

// # Example 1: Simple "Hello, World" code
ReactDOM.render(
  <div style={{ maxWidth: '320px' }}>
    <TextInput
      id="textinput"
      label="Label"
      placeholder="Placeholder"
      helperText="Assistive text"
      required
    />
    <TextInput
      id="textinput-invalid"
      label="Label"
      defaultValue="Text input value"
      helperText="Assistive text"
      style={{marginTop: 'var(--spacing-s)'}}
      disabled
    />
    <TextInput
      id="textinput-invalid"
      label="Label"
      defaultValue="Text input value"
      errorText="Error text"
      helperText="Assistive text"
      style={{marginTop: 'var(--spacing-s)'}}
      invalid
    />
  </div>,
  document.getElementById('react-app')
);
