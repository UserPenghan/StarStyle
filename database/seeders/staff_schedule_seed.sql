USE starstyle;

INSERT INTO staff_shifts (staff_id, shift_date, start_time, end_time, repeat_mode)
VALUES
    (1, '2026-05-03', '08:00:00', '17:00:00', 'weekly'),
    (2, '2026-05-02', '08:00:00', '17:00:00', 'weekly');

INSERT INTO staff_attendance (staff_id, attendance_date, shift_start, shift_end, clock_in, clock_out, source, status, selfie_in_score, selfie_out_score)
VALUES
    (1, '2026-05-03', '08:00:00', '17:00:00', '08:00:00', '17:00:00', 'Manual', 'Ontime', 0, 0),
    (2, '2026-05-02', '08:00:00', '17:00:00', '08:00:00', '17:48:00', 'Manual', 'Overtime', 0, 0);
