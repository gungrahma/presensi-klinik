import { useState } from 'react';
import { LogOut, Stethoscope, Mail, Phone, IdCard, User as UserIcon } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../stores/authStore';

export default function Profile() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [showLogoutConfirm, setShowLogoutConfirm] = useState(false);

  const onLogout = async () => {
    await logout();
    navigate('/login');
  };

  if (!user) return null;

  const fields = [
    { icon: IdCard, label: 'NIK', value: user.employee_id },
    { icon: Mail, label: 'Email', value: user.email },
    { icon: Phone, label: 'No. HP', value: user.phone },
    { icon: Stethoscope, label: 'Jabatan', value: user.position },
  ];

  return (
    <div className="p-4 pb-24 max-w-md mx-auto">
      <h1 className="text-2xl font-bold mb-4">Profil</h1>

      <div className="card text-center mb-4">
        {user.photo_url ? (
          <img
            src={user.photo_url}
            alt={user.name}
            className="w-24 h-24 rounded-full mx-auto object-cover ring-4 ring-primary-100"
          />
        ) : (
          <div className="w-24 h-24 rounded-full mx-auto bg-primary-100 text-primary-700 flex items-center justify-center">
            <UserIcon size={48} strokeWidth={1.5} />
          </div>
        )}
        <h2 className="font-bold text-lg mt-3">{user.name}</h2>
        <p className="text-sm text-gray-600">{user.position || 'Karyawan'}</p>
      </div>

      <div className="card divide-y divide-gray-100">
        {fields.map((f) => {
          const Icon = f.icon;
          return (
            <div key={f.label} className="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
              <Icon size={18} className="text-gray-400 shrink-0" />
              <div className="flex-1 min-w-0">
                <p className="text-xs text-gray-500">{f.label}</p>
                <p className="font-medium truncate">{f.value || '-'}</p>
              </div>
            </div>
          );
        })}
      </div>

      <button onClick={() => setShowLogoutConfirm(true)} className="btn-danger w-full mt-6">
        <LogOut size={18} />
        Keluar
      </button>

      {showLogoutConfirm && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl p-6 max-w-sm w-full">
            <h3 className="font-bold text-lg mb-2">Keluar dari aplikasi?</h3>
            <p className="text-sm text-gray-600 mb-5">
              Anda perlu login kembali untuk mengakses data absensi.
            </p>
            <div className="flex gap-2">
              <button onClick={() => setShowLogoutConfirm(false)} className="btn-secondary flex-1">
                Batal
              </button>
              <button onClick={onLogout} className="btn-danger flex-1">
                Ya, Keluar
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
