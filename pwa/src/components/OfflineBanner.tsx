import { useEffect, useState } from 'react';
import { CheckCircle2, Loader2, WifiOff, RefreshCw } from 'lucide-react';
import { syncQueue } from '../services/api';

export default function OfflineBanner() {
  const [online, setOnline] = useState(navigator.onLine);
  const [pending, setPending] = useState(0);
  const [syncing, setSyncing] = useState(false);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    const onOnline = async () => {
      setOnline(true);
      if (pending > 0) {
        setSyncing(true);
        const r = await syncQueue();
        setSyncing(false);
        setPending(0);
        if (r.synced > 0) {
          setMessage(`${r.synced} data berhasil dikirim`);
          setTimeout(() => setMessage(null), 3000);
        }
        if (r.failed > 0) {
          setMessage(`${r.failed} data gagal dikirim, akan dicoba lagi`);
          setTimeout(() => setMessage(null), 3000);
        }
      }
    };
    const onOffline = () => setOnline(false);
    window.addEventListener('online', onOnline);
    window.addEventListener('offline', onOffline);
    return () => {
      window.removeEventListener('online', onOnline);
      window.removeEventListener('offline', onOffline);
    };
  }, [pending]);

  if (message) {
    return (
      <div className="sticky top-0 z-40 px-4 py-2 text-sm font-medium text-center bg-green-500 text-white inline-flex items-center justify-center gap-2 w-full">
        <CheckCircle2 size={16} />
        {message}
      </div>
    );
  }

  if (online && pending === 0) return null;

  return (
    <div
      className={`sticky top-0 z-40 px-4 py-2 text-sm font-medium text-center text-white inline-flex items-center justify-center gap-2 w-full ${
        online ? 'bg-amber-500' : 'bg-red-500'
      }`}
    >
      {!online ? (
        <>
          <WifiOff size={16} />
          Tidak ada koneksi. Data akan dikirim saat online.
        </>
      ) : syncing ? (
        <>
          <Loader2 size={16} className="animate-spin" />
          Menyinkronkan {pending} data...
        </>
      ) : (
        <>
          <RefreshCw size={16} />
          {pending} data dalam antrian
        </>
      )}
    </div>
  );
}
