import React, { useState, useRef, useEffect } from 'react';
import { ChevronDown } from 'lucide-react';

export const Dropdown = ({
  trigger,
  children,
  align = 'left',
  className = '',
  onOpen = null,
  onClose = null
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);
  const triggerRef = useRef(null);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
        onClose?.();
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
      onOpen?.();
    }

    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [isOpen, onOpen, onClose]);

  return (
    <div className={`dropdown-wrapper ${className}`} ref={dropdownRef}>
      <button
        ref={triggerRef}
        className="dropdown-trigger"
        onClick={() => setIsOpen(!isOpen)}
        aria-expanded={isOpen}
      >
        {trigger}
      </button>
      {isOpen && (
        <div className={`dropdown-menu dropdown-menu-${align}`}>
          {children}
        </div>
      )}
    </div>
  );
};

export const DropdownItem = ({
  onClick,
  children,
  icon = null,
  destructive = false,
  disabled = false
}) => (
  <button
    className={`dropdown-item ${destructive ? 'dropdown-item-destructive' : ''} ${disabled ? 'dropdown-item-disabled' : ''}`}
    onClick={onClick}
    disabled={disabled}
  >
    {icon && <span className="dropdown-item-icon">{icon}</span>}
    <span className="dropdown-item-text">{children}</span>
  </button>
);

export const DropdownDivider = () => <div className="dropdown-divider" />;

export const Tabs = ({
  tabs,
  defaultTab = 0,
  onChange = null,
  variant = 'default'
}) => {
  const [activeTab, setActiveTab] = useState(defaultTab);

  const handleTabChange = (index) => {
    setActiveTab(index);
    onChange?.(index);
  };

  return (
    <div className={`tabs tabs-${variant}`}>
      <div className="tabs-header" role="tablist">
        {tabs.map((tab, index) => (
          <button
            key={index}
            role="tab"
            aria-selected={activeTab === index}
            className={`tabs-tab ${activeTab === index ? 'tabs-tab-active' : ''}`}
            onClick={() => handleTabChange(index)}
          >
            {tab.icon && <span className="tabs-tab-icon">{tab.icon}</span>}
            <span className="tabs-tab-label">{tab.label}</span>
          </button>
        ))}
      </div>
      <div className="tabs-content">
        {tabs[activeTab]?.content}
      </div>
    </div>
  );
};

export const Accordion = ({
  items,
  multiple = false,
  className = ''
}) => {
  const [expanded, setExpanded] = useState(multiple ? [] : [0]);

  const toggleItem = (index) => {
    if (multiple) {
      setExpanded((prev) =>
        prev.includes(index) ? prev.filter((i) => i !== index) : [...prev, index]
      );
    } else {
      setExpanded((prev) => (prev.includes(index) ? [] : [index]));
    }
  };

  return (
    <div className={`accordion ${className}`}>
      {items.map((item, index) => (
        <div key={index} className="accordion-item">
          <button
            className={`accordion-header ${expanded.includes(index) ? 'accordion-header-open' : ''}`}
            onClick={() => toggleItem(index)}
            aria-expanded={expanded.includes(index)}
          >
            <span className="accordion-title">{item.title}</span>
            <span className="accordion-icon">
              <ChevronDown className="w-5 h-5" />
            </span>
          </button>
          {expanded.includes(index) && (
            <div className="accordion-content">
              {item.content}
            </div>
          )}
        </div>
      ))}
    </div>
  );
};

export const Select = ({
  options = [],
  value = '',
  onChange = null,
  placeholder = 'Select an option...',
  disabled = false,
  label = '',
  error = '',
  className = ''
}) => {
  return (
    <div className={`select-wrapper ${className}`}>
      {label && <label className="select-label">{label}</label>}
      <select
        value={value}
        onChange={(e) => onChange?.(e.target.value)}
        disabled={disabled}
        className={`select ${error ? 'select-error' : ''} ${disabled ? 'select-disabled' : ''}`}
      >
        <option value="">{placeholder}</option>
        {options.map((option, idx) => (
          <option key={idx} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
      {error && <span className="select-error-text">{error}</span>}
    </div>
  );
};

