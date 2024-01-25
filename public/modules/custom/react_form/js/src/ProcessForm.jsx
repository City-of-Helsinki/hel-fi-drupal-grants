import {Fieldset, StepState} from "hds-react";
import GrantsAttachments from "./GrantsAttachments";
import GrantsTextArea from "./GrantsTextArea";
import GrantsTextInput from "./GrantsTextInput";
import GrantsRadios from "./GrantsRadios";
import GrantsSelect from "./GrantsSelect";
import parse from "html-react-parser";
import React from "react";
import PreviewPage from "./PreviewPage";

const ProcessForm = (props) => {

  function analyseArray(analysedArray, key, keyArray) {
    let tempArray = [];
    tempArray = tempArray.concat(keyArray);
    tempArray = tempArray.concat(key);
    let handleWebformChange = props.handleWebformChange;
    if (analysedArray['#type'] === 'webform_wizard_page') {
      return (
          <div
            key={key}
            style={{ display: (props.state.steps[props.state.activeStepIndex].label == analysedArray['#title'] ? 'block' : 'none') }}
          >
            <h2>{analysedArray['#title']}</h2>
            {
              Object.keys(analysedArray).map(function(arrayKey) {
                return (
                  analyseArray(analysedArray[arrayKey], arrayKey, tempArray)
                )
              })
            }
          </div>

      );
    } else if (analysedArray['#type'] === 'webform_section') {
      return (
        <div className="js-webform-states-hidden js-form-item form-item js-form-wrapper form-wrapper"
             key={key}
        >
          <div className="react-form-section">
            <h3 className="webform-section-title">{analysedArray['#title']}</h3>
            <div className="webform-section-wrapper">
              {
                Object.keys(analysedArray).map(function(arrayKey) {
                  return (
                    analyseArray(analysedArray[arrayKey], arrayKey, tempArray)
                  )
                })
              }
            </div>
          </div>
        </div>
      );
    } else if (analysedArray['#type'] === 'webform_custom_composite') {
      return (
        <Fieldset heading={analysedArray['#title']}
                  key={key}
                  border
                  id={key}>
          {
            Object.keys(analysedArray['#element']).map(function(arrayKey) {
              return (
                analyseArray(analysedArray['#element'][arrayKey], arrayKey, tempArray)
              )
            })
          }
        </Fieldset>
      );
    } else if (analysedArray['#type'] === 'grants_attachments') {
      return <GrantsAttachments
        key={key}
        id={key}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'textarea') {
      return <GrantsTextArea
        key={key}
        id={key}
        callbackKey={tempArray}
        updatedValueCallback={handleWebformChange}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'email') {
      return <GrantsTextInput
        key={key}
        id={key}
        callbackKey={tempArray}
        updatedValueCallback={handleWebformChange}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'radios') {
      return <GrantsRadios
        key={key}
        id={key}
        callbackKey={tempArray}
        preview={true}
        updatedValueCallback={handleWebformChange}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'select') {
      return <GrantsSelect
        key={key}
        id={key}
        callbackKey={tempArray}
        updatedValueCallback={handleWebformChange}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'fieldset') {
      return (
        <Fieldset heading={analysedArray['#title']}
                  key={key}
                  border
                  id={key}>
          {
            Object.keys(analysedArray).map(function(arrayKey) {
              return (
                analyseArray(analysedArray[arrayKey], arrayKey, tempArray)
              )
            })
          }
        </Fieldset>
      );
    } else if (analysedArray['#type'] === 'number') {
      return <GrantsTextInput
        key={key}
        id={key}
        callbackKey={tempArray}
        updatedValueCallback={handleWebformChange}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'date') {
      return <GrantsTextInput
        key={key}
        id={key}
        callbackKey={tempArray}
        updatedValueCallback={handleWebformChange}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'textfield') {
      return <GrantsTextInput
        key={key}
        id={key}
        callbackKey={tempArray}
        updatedValueCallback={handleWebformChange}
        inputArray={analysedArray}
      />

    } else if (analysedArray['#type'] === 'webform_actions') {

    } else if (analysedArray['#type'] === 'grants_webform_summation_field') {

    } else if (analysedArray['#type'] === 'hidden') {

    } else if (analysedArray['#type'] === 'webform_markup') {
      return parse(analysedArray['#markup'])
    } else if (analysedArray['#type'] === 'processed_text') {
      return parse(analysedArray['#text'])
    } else {
      return <div>{analysedArray['#type']}</div>
    }
  }
  const keys = Object.keys(props.webForm).map(function(key) {
    return analyseArray(props.webForm[key], key, [])
  });
  if (props.previewPage === true) {
    return (
      <>
        {keys}
      </>
    )

  } else {
    return (
      <>
        {keys}
        <div
          key='aa'
          style={{display: (props.state.steps[props.state.activeStepIndex].label == 'Esikatselu' ? 'block' : 'none')}}
        >
          <h2>Title</h2>
          <ProcessForm
            webformArray={props.webformArray}
            webForm={props.webForm}
            state={props.state}
            previewPage={true}
            handleWebformChange={props.handleWebformChange}
          />
        </div>
      </>
    )

  }
}
export default ProcessForm
