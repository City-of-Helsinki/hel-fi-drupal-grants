// biome-ignore-all lint/suspicious/noArrayIndexKey: @todo UHF-12501
import { Component, type ErrorInfo } from 'react';
import { InvalidSchemaError } from './InvalidSchemaError';

/**
 * Boundary for invalid schema errors.
 * Catches only `InvalidSchemaError` errors.
 *
 * @see https://reactjs.org/docs/error-boundaries.html
 */
export class InvalidSchemaBoundary extends Component {
  constructor(props: { children: React.ReactNode }) {
    super(props);
    this.state = { errorStack: null };
  }

  componentDidCatch(error: Error, _errorInfo: ErrorInfo) {
    // Only catch in development
    // @todo Implement a better check
    const isDevEnvironment = window.location.hostname.includes('docker.so');

    if (error instanceof InvalidSchemaError && isDevEnvironment) {
      this.setState({ errorStack: error.message });
    } else {
      throw error;
    }
  }

  render() {
    const { errorStack } = this.state;
    const { children } = this.props;

    const formatSchemaErrors = () => {
      const [, errors] = errorStack.split('schema is invalid:');

      return (
        <div>
          <h3>Invalid schema</h3>
          <ul>
            {errors.split(',').map((error, index) => (
              <li key={index}>{error}</li>
            ))}
          </ul>
        </div>
      );
    };

    if (errorStack) {
      return <div style={{ backgroundColor: 'salmon', padding: '28px' }}>{formatSchemaErrors()}</div>;
    }

    return children;
  }
}
