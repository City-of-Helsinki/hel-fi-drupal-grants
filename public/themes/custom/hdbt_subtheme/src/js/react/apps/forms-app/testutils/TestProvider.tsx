import { Provider } from 'jotai';
import { useHydrateAtoms } from 'jotai/utils';

const HydrateAtoms = ({initialValues, children}) => {
  useHydrateAtoms (initialValues);
  return children;
}

export const TestProvider = ({initialValues, children}) => {
  return (
    <Provider>
      <HydrateAtoms initialValues={initialValues}>
        {children}
      </HydrateAtoms>
    </Provider>
  );
};
