CREATE TABLE IF NOT EXISTS session_attendances (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL,
    player_id INTEGER NOT NULL,
    is_present INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    UNIQUE(session_id, player_id)
);

CREATE INDEX IF NOT EXISTS idx_attendances_session ON session_attendances(session_id);
CREATE INDEX IF NOT EXISTS idx_attendances_player ON session_attendances(player_id);
