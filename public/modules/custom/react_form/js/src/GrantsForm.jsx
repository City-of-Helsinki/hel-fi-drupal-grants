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
import ProcessForm from "./ProcessForm";

const GrantsForm = (props) => {
  const webForm = props.webform;
  const [isLoading, setIsLoading] = React.useState(false);
  const [showNotification, setShowNotification] = React.useState(false);
  const [webformArray, setWebformArray] = React.useState(false);

  function submitForm() {
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
  const steppes = Object.keys(webForm).map(function (key) {
    if ((webForm[key]['#type'] ? webForm[key]['#type'] : '') === 'webform_wizard_page') {
      return {
        label: webForm[key]['#title'],
        state: StepState.available,
      }
    } else return;
  }).filter(function (x) {
    return x !== undefined;
  });
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

  function handleWebformChange(childKeys, childValue) {
    let tempFormArray = (webformArray === false) ? webForm : webformArray;
    let depth = childKeys.length;
    console.log(tempFormArray);
    console.log('depth. ' + depth)
    if (depth == 2) {
      tempFormArray[childKeys[0]][childKeys[1]]['#value'] = childValue;
    } else if (depth == 3) {
      tempFormArray[childKeys[0]][childKeys[1]][childKeys[2]]['#value'] = childValue;
    } else if (depth == 4) {
      tempFormArray[childKeys[0]][childKeys[1]][childKeys[2]][childKeys[3]]['#value'] = childValue;
    } else if (depth == 5) {
      tempFormArray[childKeys[0]][childKeys[1]][childKeys[2]][childKeys[3]][childKeys[4]]['#value'] = childValue;
    }
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
      <form onSubmit={() => {
        submitForm()
      }}>
        <Stepper
          steps={state.steps}
          language="en"
          selectedStep={state.activeStepIndex}
          onStepClick={(event, stepIndex) => dispatch({type: 'setActive', payload: stepIndex})}
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
          <ProcessForm
            webformArray={webformArray}
            webForm={webForm}
            state={state}
            handleWebformChange={handleWebformChange}
          />

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
            onClick={() => dispatch({type: 'setActive', payload: state.activeStepIndex - 1})}
            style={{height: 'fit-content', width: 'fit-content'}}
            iconLeft={<IconArrowLeft/>}
            theme="black"
          >
            Previous
          </Button>
          <Button
            variant={lastStep ? 'primary' : 'secondary'}
            onClick={
              lastStep ?
                () => {
                  submitForm()
                } :
                () => dispatch({type: 'completeStep', payload: state.activeStepIndex})}
            style={{height: 'fit-content', width: 'fit-content'}}
            iconRight={lastStep ? undefined : <IconArrowRight/>}
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
              iconLeft={<IconUploadCloud/>}
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
}
export default GrantsForm
