import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api, setToken } from '../api';

export default function LoginPage() {
  const navigate = useNavigate();
  const [form, setForm] = useState({ email: '', password: '' });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const onChange = (event) => {
    setForm((prev) => ({ ...prev, [event.target.name]: event.target.value }));
  };

  const onSubmit = async (event) => {
    event.preventDefault();
    setLoading(true);
    setError('');

    try {
      const response = await api.post('/login_check', form);
      setToken(response.data.token);
      navigate('/feed');
    } catch (submitError) {
      setError(submitError.response?.data?.message || 'Invalid login credentials.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <section className="_social_login_wrapper _layout_main_wrapper">
      <div className="_social_login_wrap">
        <div className="container">
          <div className="row align-items-center">
            <div className="col-xl-6 col-lg-7 col-md-12 col-sm-12 mx-auto">
              <div className="_social_login_right">
                <div className="_social_login_right_area">
                  <h2 className="_social_login_right_title">Sign in to Buddy Script</h2>
                  <form className="_social_login_form" onSubmit={onSubmit}>
                    <div className="_social_login_form_input">
                      <input
                        className="form-control _social_login_form_input_control"
                        name="email"
                        type="email"
                        placeholder="Email"
                        value={form.email}
                        onChange={onChange}
                        required
                      />
                    </div>
                    <div className="_social_login_form_input">
                      <input
                        className="form-control _social_login_form_input_control"
                        name="password"
                        type="password"
                        placeholder="Password"
                        value={form.password}
                        onChange={onChange}
                        required
                      />
                    </div>
                    {error && <p className="text-danger mt-2">{error}</p>}
                    <button className="_social_login_btn" type="submit" disabled={loading}>
                      {loading ? 'Signing in...' : 'Sign in'}
                    </button>
                  </form>
                  <p className="mt-3 mb-0">
                    Do not have an account? <Link to="/register">Create account</Link>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

