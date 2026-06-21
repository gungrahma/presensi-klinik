import { useEffect, useState, type FormEvent } from 'react';
import { Plus, Calendar, CheckCircle2, X } from 'lucide-react';
import { api, queueSubmission } from '../services/api';
import type { LeaveRequest, LeaveType } from '../types';
import { formatDate, timeAgo } from '../utils/geofence';

export default function Leave() {
  const [list, setList] = useState<LeaveRequest[]>([]);
  const [loading, setLoading] = useState(true);
  const [showForm, setShowForm] = useState(false);
  const [type, setType] = useState<LeaveType>('izin');
  const [startDate, setStartDate] = useState(new Date().toISOString().split('T')[0]);
  const [endDate, setEndDate] = useState(new Date().toISOString().split('T')[0]);
  const [reason, setReason] = useState('');
  const [attachment, setAttachment] = useState<File | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState(false);

  const fetch = async () => {
    setLoading(true);
    try {
      const res = await api.get('/leave-requests');
      setList(res.data.data);
    } catch {
      // ignore
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetch();
  }, []);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      const form = new FormData();
      form.append('type', type);
      form.append('start_date', startDate);
      form.append('end_date', endDate);
      form.append('reason', reason);
      if (attachment) form.append('attachment', attachment);
      await api.post('/leave-requests', form, { headers: { 'Content-Type': 'multipart/form-data' } });
      setShowForm(false);
      setReason('');
      setAttachment(null);
      setSuccess(true);
      setTimeout(() => setSuccess(false), 3000);
      fetch();
    } catch (e: unknown) {
      if (!navigator.onLine) {
        await queueSubmission({
          type: 'leave',
          payload: { type, start_date: startDate, end_date: endDate, reason, attachment },
        });
        setError('Tidak ada koneksi. Pengajuan disimpan dan akan dikirim saat online.');
      } else {
        const err = e as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };
        setError(
          err.response?.data?.message ||
            Object.values(err.response?.data?.errors || {}).flat()[0] ||
            'Gagal mengirim pengajuan',
        );
      }
    } finally {
      setSubmitting(false);
    }
  };

  const statusBadge = (s: string) => {
    const map = {
      pending: 'bg-amber-100 text-amber-700',
      approved: 'bg-green-100 text-green-700',
      rejected: 'bg-red-100 text-red-700',
    } as Record<string, string>;
    const label = { pending: 'Menunggu', approved: 'Disetujui', rejected: 'Ditolak' } as Record<string, string>;
    return <span className={`badge ${map[s]}`}>{label[s]}</span>;
  };

  const typeBadge = (t: string) => {
    const map = {
      cuti: 'bg-blue-100 text-blue-700',
      izin: 'bg-amber-100 text-amber-700',
      sakit: 'bg-red-100 text-red-700',
    } as Record<string, string>;
    return <span className={`badge ${map[t]} capitalize`}>{t}</span>;
  };

  return (
    <div className="p-4 pb-24 max-w-md mx-auto">
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-2xl font-bold">Cuti & Izin</h1>
        <button onClick={() => setShowForm(true)} className="btn-primary !px-4 !py-2">
          <Plus size={18} />
          Ajukan
        </button>
      </div>

      {success && (
        <div className="mb-4 rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-700 inline-flex items-center gap-2 w-full">
          <CheckCircle2 size={16} />
          Pengajuan berhasil dikirim, menunggu persetujuan admin.
        </div>
      )}

      {showForm && (
        <div className="fixed inset-0 z-50 bg-black/50 flex items-end sm:items-center justify-center">
          <form onSubmit={onSubmit} className="w-full max-w-md bg-white rounded-t-2xl sm:rounded-2xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-bold">Pengajuan Baru</h2>
              <button type="button" onClick={() => setShowForm(false)} className="text-gray-400 hover:text-gray-600">
                <X size={20} />
              </button>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Jenis Pengajuan</label>
              <div className="grid grid-cols-3 gap-2">
                {(['cuti', 'izin', 'sakit'] as LeaveType[]).map((t) => (
                  <button
                    key={t}
                    type="button"
                    onClick={() => setType(t)}
                    className={`rounded-lg px-3 py-2 text-sm font-medium border transition capitalize ${
                      type === t
                        ? 'border-primary-500 bg-primary-50 text-primary-700'
                        : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300'
                    }`}
                  >
                    {t}
                  </button>
                ))}
              </div>
            </div>
            <div className="grid grid-cols-2 gap-3">
              <div>
                <label htmlFor="start" className="block text-sm font-medium text-gray-700 mb-1">
                  Dari
                </label>
                <input
                  id="start"
                  type="date"
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                  className="input"
                  required
                />
              </div>
              <div>
                <label htmlFor="end" className="block text-sm font-medium text-gray-700 mb-1">
                  Sampai
                </label>
                <input
                  id="end"
                  type="date"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                  className="input"
                  required
                  min={startDate}
                />
              </div>
            </div>
            <div>
              <label htmlFor="reason" className="block text-sm font-medium text-gray-700 mb-1">
                Alasan
              </label>
              <textarea
                id="reason"
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                className="input"
                rows={3}
                required
                maxLength={1000}
                placeholder="Jelaskan alasan pengajuan..."
              />
            </div>
            <div>
              <label htmlFor="attachment" className="block text-sm font-medium text-gray-700 mb-1">
                Lampiran <span className="text-gray-400">(opsional, misal surat dokter)</span>
              </label>
              <input
                id="attachment"
                type="file"
                accept="image/*"
                onChange={(e) => setAttachment(e.target.files?.[0] || null)}
                className="input text-sm"
              />
            </div>
            {error && (
              <div className="rounded-lg bg-red-50 border border-red-100 p-3 text-sm text-red-700">{error}</div>
            )}
            <div className="flex gap-2 pt-2">
              <button type="button" onClick={() => setShowForm(false)} className="btn-secondary flex-1">
                Batal
              </button>
              <button type="submit" disabled={submitting} className="btn-primary flex-1">
                {submitting ? 'Mengirim...' : 'Kirim'}
              </button>
            </div>
          </form>
        </div>
      )}

      {loading ? (
        <div className="text-center py-10 text-gray-500">Memuat data...</div>
      ) : list.length === 0 ? (
        <div className="text-center py-16">
          <div className="inline-flex w-14 h-14 rounded-full bg-gray-100 items-center justify-center text-gray-400 mb-3">
            <Calendar size={24} />
          </div>
          <p className="text-gray-600 font-medium">Belum ada pengajuan</p>
          <p className="text-sm text-gray-400 mt-1">Ajukan cuti atau izin untuk pertama kalinya</p>
        </div>
      ) : (
        <div className="space-y-2">
          {list.map((l) => (
            <div key={l.id} className="card">
              <div className="flex items-start justify-between mb-2 gap-2">
                <div className="flex items-center gap-2 flex-wrap">
                  {typeBadge(l.type)}
                  {statusBadge(l.status)}
                </div>
                <p className="text-xs text-gray-500 shrink-0">{timeAgo(l.created_at)}</p>
              </div>
              <p className="text-sm text-gray-900 font-medium">
                {formatDate(l.start_date)} → {formatDate(l.end_date)}
                <span className="text-gray-500 font-normal"> · {l.total_days} hari</span>
              </p>
              <p className="text-sm text-gray-600 mt-1.5">{l.reason}</p>
              {l.admin_note && (
                <div className="mt-2 pt-2 border-t border-gray-100">
                  <p className="text-xs text-gray-500 italic">Catatan: {l.admin_note}</p>
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
