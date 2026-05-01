import React from 'react';
import { cn } from '../../utils/cn';

const Skeleton = ({ className, ...props }) => {
  return (
    <div
      className={cn('skeleton rounded-md', className)}
      {...props}
    />
  );
};

export default Skeleton;
