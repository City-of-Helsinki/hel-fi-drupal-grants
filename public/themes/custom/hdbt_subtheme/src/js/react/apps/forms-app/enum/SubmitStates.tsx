
export type SubmitState = 'unsubmitted' | 'submitted' | 'accepted';

type SubmitStatesType = {
  accepted: SubmitState;
  submitted: SubmitState;
  unsubmitted: SubmitState;
}

export const SubmitStates: SubmitStatesType = {
  accepted: 'accepted',
  submitted: 'submitted',
  unsubmitted: 'unsubmitted',
};