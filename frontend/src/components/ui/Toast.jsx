import React, { createContext, useState, useCallback, useRef } from 'react';
import { X, Check, AlertCircle, Info } from 'lucide-react';

const ToastContext = createContext();

export const ToastProvider = ({ children }) => {
  const [toasts, setToasts] = useState([]);
  const toastIdRef = useRef(0);

  const addToast = useCallback(({
    message,
    title = '',
    type = 'info',
    duration = 5000,
    action = null
  }) => {
    const id = toastIdRef.current++;
    const toast = { id, message, title, type, action };

    setToasts((prev) => [...prev, toast]);

    if (duration > 0) {
      setTimeout(() => {
        setToasts((prev) => prev.filter((t) => t.id !== id));
      }, duration);
    }

    return id;
  }, []);

  const removeToast = useCallback((id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id));
  }, []);

  return (
    <ToastContext.Provider value={{ addToast, removeToast }}>
      {children}
      <ToastContainer toasts={toasts} onRemove={removeToast} />
    </ToastContext.Provider>
  );
};

export const useToast = () => {
  const context = React.useContext(ToastContext);
  if (!context) {
    throw new Error('useToast must be used within ToastProvider');
  }
  return context;
};

const ToastContainer = ({ toasts, onRemove }) => (
  <div className="toast-container" role="region" aria-label="Notifications">
    {toasts.map((toast) => (
      <Toast key={toast.id} {...toast} onRemove={onRemove} />
    ))}
  </div>
);

const Toast = ({ id, message, title, type, action, onRemove }) => {
  const iconMap = {
    success: <Check className="w-5 h-5" />,
    error: <AlertCircle className="w-5 h-5" />,
    warning: <AlertCircle className="w-5 h-5" />,
    info: <Info className="w-5 h-5" />,
  };

  const colorMap = {
    success: 'toast-success',
    error: 'toast-error',
    warning: 'toast-warning',
    info: 'toast-info',
  };

  return (
    <div className={`toast ${colorMap[type] || 'toast-info'}`} role="alert">
      <div className="toast-icon">{iconMap[type]}</div>
      <div className="toast-content">
        {title && <div className="toast-title">{title}</div>}
        <div className="toast-message">{message}</div>
      </div>
      {action && (
        <button className="toast-action" onClick={() => { action.onClick(); onRemove(id); }}>
          {action.label}
        </button>
      )}
      <button
        className="toast-close"
        onClick={() => onRemove(id)}
        aria-label="Close notification"
      >
        <X className="w-4 h-4" />
      </button>
    </div>
  );
};

