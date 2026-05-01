import React from 'react';
import { cn } from '../../utils/cn';

const Avatar = React.forwardRef(({ className, src, alt, size = 'md', children, ...props }, ref) => {
  const sizeClasses = {
    sm: 'avatar-sm',
    md: '',
    lg: 'avatar-lg',
    xl: 'avatar-xl',
  };

  return (
    <div
      ref={ref}
      className={cn('avatar', sizeClasses[size], className)}
      {...props}
    >
      {src ? (
        <img src={src} alt={alt || 'Avatar'} className="w-full h-full object-cover" />
      ) : (
        <span className="text-primary">
          {children}
        </span>
      )}
    </div>
  );
});

Avatar.displayName = 'Avatar';

const AvatarFallback = ({ children, className, ...props }) => (
  <div className={cn('avatar bg-surface text-secondary', className)} {...props}>
    {children}
  </div>
);

export { Avatar, AvatarFallback };
