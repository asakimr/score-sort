CREATE TABLE IF NOT EXISTS matches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_id INTEGER NOT NULL,
    team_a_id INTEGER NOT NULL,
    team_b_id INTEGER NOT NULL,
    winner_team_id INTEGER NULL,
    loser_team_id INTEGER NULL,
    match_order INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (team_a_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (team_b_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_team_id) REFERENCES teams(id) ON DELETE SET NULL,
    FOREIGN KEY (loser_team_id) REFERENCES teams(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_matches_session ON matches(session_id);
