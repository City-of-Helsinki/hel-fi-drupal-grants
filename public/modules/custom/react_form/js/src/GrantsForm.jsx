import {Button, Fieldset, IconArrowLeft, IconArrowRight, Stepper, StepState} from "hds-react";
import React, {useReducer} from "react";
import GrantsTextArea from "./GrantsTextArea";
import GrantsTextInput from "./GrantsTextInput";
import GrantsRadios from "./GrantsRadios";
import GrantsSelect from "./GrantsSelect";
import parse from "html-react-parser";

const GrantsForm = (props) => {
  const webForm = props.webform;
  const commonReducer = (stepsTotal) => (state, action) => {
    switch (action.type) {
      case 'completeStep': {
        const activeStepIndex = action.payload === stepsTotal - 1 ? stepsTotal - 1 : action.payload + 1;
        return {
          activeStepIndex,
          steps: state.steps.map((step, index) => {
            if (index === action.payload && index !== stepsTotal - 1) {
              // current one but not last one
              return {
                state: StepState.completed,
                label: step.label,
              };
            }
            if (index === action.payload + 1) {
              // next one
              return {
                state: StepState.available,
                label: step.label,
              };
            }
            return step;
          }),
        };
      }
      case 'setActive': {
        return {
          activeStepIndex: action.payload,
          steps: state.steps.map((step, index) => {
            if (index === action.payload) {
              return {
                state: StepState.available,
                label: step.label,
              };
            }
            return step;
          }),
        };
      }
      default:
        throw new Error();
    }
  };
  const steppes = Object.keys(webForm).map(function(key) {
    if((webForm[key]['#type'] ? webForm[key]['#type'] : '') === 'webform_wizard_page') {
      return {
        label: webForm[key]['#title'],
        state: StepState.available,
      }
    } else return;
  }).filter(function(x) { return x !== undefined; });
  const reducer = commonReducer(steppes.length);
  const initialState = {
    activeStepIndex: 0,
    steps: steppes,
  };
  const [state, dispatch] = useReducer(reducer, initialState);
  const lastStep = state.activeStepIndex === state.steps.length - 1;

  const keys = Object.keys(webForm).map(function(key) {
    return analyseArray(webForm[key], key)
  });
  return (
    <div key="ReactApp" id="ReactApp">
      <Stepper
        steps={state.steps}
        language="en"
        selectedStep={state.activeStepIndex}
        onStepClick={(event, stepIndex) => dispatch({ type: 'setActive', payload: stepIndex })}
        theme={{
          '--hds-not-selected-step-label-color': 'var(--color-black-90)',
          '--hds-step-background-color': 'var(--color-white)',
          '--hds-step-content-color': 'var(--color-black-90)',
          '--hds-stepper-background-color': 'var(--color-white)',
          '--hds-stepper-color': 'var(--color-black-90)',
          '--hds-stepper-disabled-color': 'var(--color-black-30)',
          '--hds-stepper-focus-border-color': 'var(--color-black-90)'
        }}
      />
      <div>
        { keys }
      </div>
      <div
        style={{
          display: 'flex',
          justifyContent: 'flex-start',
          alignItems: 'flex-end',
          gap: '24px',
        }}
      >
        <Button
          disabled={state.activeStepIndex === 0}
          variant="secondary"
          onClick={() => dispatch({ type: 'setActive', payload: state.activeStepIndex - 1 })}
          style={{ height: 'fit-content', width: 'fit-content' }}
          iconLeft={<IconArrowLeft />}
          theme="black"
        >
          Previous
        </Button>
        <Button
          variant={lastStep ? 'primary' : 'secondary'}
          onClick={() => dispatch({ type: 'completeStep', payload: state.activeStepIndex })}
          style={{ height: 'fit-content', width: 'fit-content' }}
          iconRight={lastStep ? undefined : <IconArrowRight />}
          theme="black"
        >
          {lastStep ? 'Send' : 'Next'}
        </Button>
      </div>
    </div>
  );
  function analyseArray(analysedArray, key) {
    if (analysedArray['#type'] === 'webform_wizard_page') {
      return (
        <div
          key={key}
          style={{ display: (state.steps[state.activeStepIndex].label == analysedArray['#title'] ? 'block' : 'none') }}
        >
          <h2>{analysedArray['#title']}</h2>
          {
            Object.keys(analysedArray).map(function(arrayKey) {
              return (
                analyseArray(analysedArray[arrayKey], arrayKey)
              )
            })
          }
        </div>
      );
    } else if (analysedArray['#type'] === 'webform_section') {
      return (
        <section className="js-webform-states-hidden js-form-item form-item js-form-wrapper form-wrapper webform-section"
                 key={key}
        >
          <div className="webform-section-flex-wrapper">
            <h3 className="webform-section-title">{analysedArray['#title']}</h3>
            <div className="webform-section-wrapper">
              {
                Object.keys(analysedArray).map(function(arrayKey) {
                  return (
                    analyseArray(analysedArray[arrayKey], arrayKey)
                  )
                })
              }
            </div>
          </div>
        </section>
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
                analyseArray(analysedArray['#element'][arrayKey], arrayKey)
              )
            })
          }
        </Fieldset>
      );
    } else if (analysedArray['#type'] === 'textarea') {
      analysedArray['#required'] = true;
      return <GrantsTextArea
        key={key}
        id={key}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'email') {
      return <GrantsTextInput
        key={key}
        id={key}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'radios') {
      return <GrantsRadios
        key={key}
        id={key}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'select') {
      return <GrantsSelect
        key={key}
        id={key}
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
                analyseArray(analysedArray[arrayKey], arrayKey)
              )
            })
          }
        </Fieldset>
      );
    } else if (analysedArray['#type'] === 'number') {
      return <GrantsTextInput
        key={key}
        id={key}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'date') {
      return <GrantsTextInput
        key={key}
        id={key}
        inputArray={analysedArray}
      />
    } else if (analysedArray['#type'] === 'textfield') {
      return <GrantsTextInput
        key={key}
        id={key}
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
}
export default GrantsForm
