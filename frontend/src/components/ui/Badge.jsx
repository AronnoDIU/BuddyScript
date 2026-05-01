import React from 'react';
import { cn } from '../../utils/cn';

const Badge = React.forwardRef(({ className, variant = 'default', children, ...props }, ref) => {
  const variantClasses = {
    default: 'badge',
    success: 'badge-success',
    warning: 'badge-warning',
    error: 'badge-error',
    secondary: 'bg-surface text-secondary',
    outline: 'border border-border text-primary bg-surface',
  };

  return (
    <span
      ref={ref}
      className={cn('inline-flex items-center', variantClasses[variant], className)}
      {...props}
    >
      {children}
    </span>
  );
});

Badge.displayName = 'Badge';

export default Badge;
