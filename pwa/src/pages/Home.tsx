import { useEffect, useState } from 'react';
import { Loader2, LogIn, LogOut, X, CheckCircle2 } from 'lucide-react';
import { api, queueSubmission } from '../services/api';
import CameraCapture from '../components/CameraCapture';
import GpsChecker from '../components/GpsChecker';
import { useAuth } from '../stores/authStore';
import { formatTime } from '../utils/geofence';
import type { Attendance, ClinicSettings, ScheduleToday } from '../types';

type Step = 'idle' | 'gps' | 'camera' | 'uploading' | 'done' | 'error';

export default function Home() {
  const { user } = useAuth();
  const [attendance, setAttendance] = useState<Attendance | null>(null);
  const [settings, setSettings] = useState<ClinicSettings | null>(null);
  const [schedule, setSchedule] = useState<ScheduleToday | null>(null);
  const [step, setStep] = useState<Step>('idle');
  const [action, setAction] = useState<'clock_in' | 'clock_out' | null>(null);
  const [position, setPosition] = useState<{ lat: number; lng: number; accuracy: number } | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [now, setNow] = useState(new Date());

  const fetchData = async () => {
    setLoading(true);
    try {
      const [att, sch] = await Promise.all([
        api.get('/attendance/today'),
        api.get('/schedule/today'),
      ]);
      setAttendance(att.data.attendance);
      setSettings(att.data.settings);
      setSchedule(sch.data);
    } catch {
      // ignore
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
    const t = setInterval(() => setNow(new Date()), 1000);
    return () => clearInterval(t);
  }, []);

  const startAttendance = (a: 'clock_in' | 'clock_out') => {
    setAction(a);
    setError(null);
    setPosition(null);
    setStep('gps');
  };

  const onGps = (lat: number, lng: number, accuracy: number) => {
    setPosition({ lat, lng, accuracy });
  };

  const proceedToCamera = () => {
    if (!position) {
      setError('Ambil lokasi terlebih dahulu');
      return;
    }
    setStep('camera');
  };

  const onCapture = (blob: Blob) => {
    setStep('uploading');
    submit(blob);
  };

  const submit = async (blob: Blob) => {
    if (!action || !position) return;
    try {
      const form = new FormData();
      form.append('photo', blob);
      form.append('lat', String(position.lat));
      form.append('lng', String(position.lng));
      form.append('accuracy', String(position.accuracy));
      const endpoint = action === 'clock_in' ? '/attendance/clock-in' : '/attendance/clock-out';
      const res = await api.post(endpoint, form, { headers: { 'Content-Type': 'multipart/form-data' } });
      setAttendance(res.data.attendance);
      setStep('done');
      setTimeout(() => {
        setStep('idle');
        setAction(null);
        setPosition(null);
      }, 2000);
    } catch (e: unknown) {
      if (!navigator.onLine) {
        await queueSubmission({ type: action, payload: { photoBlob: blob, ...position } });
        setError('Tidak ada koneksi. Data disimpan dan akan dikirim saat online.');
        setStep('error');
        setTimeout(() => {
          setStep('idle');
          setAction(null);
          setError(null);
        }, 3000);
        return;
      }
      const err = e as { response?: { data?: { message?: string } } };
      setError(err.response?.data?.message || 'Gagal absen');
      setStep('error');
    }
  };

  const isClockedIn = !!attendance?.clock_in_at;
  const isClockedOut = !!attendance?.clock_out_at;

  return (
    <div className="p-4 pb-24 max-w-md mx-auto">
      <div className="text-center py-6">
        <p className="text-sm text-gray-500">{now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}</p>
        <p className="text-4xl font-bold text-gray-900 tabular-nums mt-1">
          {now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}
        </p>
        <p className="text-sm text-gray-600 mt-2">{user?.name}</p>
      </div>

      {schedule?.has_schedule && schedule.schedule?.shift && (
        <div className="card mb-4">
          <p className="text-xs text-gray-500">Jadwal Shift</p>
          <p className="font-semibold text-gray-900">{schedule.schedule.shift.name}</p>
          <p className="text-sm text-gray-600">
            {schedule.schedule.shift.start_time} – {schedule.schedule.shift.end_time}
          </p>
        </div>
      )}

      <div className="card mb-4">
        <p className="text-xs text-gray-500 mb-3">Status Absen</p>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <p className="text-xs text-gray-500 mb-1">Masuk</p>
            <p className="text-2xl font-bold tabular-nums">
              {isClockedIn ? formatTime(attendance!.clock_in_at) : '–'}
            </p>
            {attendance?.clock_in_status === 'on_time' && (
              <span className="badge bg-green-100 text-green-700 inline-flex items-center gap-1 mt-1">
                <CheckCircle2 size={10} />
                Tepat
              </span>
            )}
            {attendance?.clock_in_status === 'late' && (
              <span className="badge bg-red-100 text-red-700 inline-flex items-center gap-1 mt-1">
                <X size={10} />
                Telat
              </span>
            )}
          </div>
          <div>
            <p className="text-xs text-gray-500 mb-1">Pulang</p>
            <p className="text-2xl font-bold tabular-nums">
              {isClockedOut ? formatTime(attendance!.clock_out_at) : '–'}
            </p>
            {attendance?.clock_out_status === 'on_time' && (
              <span className="badge bg-green-100 text-green-700 inline-flex items-center gap-1 mt-1">
                <CheckCircle2 size={10} />
                Tepat
              </span>
            )}
            {attendance?.clock_out_status === 'early' && (
              <span className="badge bg-amber-100 text-amber-700 inline-flex items-center gap-1 mt-1">
                <X size={10} />
                Pulang Cepat
              </span>
            )}
          </div>
        </div>
      </div>

      {step === 'gps' && settings && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-end sm:items-center justify-center p-4">
          <div className="w-full max-w-md bg-white rounded-2xl p-6 space-y-4">
            <h2 className="text-lg font-bold">
              {action === 'clock_in' ? 'Clock In' : 'Clock Out'}
            </h2>
            <GpsChecker settings={settings} onPosition={onGps} onError={setError} />
            {error && <p className="text-sm text-red-600">{error}</p>}
            <div className="flex gap-2">
              <button onClick={() => setStep('idle')} className="btn-secondary flex-1">
                Batal
              </button>
              <button
                onClick={proceedToCamera}
                disabled={!position}
                className="btn-primary flex-1"
              >
                Lanjut
              </button>
            </div>
          </div>
        </div>
      )}

      {step === 'camera' && (
        <CameraCapture
          onCapture={onCapture}
          onCancel={() => setStep('gps')}
        />
      )}

      {step === 'uploading' && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center">
          <div className="bg-white rounded-2xl p-6 text-center">
            <Loader2 size={40} className="text-primary-500 animate-spin mx-auto mb-3" />
            <p className="font-semibold">Mengirim data absen...</p>
          </div>
        </div>
      )}

      {step === 'done' && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl p-6 text-center max-w-sm">
            <div className="w-16 h-16 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-3">
              <CheckCircle2 size={32} />
            </div>
            <p className="font-semibold text-lg">
              {action === 'clock_in' ? 'Clock In berhasil' : 'Clock Out berhasil'}
            </p>
          </div>
        </div>
      )}

      {step === 'error' && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl p-6 text-center max-w-sm">
            <div className="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto mb-3">
              <X size={32} />
            </div>
            <p className="font-semibold text-lg mb-2">Gagal</p>
            <p className="text-sm text-gray-600 mb-4">{error}</p>
            <button onClick={() => setStep('idle')} className="btn-primary w-full">
              Tutup
            </button>
          </div>
        </div>
      )}

      <div className="grid grid-cols-2 gap-3 mt-4">
        <button
          onClick={() => startAttendance('clock_in')}
          disabled={loading || isClockedIn || step !== 'idle'}
          className="btn-primary"
        >
          <LogIn size={18} />
          {isClockedIn ? 'Sudah Clock In' : 'Clock In'}
        </button>
        <button
          onClick={() => startAttendance('clock_out')}
          disabled={loading || !isClockedIn || isClockedOut || step !== 'idle'}
          className="btn-danger"
        >
          <LogOut size={18} />
          {isClockedOut ? 'Sudah Clock Out' : 'Clock Out'}
        </button>
      </div>
    </div>
  );
}
