import { useEffect, useState } from 'react';
import { MapPin, RefreshCw, CheckCircle2, XCircle, Loader2 } from 'lucide-react';
import { haversineDistance, getCurrentPosition } from '../utils/geofence';
import type { ClinicSettings } from '../types';

interface Props {
  settings: ClinicSettings | null;
  onPosition: (lat: number, lng: number, accuracy: number) => void;
  onError?: (msg: string) => void;
  compact?: boolean;
}

interface State {
  lat: number | null;
  lng: number | null;
  accuracy: number | null;
  distance: number | null;
  loading: boolean;
  error: string | null;
  within: boolean;
}

export default function GpsChecker({ settings, onPosition, onError, compact = false }: Props) {
  const [state, setState] = useState<State>({
    lat: null,
    lng: null,
    accuracy: null,
    distance: null,
    loading: false,
    error: null,
    within: false,
  });

  const fetchPosition = async () => {
    setState((s) => ({ ...s, loading: true, error: null }));
    try {
      const pos = await getCurrentPosition();
      const { latitude, longitude, accuracy } = pos.coords;
      const dist = settings
        ? haversineDistance(latitude, longitude, settings.clinic_lat, settings.clinic_lng)
        : null;
      const within = dist !== null && settings ? dist <= settings.radius_meter : false;
      setState({
        lat: latitude,
        lng: longitude,
        accuracy: Math.round(accuracy),
        distance: dist,
        loading: false,
        error: null,
        within,
      });
      onPosition(latitude, longitude, Math.round(accuracy));
      if (!within && onError && dist !== null && settings) {
        onError(`Anda ${dist}m dari klinik (maks ${settings.radius_meter}m)`);
      }
    } catch (e: unknown) {
      const msg = e instanceof Error ? e.message : 'Gagal mendapatkan lokasi';
      setState((s) => ({ ...s, loading: false, error: msg }));
      onError?.(msg);
    }
  };

  useEffect(() => {
    fetchPosition();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [settings?.clinic_lat, settings?.clinic_lng]);

  if (compact) {
    return (
      <div className="flex items-center gap-2 text-sm">
        {state.loading ? (
          <span className="text-gray-500 inline-flex items-center gap-1.5">
            <Loader2 size={14} className="animate-spin" />
            Mengambil lokasi...
          </span>
        ) : state.error ? (
          <span className="text-red-600 inline-flex items-center gap-1.5">
            <XCircle size={14} />
            {state.error}
          </span>
        ) : state.within ? (
          <span className="text-green-600 inline-flex items-center gap-1.5">
            <CheckCircle2 size={14} />
            Dalam radius ({state.distance}m)
          </span>
        ) : (
          <span className="text-red-600 inline-flex items-center gap-1.5">
            <XCircle size={14} />
            Di luar radius ({state.distance}m)
          </span>
        )}
        <button
          onClick={fetchPosition}
          className="text-primary-600 text-xs font-semibold inline-flex items-center gap-1 disabled:opacity-50"
          disabled={state.loading}
        >
          <RefreshCw size={12} className={state.loading ? 'animate-spin' : ''} />
          Refresh
        </button>
      </div>
    );
  }

  return (
    <div className="card">
      <div className="flex items-start justify-between mb-2">
        <div>
          <p className="font-semibold text-gray-900 inline-flex items-center gap-2">
            <MapPin size={16} className="text-gray-500" />
            Lokasi Anda
          </p>
          {state.loading ? (
            <p className="text-sm text-gray-500 inline-flex items-center gap-1.5 mt-1">
              <Loader2 size={12} className="animate-spin" />
              Mengambil lokasi...
            </p>
          ) : state.error ? (
            <p className="text-sm text-red-600 mt-1">{state.error}</p>
          ) : state.distance !== null ? (
            <p className="text-sm mt-1">
              {state.within ? (
                <span className="text-green-600 font-medium inline-flex items-center gap-1.5">
                  <CheckCircle2 size={14} />
                  Dalam radius klinik ({state.distance}m)
                </span>
              ) : (
                <span className="text-red-600 font-medium inline-flex items-center gap-1.5">
                  <XCircle size={14} />
                  Di luar radius ({state.distance}m / maks {settings?.radius_meter}m)
                </span>
              )}
            </p>
          ) : null}
        </div>
        <button
          onClick={fetchPosition}
          disabled={state.loading}
          className="text-xs font-semibold text-primary-600 inline-flex items-center gap-1 disabled:opacity-50"
        >
          <RefreshCw size={12} className={state.loading ? 'animate-spin' : ''} />
          {state.loading ? 'Memuat...' : 'Refresh'}
        </button>
      </div>
      {state.lat && state.lng && (
        <div className="text-xs text-gray-500 space-y-0.5 font-mono">
          <p>Lat: {state.lat.toFixed(6)}</p>
          <p>Lng: {state.lng.toFixed(6)}</p>
          {state.accuracy && <p>Akurasi: ±{state.accuracy}m</p>}
        </div>
      )}
    </div>
  );
}
