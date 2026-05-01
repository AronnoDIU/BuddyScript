import React from 'react';

const Input = React.forwardRef(({ className, type, error, ...props }, ref) => {
  return (
    <input
      type={type}
      className={`input ${error ? 'border-accent-500 focus:border-accent-500 focus:ring-accent-500' : ''} ${className || ''}`}
      ref={ref}
      {...props}
    />
  );
});

Input.displayName = 'Input';

export default Input;
