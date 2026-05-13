USE starstyle;

CREATE TABLE IF NOT EXISTS staff_shifts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    shift_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    repeat_mode VARCHAR(20) NOT NULL DEFAULT 'none',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    shift_start TIME NOT NULL,
    shift_end TIME NOT NULL,
    clock_in TIME NOT NULL,
    clock_out TIME NOT NULL,
    source VARCHAR(40) NOT NULL DEFAULT '-',
    status VARCHAR(30) NOT NULL DEFAULT 'Ontime',
    selfie_in_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    selfie_out_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
