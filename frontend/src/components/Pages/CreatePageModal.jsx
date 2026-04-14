import { useState } from 'react';
import { X } from 'lucide-react';

const INITIAL_FORM = {
  name: '',
  category: 'Community',
  description: '',
};

export default function CreatePageModal({ onClose, onCreate }) {
  const [form, setForm] = useState(INITIAL_FORM);
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const onChange = (event) => {
    const { name, value } = event.target;
    setForm((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) {
      setErrors((prev) => ({ ...prev, [name]: '' }));
    }
  };

  const validate = () => {
    const nextErrors = {};
    if (!form.name.trim()) {
      nextErrors.name = 'Page name is required.';
    }
    if (form.description.length > 400) {
      nextErrors.description = 'Description must be 400 characters or less.';
    }
    setErrors(nextErrors);
    return Object.keys(nextErrors).length === 0;
  };

  const submit = async (event) => {
    event.preventDefault();
    if (!validate()) {
      return;
    }

    setSubmitting(true);
    try {
      await onCreate?.({
        name: form.name.trim(),
        category: form.category,
        description: form.description.trim(),
      });
      setForm(INITIAL_FORM);
    } catch (error) {
      setErrors({ submit: error?.response?.data?.message || 'Failed to create page.' });
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="pages-modal" onClick={onClose} role="presentation">
      <div className="pages-modal__card" onClick={(event) => event.stopPropagation()}>
        <div className="pages-modal__header">
          <h3>Create a Page</h3>
          <button type="button" className="pages-modal__close" onClick={onClose}>
            <X size={18} />
          </button>
        </div>

        <form className="pages-modal__form" onSubmit={submit}>
          <label>
            Page name
            <input name="name" value={form.name} onChange={onChange} placeholder="Acme Studio" maxLength={80} />
          </label>
          {errors.name && <p className="pages-modal__error">{errors.name}</p>}

          <label>
            Category
            <input name="category" value={form.category} onChange={onChange} placeholder="Business" maxLength={40} />
          </label>

          <label>
            Description
            <textarea
              name="description"
              value={form.description}
              onChange={onChange}
              placeholder="Tell people what your Page is about"
              rows={4}
              maxLength={400}
            />
          </label>
          {errors.description && <p className="pages-modal__error">{errors.description}</p>}

          {errors.submit && <p className="pages-modal__error">{errors.submit}</p>}

          <div className="pages-modal__actions">
            <button type="button" className="pages-btn pages-btn--ghost" onClick={onClose}>Cancel</button>
            <button type="submit" className="pages-btn pages-btn--primary" disabled={submitting}>
              {submitting ? 'Creating...' : 'Create Page'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}

