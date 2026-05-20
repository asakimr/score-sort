CREATE TABLE IF NOT EXISTS match_transfers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    match_id INTEGER NOT NULL,
    player_id INTEGER NOT NULL,
    from_team_id INTEGER NOT NULL,
    to_team_id INTEGER NOT NULL,
    transfer_mode TEXT NOT NULL DEFAULT 'manual',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (from_team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (to_team_id) REFERENCES teams(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_match_transfers_match ON match_transfers(match_id);
