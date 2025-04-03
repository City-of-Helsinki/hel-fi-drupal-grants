
export type SubmitState = 'unsubmitted' | 'submitted' | 'accepted' | 'editing';

type SubmitStatesType = {
  accepted: SubmitState;
  editing: SubmitState;
  submitted: SubmitState;
  unsubmitted: SubmitState;
}

export const SubmitStates: SubmitStatesType = {
  accepted: 'accepted',
  editing: 'editing',
  submitted: 'submitted',
  unsubmitted: 'unsubmitted',
};