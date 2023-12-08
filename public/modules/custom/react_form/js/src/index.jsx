import React from 'react';
import ReactDOM from 'react-dom';
import GrantsForm from "./GrantsForm";

const webForm = drupalSettings.reactApp.webform

ReactDOM.render(
    <GrantsForm webform={webForm} key="ReactApp_page" id="ReactApp_page"/>,
  document.getElementById('react-app')
);
