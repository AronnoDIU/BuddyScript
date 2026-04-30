import React from 'react';

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error) {
    // Update state so the next render will show the fallback UI.
    return { hasError: true };
  }

  componentDidCatch(error, errorInfo) {
    // You can also log the error to an error reporting service
    console.error("ErrorBoundary caught an error:", error, errorInfo);
    this.setState({
      error: error,
      errorInfo: errorInfo
    });
    // Optionally, track this error with your analytics service
    // trackError('ErrorBoundary', { error: error.message, componentStack: errorInfo.componentStack });
  }

  render() {
    if (this.state.hasError) {
      // You can render any custom fallback UI
      return (
        <div className="flex flex-col items-center justify-center min-h-screen bg-red-100 text-red-800 p-4">
          <h2 className="text-2xl font-bold mb-4">Oops! Something went wrong.</h2>
          <p className="text-lg mb-2">We're sorry for the inconvenience. Please try refreshing the page.</p>
          {process.env.NODE_ENV === 'development' && (
            <details className="mt-4 p-2 bg-red-200 rounded-md w-full max-w-lg overflow-auto">
              <summary className="font-semibold cursor-pointer">Error Details</summary>
              <pre className="whitespace-pre-wrap text-sm mt-2">
                {this.state.error && this.state.error.toString()}
                <br />
                {this.state.errorInfo && this.state.errorInfo.componentStack}
              </pre>
            </details>
          )}
        </div>
      );
    }

    return this.props.children;
  }
}

export default ErrorBoundary;
