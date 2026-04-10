
export function Skeleton({ className = '', style = {}, rounded = true }) {
  const classes = ['ui_skeleton', rounded ? 'ui_skeleton_rounded' : '', className].filter(Boolean).join(' ');
  return <div className={classes} style={style} aria-hidden="true" />;
}

export function SkeletonLine({ width = '100%', className = '' }) {
  return <Skeleton className={`ui_skeleton_line ${className}`.trim()} style={{ width }} />;
}

export function SkeletonCardRows({ rows = 3, className = '' }) {
  return (
    <div className={`ui_skeleton_rows ${className}`.trim()} aria-hidden="true">
      {Array.from({ length: rows }, (_, index) => (
        <Skeleton key={index} className="ui_skeleton_row" />
      ))}
    </div>
  );
}

export default Skeleton;

