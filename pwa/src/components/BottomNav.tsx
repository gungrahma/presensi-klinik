import { NavLink, useLocation } from 'react-router-dom';
import { Home, ClipboardList, FileText, User } from 'lucide-react';

const nav = [
  { to: '/', label: 'Beranda', icon: Home },
  { to: '/history', label: 'Riwayat', icon: ClipboardList },
  { to: '/leave', label: 'Cuti', icon: FileText },
  { to: '/profile', label: 'Profil', icon: User },
];

export default function BottomNav() {
  const { pathname } = useLocation();
  if (pathname === '/login') return null;

  return (
    <nav className="fixed bottom-0 left-0 right-0 z-30 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)]">
      <div className="mx-auto flex max-w-md">
        {nav.map((n) => {
          const Icon = n.icon;
          return (
            <NavLink
              key={n.to}
              to={n.to}
              end={n.to === '/'}
              className={({ isActive }) =>
                `flex-1 flex flex-col items-center gap-1 py-2.5 text-xs transition ${
                  isActive ? 'text-primary-600 font-semibold' : 'text-gray-500'
                }`
              }
            >
              {({ isActive }) => (
                <>
                  <Icon size={22} strokeWidth={isActive ? 2.25 : 1.75} />
                  <span>{n.label}</span>
                </>
              )}
            </NavLink>
          );
        })}
      </div>
    </nav>
  );
}
