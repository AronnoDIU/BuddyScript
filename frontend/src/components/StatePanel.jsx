const iconByVariant = {
  info: 'i',
  error: '!',
  empty: '~',
};

export default function StatePanel({
  variant = 'info',
  title,
  message = '',
  compact = false,
  action = null,
  className = '',
}) {
  const safeVariant = ['info', 'error', 'empty'].includes(variant) ? variant : 'info';
  const classes = [
    'ui_state',
    `ui_state_${safeVariant}`,
    compact ? 'ui_state_compact' : '',
    className,
  ].filter(Boolean).join(' ');

  return (
    <div className={classes} role={safeVariant === 'error' ? 'alert' : 'status'}>
      <span className="ui_state_icon" aria-hidden="true">{iconByVariant[safeVariant]}</span>
      <div className="ui_state_body">
        {title && <h4 className="ui_state_title">{title}</h4>}
        {message && <p className="ui_state_message">{message}</p>}
        {action}
      </div>
    </div>
  );
}

