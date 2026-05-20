ALTER TABLE sessions ADD COLUMN prioritize_goalkeepers INTEGER NOT NULL DEFAULT 1;
ALTER TABLE matches ADD COLUMN score_team_a INTEGER NOT NULL DEFAULT 0;
ALTER TABLE matches ADD COLUMN score_team_b INTEGER NOT NULL DEFAULT 0;
ALTER TABLE matches ADD COLUMN transfer_player_id INTEGER NULL;
ALTER TABLE matches ADD COLUMN transfer_to_team_id INTEGER NULL;
ALTER TABLE matches ADD COLUMN transfer_mode TEXT NULL;

CREATE TABLE IF NOT EXISTS match_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    match_id INTEGER NOT NULL,
    team_id INTEGER NOT NULL,
    player_id INTEGER NOT NULL,
    event_type TEXT NOT NULL,
    related_player_id INTEGER NULL,
    event_order INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (player_id) REFERENCES players(id) ON DELETE CASCADE,
    FOREIGN KEY (related_player_id) REFERENCES players(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_match_events_match ON match_events(match_id);
CREATE INDEX IF NOT EXISTS idx_match_events_team ON match_events(team_id);
CREATE INDEX IF NOT EXISTS idx_match_events_player ON match_events(player_id);
