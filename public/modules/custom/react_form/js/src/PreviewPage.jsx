import React from "react";
import {Fieldset} from "hds-react";
import GrantsTextArea from "./GrantsTextArea";
import GrantsTextInput from "./GrantsTextInput";
import GrantsRadios from "./GrantsRadios";
import GrantsSelect from "./GrantsSelect";
import parse from "html-react-parser";

const PreviewPage = (props) => {
  const webForm = props.webform;
  const keys = Object.keys(webForm).map(function(key) {
    return analyseArray(webForm[key], key, [])
  });

  function analyseArray(analysedArray, key, keyArray) {
    let tempArray = [];
    tempArray = tempArray.concat(keyArray);
    tempArray = tempArray.concat(key);
    if (analysedArray['#type'] === 'webform_wizard_page') {
      return (
        <div
          key={key}
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
        <div
          key={key}
        >
          <h3 className="webform-section-title">{analysedArray['#title']}</h3>
          {
            Object.keys(analysedArray).map(function(arrayKey) {
              return (
                analyseArray(analysedArray[arrayKey], arrayKey, tempArray)
              )
            })
          }
        </div>
      );
    } else if (analysedArray['#type'] === 'webform_custom_composite') {
      return (
        <div      key={key}
        >
          {
            Object.keys(analysedArray['#element']).map(function(arrayKey) {
              return (
                analyseArray(analysedArray['#element'][arrayKey], arrayKey, tempArray)
              )
            })
          }
        </div>
      );
    } else if (analysedArray['#type'] === 'hidden') {
    } else if (analysedArray['#type'] === 'webform_markup') {
      return parse(analysedArray['#markup'])
    } else if (analysedArray['#type'] === 'processed_text') {
      return parse(analysedArray['#text'])
    } else {
      return (
        <div key={key}>
          <h4>{analysedArray['#title']}</h4>
          <div>{analysedArray['#value']}</div>
        </div>
      )
    }
  }


  return (
    <div>
      {keys}
    </div>
  );
}
export default PreviewPage
