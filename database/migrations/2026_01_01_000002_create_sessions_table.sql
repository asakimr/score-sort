CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_date TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    max_players_per_match INTEGER NOT NULL DEFAULT 10,
    draw_mode TEXT NOT NULL DEFAULT 'balanced',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_sessions_date ON sessions(session_date);
