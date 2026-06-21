import { useState, type FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { Stethoscope } from 'lucide-react';
import { useAuth } from '../stores/authStore';

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const { login, loading } = useAuth();
  const navigate = useNavigate();

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    try {
      await login(email, password, navigator.userAgent);
      navigate('/');
    } catch (e: unknown) {
      const err = e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
      setError(err.response?.data?.message || err.response?.data?.errors?.email?.[0] || 'Email atau password salah');
    }
  };

  return (
    <div className="min-h-full flex flex-col items-center justify-center p-6 bg-gradient-to-br from-primary-500 to-primary-700">
      <div className="w-full max-w-sm">
        <div className="text-center mb-8">
          <div className="inline-flex w-20 h-20 rounded-2xl bg-white/20 backdrop-blur items-center justify-center mb-4">
            <Stethoscope size={40} className="text-white" strokeWidth={1.75} />
          </div>
          <h1 className="text-3xl font-bold text-white">Absensi Klinik</h1>
          <p className="text-primary-100 mt-2">Masuk untuk melanjutkan</p>
        </div>

        <form onSubmit={onSubmit} className="bg-white rounded-2xl p-6 shadow-2xl space-y-4">
          {error && (
            <div className="rounded-lg bg-red-50 border border-red-100 p-3 text-sm text-red-700">
              {error}
            </div>
          )}
          <div>
            <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
              Email
            </label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="input"
              placeholder="nama@klinik.com"
              autoComplete="email"
            />
          </div>
          <div>
            <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
              Password
            </label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className="input"
              placeholder="••••••••"
              autoComplete="current-password"
            />
          </div>
          <button type="submit" disabled={loading} className="btn-primary w-full">
            {loading ? 'Memproses...' : 'Masuk'}
          </button>
        </form>

        <p className="text-center text-xs text-primary-100 mt-6">
          Kendala login? Hubungi admin klinik
        </p>
      </div>
    </div>
  );
}
