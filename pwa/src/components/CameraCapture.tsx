import { useEffect, useRef, useState } from 'react';
import { X, RotateCw } from 'lucide-react';

interface Props {
  onCapture: (blob: Blob) => void;
  onCancel: () => void;
}

export default function CameraCapture({ onCapture, onCancel }: Props) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const [error, setError] = useState<string | null>(null);
  const [facingMode, setFacingMode] = useState<'user' | 'environment'>('user');
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let active = true;
    let currentStream: MediaStream | null = null;

    async function start() {
      try {
        if (currentStream) {
          currentStream.getTracks().forEach((t) => t.stop());
        }
        const s = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: { ideal: facingMode }, width: { ideal: 720 }, height: { ideal: 720 } },
          audio: false,
        });
        if (!active) {
          s.getTracks().forEach((t) => t.stop());
          return;
        }
        currentStream = s;
        if (videoRef.current) {
          videoRef.current.srcObject = s;
          await videoRef.current.play();
          setReady(true);
        }
      } catch (e: unknown) {
        setError(e instanceof Error ? e.message : 'Tidak bisa akses kamera');
      }
    }

    setReady(false);
    start();

    return () => {
      active = false;
      if (currentStream) {
        currentStream.getTracks().forEach((t) => t.stop());
      }
    };
  }, [facingMode]);

  const handleSnap = () => {
    if (!videoRef.current || !canvasRef.current) return;
    const video = videoRef.current;
    const canvas = canvasRef.current;
    const size = Math.min(video.videoWidth, video.videoHeight);
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const sx = (video.videoWidth - size) / 2;
    const sy = (video.videoHeight - size) / 2;
    ctx.drawImage(video, sx, sy, size, size, 0, 0, size, size);
    canvas.toBlob(
      (blob) => {
        if (blob) onCapture(blob);
      },
      'image/jpeg',
      0.85,
    );
  };

  return (
    <div className="fixed inset-0 z-50 bg-black flex flex-col">
      {error ? (
        <div className="flex-1 flex flex-col items-center justify-center p-6 text-center text-white">
          <p className="text-lg font-semibold mb-2">Kamera tidak bisa diakses</p>
          <p className="text-sm text-white/70 mb-6">{error}</p>
          <p className="text-xs text-white/50 mb-6 max-w-xs">
            Pastikan Anda mengizinkan akses kamera di pengaturan browser, lalu coba lagi.
          </p>
          <button onClick={onCancel} className="btn-secondary">
            Tutup
          </button>
        </div>
      ) : (
        <>
          <div className="flex-1 flex items-center justify-center relative overflow-hidden">
            <video
              ref={videoRef}
              autoPlay
              playsInline
              muted
              className="w-full h-full object-cover"
            />
            <div className="absolute inset-0 pointer-events-none flex items-center justify-center">
              <div className="w-64 h-64 rounded-full border-4 border-white/80" />
            </div>
            <button
              onClick={onCancel}
              className="absolute top-4 right-4 w-10 h-10 rounded-full bg-black/50 text-white flex items-center justify-center hover:bg-black/70"
              aria-label="Tutup kamera"
            >
              <X size={20} />
            </button>
          </div>
          <div className="bg-black p-6 flex items-center justify-around">
            <div className="w-12" />
            <button
              onClick={handleSnap}
              disabled={!ready}
              className="w-20 h-20 rounded-full bg-white ring-4 ring-white/30 active:scale-95 transition disabled:opacity-50"
              aria-label="Ambil foto"
            />
            <button
              onClick={() => setFacingMode((f) => (f === 'user' ? 'environment' : 'user'))}
              className="w-12 h-12 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20"
              aria-label="Putar kamera"
            >
              <RotateCw size={20} />
            </button>
          </div>
          <canvas ref={canvasRef} className="hidden" />
        </>
      )}
    </div>
  );
}
