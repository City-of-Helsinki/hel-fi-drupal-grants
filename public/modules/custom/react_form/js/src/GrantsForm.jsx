import {
  Button,
  Fieldset,
  IconArrowLeft,
  IconArrowRight,
  Stepper,
  StepState,
  Notification,
  IconUploadCloud, FileInput
} from "hds-react";
import axios from "axios";
import React, {useReducer} from "react";
import GrantsTextArea from "./GrantsTextArea";
import GrantsTextInput from "./GrantsTextInput";
import GrantsRadios from "./GrantsRadios";
import GrantsAttachments from "./GrantsAttachments";
import GrantsSelect from "./GrantsSelect";
import parse from "html-react-parser";
import PreviewPage from "./PreviewPage";

const GrantsForm = (props) => {
  const webForm = props.webform;
  const [isLoading, setIsLoading] = React.useState(false);
  const [showNotification, setShowNotification] = React.useState(false);
  const [webformArray, setWebformArray] = React.useState(false);

  function submitForm() {
    console.log(webformArray);
    alert('Form Submit function called, see your console.log');
  }
  const commonReducer = (stepsTotal) => (state, action) => {
    switch (action.type) {
      case 'completeStep': {
        const lastStep = state.activeStepIndex === state.steps.length - 1;
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
  steppes.push({
      label: 'Esikatselu',
      state: StepState.available,
    }
  );
  const reducer = commonReducer(steppes.length);
  const initialState = {
    activeStepIndex: 0,
    steps: steppes,
  };
  const [state, dispatch] = useReducer(reducer, initialState);
  const lastStep = state.activeStepIndex === state.steps.length - 1;

  const keys = Object.keys(webForm).map(function(key) {
    return analyseArray(webForm[key], key, [])
  });
 keys.push(
   <div
     key='aa'
     style={{ display: (state.steps[state.activeStepIndex].label == 'Esikatselu' ? 'block' : 'none') }}
   >
     <h2>Title</h2>
     <PreviewPage webform={webformArray}/>
   </div>
 )

  function handleWebformChange(childKeys, childValue) {
    let tempFormArray = (webformArray === false) ? webForm : webformArray;
    let depth = childKeys.length;
    if (depth == 2) {
      tempFormArray[childKeys[0]][childKeys[1]]['#value'] = childValue;
    } else if (depth == 3) {
      tempFormArray[childKeys[0]][childKeys[1]][childKeys[2]]['#value'] = childValue;
    }
    console.log(tempFormArray)
    setWebformArray(tempFormArray);
  }
  async function sendDataAsDraft() {
    const response =
    await axios.patch('kasko_ip_lisa/app_nro123');
    setShowNotification(true);
    setIsLoading(false);
    console.log(response.data)
  }
  return (
    <div key="ReactApp" id="ReactApp">
      <form onSubmit={() => {submitForm()}}>
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
            onClick={
              lastStep ?
                () => {submitForm()} :
                () => dispatch({ type: 'completeStep', payload: state.activeStepIndex })}
            style={{ height: 'fit-content', width: 'fit-content' }}
            iconRight={lastStep ? undefined : <IconArrowRight />}
            type={lastStep ? 'submit' : 'button'}
            theme="black"
          >
            {lastStep ? Drupal.t('Send') : Drupal.t('Next')}
          </Button>

          <>
            <Button
              isLoading={isLoading}
              variant="supplementary"
              theme="black"
              iconLeft={<IconUploadCloud />}
              loadingText={Drupal.t("Saving form changes")}
              onClick={async () => {
                setShowNotification(false);
                setIsLoading(true);
                await sendDataAsDraft();
              }}
            >
              {Drupal.t('Save Draft')}
            </Button>
            {showNotification && (
              <Notification
                key={new Date().toString()}
                position="top-right"
                displayAutoCloseProgress={false}
                autoClose
                dismissible
                label="Form saved!"
                type="success"
                onClose={() => {
                  setShowNotification(false);
                }}
              >
                {Drupal.t('Saving your form was successful.')}
              </Notification>
            )}
          </>
        </div>
      </form>
    </div>
  );
  function analyseArray(analysedArray, key, keyArray) {
    let tempArray = [];
    tempArray = tempArray.concat(keyArray);
    tempArray = tempArray.concat(key);
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
}
export default GrantsForm
