export type Role = 'admin' | 'employee';

export interface User {
  id: number;
  name: string;
  email: string;
  role: Role;
  employee_id: string | null;
  position: string | null;
  phone: string | null;
  photo_url: string | null;
}

export type ClockStatus = 'on_time' | 'late' | 'early' | null;

export interface Attendance {
  id: number;
  work_date: string;
  clock_in_at: string | null;
  clock_in_status: ClockStatus;
  clock_in_photo_url: string | null;
  clock_in_distance_m: number | null;
  clock_out_at: string | null;
  clock_out_status: ClockStatus;
  clock_out_photo_url: string | null;
  clock_out_distance_m: number | null;
}

export interface Shift {
  id: number;
  name: string;
  start_time: string;
  end_time: string;
  tolerance_minutes: number;
}

export interface ScheduleToday {
  has_schedule: boolean;
  schedule: {
    id: number;
    work_date: string;
    is_off: boolean;
    notes: string | null;
    shift: Shift | null;
  } | null;
}

export interface ClinicSettings {
  clinic_lat: number;
  clinic_lng: number;
  radius_meter: number;
  late_tolerance: number;
  min_clock_out_minutes: number;
}

export type LeaveType = 'cuti' | 'izin' | 'sakit';
export type LeaveStatus = 'pending' | 'approved' | 'rejected';

export interface LeaveRequest {
  id: number;
  type: LeaveType;
  start_date: string;
  end_date: string;
  total_days: number;
  reason: string;
  attachment_url: string | null;
  status: LeaveStatus;
  admin_note: string | null;
  created_at: string;
  approved_at: string | null;
}

export interface Notification {
  id: string;
  type: string;
  data: {
    title: string;
    body: string;
  };
  read_at: string | null;
  created_at: string;
}

export interface PendingSubmission {
  id?: number;
  type: 'clock_in' | 'clock_out' | 'leave';
  payload: Record<string, unknown>;
  created_at: number;
}
