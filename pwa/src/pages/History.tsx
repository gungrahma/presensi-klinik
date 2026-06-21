import { useEffect, useState } from 'react';
import { ChevronLeft, ChevronRight, Calendar, CheckCircle2, XCircle } from 'lucide-react';
import { api } from '../services/api';
import type { Attendance } from '../types';
import { formatDate, formatTime } from '../utils/geofence';

export default function History() {
  const [data, setData] = useState<Attendance[]>([]);
  const [loading, setLoading] = useState(true);
  const [month, setMonth] = useState(new Date().getMonth() + 1);
  const [year, setYear] = useState(new Date().getFullYear());

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      try {
        const res = await api.get('/attendance/history', { params: { month, year } });
        setData(res.data.data);
      } catch {
        // ignore
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [month, year]);

  const monthName = new Date(year, month - 1, 1).toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });

  return (
    <div className="p-4 pb-24 max-w-md mx-auto">
      <h1 className="text-2xl font-bold mb-4">Riwayat Absensi</h1>

      <div className="card mb-4">
        <div className="flex items-center justify-between">
          <button
            onClick={() => {
              if (month === 1) {
                setMonth(12);
                setYear(year - 1);
              } else {
                setMonth(month - 1);
              }
            }}
            className="text-gray-600 hover:bg-gray-100 rounded-lg p-2"
            aria-label="Bulan sebelumnya"
          >
            <ChevronLeft size={20} />
          </button>
          <p className="font-semibold capitalize">{monthName}</p>
          <button
            onClick={() => {
              if (month === 12) {
                setMonth(1);
                setYear(year + 1);
              } else {
                setMonth(month + 1);
              }
            }}
            className="text-gray-600 hover:bg-gray-100 rounded-lg p-2"
            aria-label="Bulan berikutnya"
          >
            <ChevronRight size={20} />
          </button>
        </div>
      </div>

      {loading ? (
        <div className="text-center py-10 text-gray-500">Memuat data...</div>
      ) : data.length === 0 ? (
        <div className="text-center py-16">
          <div className="inline-flex w-14 h-14 rounded-full bg-gray-100 items-center justify-center text-gray-400 mb-3">
            <Calendar size={24} />
          </div>
          <p className="text-gray-600 font-medium">Tidak ada catatan absensi</p>
          <p className="text-sm text-gray-400 mt-1">Belum ada absensi di bulan ini</p>
        </div>
      ) : (
        <div className="space-y-2">
          {data.map((a) => (
            <div key={a.id} className="card flex items-center gap-3">
              <div className="flex-1 min-w-0">
                <p className="font-semibold text-gray-900">{formatDate(a.work_date)}</p>
                <div className="flex items-center gap-3 text-sm text-gray-600 mt-1.5">
                  <span className="inline-flex items-center gap-1">
                    <span className="w-1.5 h-1.5 rounded-full bg-green-500" />
                    {formatTime(a.clock_in_at)}
                  </span>
                  <span className="inline-flex items-center gap-1">
                    <span className="w-1.5 h-1.5 rounded-full bg-red-500" />
                    {formatTime(a.clock_out_at)}
                  </span>
                </div>
              </div>
              <div className="flex flex-col gap-1 items-end shrink-0">
                {a.clock_in_status === 'on_time' && (
                  <span className="badge bg-green-100 text-green-700 inline-flex items-center gap-1">
                    <CheckCircle2 size={10} />
                    Tepat
                  </span>
                )}
                {a.clock_in_status === 'late' && (
                  <span className="badge bg-red-100 text-red-700 inline-flex items-center gap-1">
                    <XCircle size={10} />
                    Telat
                  </span>
                )}
                {a.clock_out_status === 'early' && (
                  <span className="badge bg-amber-100 text-amber-700 inline-flex items-center gap-1">
                    <XCircle size={10} />
                    Pulang Cepat
                  </span>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
